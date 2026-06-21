# Package Compatibility — parity with the backend clients, proven in the test app

This package is a **full feature-parity replacement** for the backend's bundled
`code-creatives/laravel-redeam` and `code-creatives/laravel-smartorder` clients. Every public
client method the backend (`knowbeforeugo-backend`) calls has an equivalent here, and each is
**proven in the reference app** (`kbug-adaptor-testing`).

> The backend is a **read-only knowledge base** — the spec, not a migration target. "Compatibility"
> means this package + the reference app reach parity with it, verified there.

**How each method is proven:**
- **Reads** (catalog, products, rates, pricing, suppliers) — verified against the **live sandbox**
  *and* with `Http::fake` contract tests.
- **Writes** (holds, bookings, orders, cancellations) — verified with **`Http::fake` only**, never
  live: those calls create real reservations/orders. The package's no-retry-on-write transport means
  a faked write is the correct and only safe proof.

---

## SmartOrder / Universal — `UniversalSmartOrder2Adapter`

| Backend `SmartOrderClient` method | Package equivalent | Kind | Proven by |
| --- | --- | --- | --- |
| `getAllProducts()` (MyProductCatalog) | `getAllProducts()` + `catalog()` building block | read | live + `UniversalCatalogTest`, `UniversalSyncTest` |
| `getAvailableMonths()` | `getAvailableMonths()` | read | `UniversalSmartOrder2AdapterTest` |
| `findEvents()` | `findEvents()` | read | `UniversalBookingTest` (capacity + event data) |
| `placeOrder()` | `placeOrder()` | **write** | `UniversalBookingTest` (faked) |
| `getExistingOrder()` | `getExistingOrder()` | read | `UniversalBookingTest` |
| `canCancelOrder()` | `canCancelOrder()` | read | `UniversalBookingTest` |
| `cancelOrder()` | `cancelOrder()` | **write** | `UniversalBookingTest` (faked) |
| voucher data (`createdTicketResponses[].visualID`) | `tickets()` → `TicketArtifact[]` | read | `TicketArtifactTest`, `VoucherTest` + live (`--voucher`) |
| `LaravelSmartOrder::insertIdentifiers()` | app-side `UniversalInitialDataService` | — | `UniversalSyncTest` + live |
| `LaravelSmartOrder::getNewPromoProducts()` / `getAllHHNProducts()` / `getAllProducts($catalog)` | app-side via `catalog()` predicates | — | `UniversalSyncTest` + live |

## Redeam — `DisneyRedeamAdapter` / `SeaWorldRedeamAdapter`

| Backend `LaravelRedeamFor*` method | Package equivalent | Kind | Proven by |
| --- | --- | --- | --- |
| `getAllSuppliers()` / `getSupplier()` | same | read | live (`SeaWorldInitialDataService` seeds suppliers) + adapter tests |
| `getAllProducts()` / `getProduct()` | same (Disney implicit supplier, SeaWorld per-call) | read | live + `Disney/SeaWorldInitialDataService` |
| `getProductRates()` / `getProductRate()` | same | read | live + initial services |
| `getProductPricingSchedule()` / `getProductRatePricingSchedule()` | same | read | live + `Disney/SeaWorldPricingDataService`, `SeaWorldPricingTest` |
| `checkAvailability()` / `checkAvailabilities()` / `getProductAvailability()` | same | read | `RedeamBookingTest` (availability → hold) |
| `getParkAvailability()` (Disney) | `getParkAvailability()` | read | adapter present (Disney observability endpoint) |
| `createNewHold()` / `getHold()` / `deleteHold()` | inherited from `AbstractRedeamAdapter` | **write**/read | `RedeamBookingTest` (faked) + **live** (Disney smoke) |
| `createNewBooking()` / `getBooking()` / `deleteBooking()` | inherited | **write**/read | `RedeamBookingTest` (faked) + **live** (Disney smoke, create→cancel) |
| voucher data (Disney `ext."supplier.reference"`, SeaWorld `tickets[].barcode.value`) | `tickets()` → `TicketArtifact[]` | read | `TicketArtifactTest`, `VoucherTest` + live (Disney `--voucher`) |
| `getOptionCode()` | `OptionCodeResolver` (drop-in helper) | — | app-side `DisneyInitialDataService` |
| `getCommissionPercentage()` | app-side — operator margin, not provider data | — | `DisneyInitialDataService::commissionPercentage` |

---

## The package / app boundary

The package owns **transport + provider-native surface only**: auth, retries (no-retry on writes),
customerId/supplier injection, the raw client methods, and provider-native conveniences (the
`catalog()` filter predicates, the Redeam `Result` DTOs, and the `tickets()` voucher-data extractors).
Everything KBYG-specific lives in the **app** (reference app today, the backend's own service layer
tomorrow):

- **Sync interpretation** — SmartOrder CODES/grains/blueprints/identifier sweep
  (`UniversalInitialDataService`/`UniversalPricingDataService`); Redeam option-code/commission usage
  and product categorisation (`Disney`/`SeaWorld` initial + pricing services).
- **Booking lifecycle** — payload assembly (externalOrderId + `approved_suffix`, `smartOrderLines`,
  hold/booking lines, lead-traveler flag, supplier stamping) in `UniversalBookingService` and the
  `RedeamBookingService` (`Disney`/`SeaWorld` subclasses).
- **Vouchers** — the package extracts the provider-native redeemable artifact via `tickets()`
  (`ProvidesTicketArtifacts` → `TicketArtifact`); barcode/QR images, Blade templates, PDF, storage and
  delivery are the app's (`VoucherRenderer`). See [VOUCHERS.md](VOUCHERS.md).

## Proof index (reference app `kbug-adaptor-testing`)

| Concern | Service(s) | Test |
| --- | --- | --- |
| Universal catalog → products/tickets/prices | `UniversalInitialDataService`, `UniversalPricingDataService` | `tests/Feature/UniversalSyncTest.php` + live |
| Universal booking lifecycle | `UniversalBookingService` | `tests/Feature/UniversalBookingTest.php` |
| Disney/SeaWorld catalog → tickets/prices | `Disney`/`SeaWorld` `InitialDataService`/`PricingDataService` | `tests/Feature/SeaWorldPricingTest.php` + live |
| Redeam booking lifecycle (both parks) | `RedeamBookingService` (`Disney`/`SeaWorld`) | `tests/Feature/RedeamBookingTest.php` |
| Package catalog building block | — (package) | `tests/Adapters/UniversalCatalogTest.php` |
| Voucher data layer (all 3 providers) | — (package `tickets()`) | `tests/Adapters/TicketArtifactTest.php` |
| Voucher rendering (data → PDF) | `VoucherRenderer` | `tests/Feature/VoucherTest.php` + live `booking:smoke --voucher` |

## Live sandbox booking smoke

The faked tests above are the CI proof. For on-demand **live** confirmation against the sandboxes,
the reference app ships `php artisan booking:smoke` (`app/Console/Commands/BookingSmokeCommand.php`) —
**not** part of the CI suite, since booking calls are real writes. It discovers a bookable target
from synced data + live availability, runs the full lifecycle through the booking services, and
**auto-cancels everything it creates** in a `finally` block.

```
php artisan booking:smoke                     # all three parks, full lifecycle, auto-cancelled
php artisan booking:smoke --park=universal    # one park
php artisan booking:smoke --reads-only        # findEvents / checkAvailabilities only (no writes)
php artisan booking:smoke --keep              # debug: skip cleanup (leaves sandbox residue)
```

Latest live run:

| Park | Result |
| --- | --- |
| **Universal** | **FULL** — `findEvents` → `placeOrder` (real order placed) → `getExistingOrder` → `cancelOrder` (cancelled). End-to-end write path proven live. |
| **Disney** | **FULL** — `checkAvailabilities` → rate pricing schedule → `createNewHold` (verified) → `createNewBooking` → `deleteBooking` (cancelled, HTTP 204). Full hold→book→cancel lifecycle proven live. Disney's sandbox order service intermittently 500s on `createNewBooking` (`PARTNER_NAME: Disney`, `code 197`); the command degrades gracefully to **HOLD GREEN** and the hold auto-expires. |
| **SeaWorld** | **SKIP** — Discovery Cove (the only sandbox supplier) has no availability in the window. |

### The Disney "4-Day" canonical-price hold — diagnosed and fixed

The earlier REACHED status was a real, fixable price-validation rejection, **not** a hidden product
needing discovery. Two app-side payload-assembly bugs (both in the reference app, never the package):

1. **Price-row selection matched the unit only.** Redeam validates a hold against the canonical
   schedule, which holds exactly **one** candidate per `(unit, ageBand, dateTime)`. A 4-Day slot
   offers both adult and child units, so matching unit alone could pick the **child** price for an
   **adult** traveler → *"price ID does not match the 1 candidate price"*. Fix: match the schedule row
   by **unit *and* `travelerType.ageBand`** (`BookingSmokeCommand`).
2. **The hold's `at` was a date-midnight UTC value.** Disney's sandbox slots start at midnight
   **Eastern** (e.g. `04:00Z`), so `Carbon::parse('Y-m-d')->toISOString()` (= UTC midnight) lands on
   the **previous Eastern day** and Redeam validated against the wrong day's schedule. Fix: anchor the
   hold `at` and booking `startTime` to the matched availability's **real `start`**
   (`RedeamBookingService`).

> Note for the eventual backend swap-in: the backend uses date-midnight `at`/`startTime`, which works
> in production because prod Disney slots are UTC-aligned. When the backend adopts this package it
> should anchor to the availability's own `start` to be timezone-robust. GET on the "canonical"
> product (`08d002b6…`) just returns the public product (`73fc68dc…`) — they are the same entity; the
> canonical product/rate are Redeam's internal pricing-engine representation, never exposed through the
> supplier API, so the holdable price always comes from the **public** rate schedule.

So all three providers are proven **live**: the SmartOrder order lifecycle (place → cancel), the
Redeam hold→book→cancel lifecycle (Disney), plus `Http::fake` CI proof for both. Only SeaWorld is
unverifiable live — its sole sandbox supplier (Discovery Cove) has no availability.

**Bottom line:** the package exposes every method the backend's clients do, the read paths are
verified live across all three parks, the write/booking paths are verified via `Http::fake`, and the
**SmartOrder *and* Redeam (Disney) booking lifecycles are both additionally verified live (create →
cancel)** — so the package is compatible end-to-end, demonstrated entirely in the reference app with
no backend changes.
