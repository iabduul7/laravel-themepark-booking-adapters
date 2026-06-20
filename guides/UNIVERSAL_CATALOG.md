# Universal / SmartOrder Catalog — building block + reference sync

How Universal (SmartOrder / "SmartOrder2") product data flows from the API into the normalised
`Product → Ticket → {SmartOrderTicketDetail, TicketPrice}` model — and **exactly where the package
boundary sits**, because Universal needs far more interpretation than the Redeam parks.

> **End goal:** this package's catalog building block plus the reference app's Universal services are
> the intended drop-in replacement for the backend's local `packages/laravel-smartorder` product /
> ticket / price sync. The package owns the provider transport + a generic, provider-native filter
> surface; the application owns the KBYG-specific catalog interpretation. Keeping that split clean is
> the whole point of this document.

---

## The boundary in one line

- **Package** = Universal's own API: the catalog call + filters over fields Universal itself returns.
- **App** = KBYG's interpretation: the curated PLU set, product grains, blueprints, "promo"/"HHN"
  labels, and persistence.

Nothing KBYG-specific lives in the package. The package never decides what a "product" is — it
returns Universal's catalog entries (PLUs); the app collapses them into KBYG products.

---

## Package: the `catalog()` building block

`UniversalSmartOrder2Adapter::catalog(array $options = []): CatalogEntryCollection` is an opt-in layer
over the raw `getAllProducts()` (`GET /smartorder/MyProductCatalog`). It passes native query params
through, flattens the nested `catalogBySalesProgram[]`, tags each entry with its sales program, and
returns a filterable collection of typed DTOs.

```php
$catalog = ThemePark::provider('universal')->catalog([
    'startDateInclusive' => '2026-07-01',   // string|DateTimeInterface
    'endDateInclusive'   => '2026-12-31',
    'retrieveOnly'       => 'future',        // future | current | (omit = both)
    'pricing'            => 'base',          // base   | discounted | (omit = both)
    'collapseDates'      => false,           // true = fold equal-price days into ranges
]);

$catalog->salesProgram(4689)   // ignored server-side, so applied client-side
        ->expressPasses()      // isLimitedExpress || isUnlimitedExpress
        ->themeParkAccess()    // isThemeParkAccess
        ->parkToPark()         // isParkToPark
        ->epicAccess()         // isEpicAccess
        ->days(3)              // numberOfDays === 3   (also multiDay(), singleDay())
        ->forAge('A')          // ageValue: A | C | NA
        ->raw();               // escape hatch — never hides fields
```

### Native parameters (verified against the sandbox)

| Param | Role | Notes |
| --- | --- | --- |
| `customerId` | required account | injected by the transport — **do not pass** |
| `startDateInclusive` / `endDateInclusive` | bound the `pricesByDay` calendar | **required** — without a window the price-scheduled catalog returns no entries |
| `retrieveOnly` | `future` \| `current` | default returns both pricing horizons |
| `pricing` | `base` \| `discounted` | default returns both buckets |
| `collapseDates` | bool | folds contiguous equal-price days into ranges (see `PricePoint`) |
| `salesProgramId` | int | **ignored server-side** (returns every program), so `catalog()` also filters it client-side |

### DTOs

**`CatalogEntry`** — one PLU. Every accessor reads a field Universal returns:
`getPlu()`, `getProductName()`, `getProductKind()`, `getNumberOfDays()`, `getAgeValue()`,
`getResidencyRequirement()`, `isThemeParkAccess()`, `isParkToPark()`, `isEpicAccess()`,
`isLimitedExpress()`, `isUnlimitedExpress()`, `isExpressPass()`, `requiresFindEvents()`,
`getSalesProgramId()` / `getSalesProgramName()` (injected from the parent bucket), plus pricing via
`futureBasePrices()`, `futureDiscountedPrices()`, `currentBasePrices()`, `currentDiscountedPrices()`
or the generic `prices($window, $type)`. `getData()` / `toArray()` return the untouched payload.

**`PricePoint`** — one price row, normalising both shapes:
- per-day (default): `getDate()` set, `getEndDate()` null, `isRange() === false`.
- collapsed (`collapseDates=true`): one row per contiguous equal-price span, `getDate()` ..
  `getEndDate()`, `isRange() === true`.

Tax is provider-shaped (`tax1 + tax2`) via `getTax()`; `getPrice()`, `getTotal()`, and discount
metadata (`isDiscounted()`, `getDiscountType()`, `getDiscountAmount()`) round it out.

### Why there is **no** `promo()` / `hhn()` predicate

The catalog self-describes type via `isLimitedExpress`, `isUnlimitedExpress`, `isEpicAccess`,
`isParkToPark`, `isThemeParkAccess`, `numberOfDays`, `ageValue`, `salesProgramId` — so those filters
are legitimately package-native. But **"promo" and "HHN" are not provider concepts**: every entry
carries discount data (so "has a discount" ≠ promo), and no field flags HHN. They are KBYG labels —
applied in the app, never in the package.

---

## App: the reference Universal sync (the part that replaces `laravel-smartorder`)

Lives in the reference app (`kbug-adaptor-testing`) as `App\Services\Universal\*`, mirroring the
backend `SmartOrderJob` + `SmartOrderInsertDataService` so it is copy-pasteable into the backend —
**minus the dropped `domain_id` loop** (one ticket per PLU here).

### 1. Curated codes + sales-program routing (`UniversalInitialDataService`)

```
CODES = [1101, 1701, 1501, 1851, 1853, 150128]   // 1751, 1801 exist but are intentionally NOT sold
```

| Code(s) | Routed by | Sales program | Grain key | Product option | is_dated / is_express / is_promo |
| --- | --- | --- | --- | --- | --- |
| 1101, 1701 | salesProgram 4638 | 4638 | `SO2-EXPRESS` (collapsed) | DATED | 0 / 1 / 0 |
| 1501 | salesProgram 4638 | 4638 | `SO2-DAY-{n}` | NON_DATED | 0 / 0 / 0 |
| 1851 | salesProgram 4638 | 4638 | `SO2-CALENDAR-{n}` | DATED | 1 / 0 / 0 |
| 1853 | PLU prefix `1853` | 4689 | `SO2-PROMO` | DATED | 1 / 0 / 1 |
| 150128 | salesProgram 3552 | 3552 | `SO2-HHN` | DATED | 0 / 0 / 0 |

A **longer-prefix guard** keeps `1501` from swallowing `150128` PLUs. Day-based codes (`1501`,
`1851`) create one Product per unique `numberOfDays`; fixed codes collapse into a single Product.
Product blueprints (title/slug) are KBYG catalog/SEO decisions and live in the service.

### 2. Ticket field map (normalised split)

Generic columns → **`tickets`**: `product_id`, `name`/`api_name` = `productName`, `days` =
`numberOfDays`, `age_value` (`A`→Adult, `C`→Child, else null), `is_dated`, `api_identifier` = `plu`,
`provider` = `SmartOrder2`, `ticket_data`. Identity key = `(api_identifier, provider)`.

Provider columns → **`smartorder_ticket_details`** (1:1): `sales_program_id`, `is_express`,
`is_promo`. (`is_dated` stays generic on the ticket.)

### 3. Identifiers — sweep → upsert → re-link

Once per run, over every routed sales-program PLU: unlink + hide tickets whose PLU vanished, mark
their identifiers inactive, upsert the fresh identifiers, then re-link **only** swept tickets by
product name. Aborts (throws) if the catalog returned zero PLUs, so an API outage can never blank the
Universal catalogue.

### 4. Pricing → unified `ticket_prices` (`UniversalPricingDataService`)

- **Dated** (every live product): `futureBasePrices()` → one row per date, `(ticket_id, date)`
  upsert, deduped by date. Columns filled: `date`, `price`, `tax` (`tax1+tax2`), `total`, `days`,
  `option` (the product's option), `ticket_data` (entry minus the bulky price calendars). Redeam-only
  columns (`net`, `retail`, `schedule_price_id`) stay null.
- **Non-dated** (`1501`-style, no date grain): `currentPricing.basePriceData.singlePriceWithTaxResponse`
  → a single dateless row, `(ticket_id, date=null)` upsert.

---

## Verified against the live sandbox

`universal:sync --initial` then `--pricing` against the SmartOrder QA sandbox:

- **52** catalog entries → **39** tickets (1101×6, 1701×1, 1851×26, 1853×6; `1501`/`150128` absent in
  the sandbox, `1751`/`1801` correctly skipped = 13 not sold).
- **6** grains: `SO2-EXPRESS`, `SO2-CALENDAR-{2,3,4,5}`, `SO2-PROMO`.
- **52** identifiers (every routed PLU, including the unsold ones).
- **9,794** `ticket_prices` rows, zero `(ticket_id, date)` duplicates, all `option = Dated`,
  `net`/`retail` null (correctly Redeam-only) — confirming the unified price table serves both
  providers.

Contract tests: package `tests/Adapters/UniversalCatalogTest.php` (catalog building block, off a
trimmed real fixture) and reference-app `tests/Feature/UniversalSyncTest.php` (grains, tickets,
details, prices, idempotency, skipped non-CODES PLUs).

---

## Backend replacement map

| Concern | Today (backend `laravel-smartorder`) | After replacement |
| --- | --- | --- |
| Transport (`MyProductCatalog`, FindEvents, PlaceOrder, cancel) | `LaravelSmartOrder` / `SmartOrderApiClient` | **package** `UniversalSmartOrder2Adapter` |
| Catalog filtering | `getAllProducts` / `getNewPromoProducts` / `getAllHHNProducts` | **package** `catalog()` predicates (`salesProgram`, `expressPasses`, …) — *provider-native only* |
| CODES, grains, blueprints, salesProgram routing | `SmartOrderJob` + `SmartOrderInsertDataService` | **app** `UniversalInitialDataService` (this shape) |
| Product/ticket/identifier persistence | backend models (with `domain_id`) | **app** services into the normalised schema (no `domain_id`) |
| Price persistence (`dated_ticket_prices`) | `SmartOrderInsertDataService` | **app** `UniversalPricingDataService` → unified `ticket_prices` |

The backend adoption itself is staged and **additive-migration only** — see
`DATA_MODEL_NORMALIZATION.md`.
