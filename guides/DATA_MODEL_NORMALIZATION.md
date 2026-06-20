# Backend Data-Model Normalization Plan (products / tickets / prices)

A **future roadmap** for normalizing the production backend (`knowbeforeugo-backend`)'s
products / tickets / prices so all three parks — **Disney** & **SeaWorld/United Parks** (Redeam) and
**Universal** (SmartOrder) — share one clean, provider-neutral schema instead of the current
Redeam/SmartOrder-shaped columns.

> **Proven by a reference implementation.** The `kbug-adaptor-testing` app drives this exact target
> schema from the package adapters (`ThemePark::provider('disney'|'seaworld'|'universal')`) against
> the live sandbox — **all three parks**, both Redeam *and* SmartOrder/Universal — so the shape below
> is validated, not theoretical. The Universal side runs off the package's opt-in catalog building
> block (see `UNIVERSAL_CATALOG.md`). This document is the plan to bring the backend to it — applied
> with **additive migrations only** (see Migration strategy).

## Current backend state (denormalized)

- **`tickets`** — one wide table mixing generic columns with Redeam-only columns
  (`redeam_rate_id`, `redeam_unit_id`, `redeam_price_id`, `redeam_option_code`, `commission`,
  `adjustment`) and SmartOrder-only columns (`sales_program_id`, `is_express`, `is_promo`).
- **Two price tables** — `redeem_ticket_prices` (Redeam: `net`, `retail`, `tax`, `schedule_price_id`)
  and `dated_ticket_prices` (SmartOrder: `price`, `tax`, `total`, `option`, `days`, `ticket_data`),
  **both carrying a redundant `product_id`** (derivable through the ticket).
- Provider is the `ProductProvidersEnum` string (no lookup table) — fine, kept as-is.

## Target model

```
Product 1 ──── * Ticket 1 ──── 1  RedeamTicketDetail       (1:1, Redeam parks)
                       │  └──── 1  SmartOrderTicketDetail   (1:1, Universal)
                       └──── *  TicketPrice                 (dated prices, all parks)
```

- A **price belongs to a ticket**; the product is reached *through* the ticket — drop `product_id`
  from the price table(s). `Product::prices()` becomes `hasManyThrough(TicketPrice, Ticket)`.
- Provider-specific attributes live in **1:1 detail tables**, not on `tickets`.
- The two price tables merge into one provider-neutral **`ticket_prices`** (`(ticket_id, date)` unique).

### Field classification

| Concern | Generic (`tickets`) | Redeam (`redeam_ticket_details`) | SmartOrder (`smartorder_ticket_details`) |
| --- | --- | --- | --- |
| Ticket | product_id, name, api_name, age_value, days, start_date, end_date, is_dated, **provider**, api_identifier, ticket_data, is_available, is_visible | rate_id, unit_id, price_id, option_code, commission, adjustment | sales_program_id, is_express, is_promo |

`api_identifier` is the provider's option/PLU reference (Redeam `optionId`, SmartOrder `plu`) and stays
generic. The natural ticket identity (product + rate + unit) now resolves via the detail relation:
`whereHas('redeamDetail', fn ($q) => $q->where('rate_id', …)->where('unit_id', …))`.

### Unified `ticket_prices`

| Column | Redeam fills | SmartOrder fills |
| --- | --- | --- |
| `price` | original / `net` | `price` |
| `net`, `retail` | ✓ | — |
| `tax` | ✓ | ✓ |
| `total`, `option`, `days` | — | ✓ |
| `schedule_price_id` | ✓ | — |
| `ticket_data` | — | ✓ |

Provider is **not** stored on the price (derive via `price->ticket->provider`).

## Migration strategy — additive, backfilled (expand → migrate → contract)

The backend has live data, so this is **not** a schema rewrite. **Never edit or delete an existing
migration; only add new ones**, and roll out with the expand-and-contract pattern so reads/writes
never break:

1. **Expand** — add the new structures without removing anything:
   - `create_redeam_ticket_details_table` (+ `create_smartorder_ticket_details_table`), 1:1 on `tickets`.
   - `create_ticket_prices_table` (the unified superset).
2. **Backfill** — a data migration (or queued backfill job for large tables) copies existing data:
   - tickets' `redeam_*`/`commission`/`adjustment` → `redeam_ticket_details`; SmartOrder columns →
     `smartorder_ticket_details`.
   - `redeem_ticket_prices` + `dated_ticket_prices` rows → `ticket_prices`.
3. **Cut over** — deploy code that reads/writes the new tables/relations (the sync services + models,
   as the reference app demonstrates). Run dual-write briefly if you want a safety window.
4. **Contract** — only after cutover is verified, add migrations that drop the old columns
   (`redeam_*`, `commission`, `adjustment`, `sales_program_id`, …), drop the redundant `product_id`,
   and drop the old `redeem_ticket_prices` / `dated_ticket_prices` tables.

Every migration ships a real `down()` so each step is reversible. Rename `api_provider` → `provider`
on `tickets` via a column-rename migration (not a column drop/recreate) to preserve data.

> The reference app shows the **end shape** of every table and the working sync/relation code; its
> migration history is itself additive (`normalize_tickets_table`, `create_redeam_ticket_details_table`,
> `rename_redeem_ticket_prices_to_ticket_prices`, `create_smartorder_ticket_details_table`) — mirror
> that ordering in the backend with backfill steps added for the live data. Both 1:1 detail tables
> (`redeam_ticket_details`, `smartorder_ticket_details`) and the unified `ticket_prices` are now built
> and exercised against the live sandbox for all three parks.

## Why these choices

- **1:1 detail tables** over JSON/EAV — strongly typed and indexable (`rate_id`,`unit_id`); each
  provider's columns stay isolated, so adding Universal never widens shared tables.
- **Keep the enum** — provider is a fixed small set; a lookup table would add joins and ripple through
  every model/scope for no real gain.
- **Unify prices** — both providers are "one price per ticket per date"; a single table with nullable
  provider columns + `ticket_data` covers both and simplifies querying a product's price calendar.
