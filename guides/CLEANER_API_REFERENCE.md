# Cleaner API Reference (proposed v2)

> **Status:** design reference, not yet implemented. The current adapters are deliberately a
> **drop-in** match for the upstream `LaravelRedeamForWaltDisney`, `LaravelRedeamForUnitedParks`
> and `SmartOrderClient` clients (same method names, signatures and return shapes) so the backend
> can adopt the package incrementally. This document captures the *normalised* interface we would
> expose in a future major version once drop-in adoption is no longer required.

## Why a v2

The drop-in adapters intentionally inherit the quirks of the upstream clients:

- **Inconsistent signatures across parks.** Disney's product/rate/availability calls take no
  `supplier_id` (it is fixed from config); SeaWorld/United Parks takes `supplier_id` as the leading
  argument. A caller cannot treat the two Redeam adapters interchangeably.
- **Two different lifecycles.** Redeam is hold→book (`SupportsHolds`); SmartOrder is a direct
  order (`SupportsEvents`). There is no shared booking verb.
- **Raw-array payloads in / out.** Holds, bookings and availability are associative arrays whose
  shape is provider-specific; reads return thin `Result` wrappers but writes do not.
- **Provider-named methods.** `getAllProducts`, `createNewHold`, `placeOrder`, `findEvents` leak the
  vendor vocabulary into application code.

A normalised interface lets application code target *one* contract regardless of park/provider, at
the cost of no longer being byte-compatible with the upstream clients.

## Proposed contract

```php
namespace Iabduul7\ThemeParkAdapters\Contracts;

interface BookingAdapter
{
    public function providerName(): string;
    public function validateCredentials(): bool;

    /** @return Collection<int, Product> */
    public function products(ProductQuery $query): Collection;
    public function product(ProductId $id): Product;

    /** @return Collection<int, Rate> */
    public function rates(ProductId $id): Collection;

    public function availability(AvailabilityQuery $query): Availability;
    public function pricing(PricingQuery $query): PricingSchedule;

    /** Reserve inventory. SmartOrder-style providers return an already-confirmed Reservation. */
    public function reserve(ReservationRequest $request): Reservation;

    /** Confirm a prior reservation (no-op/identity for direct-order providers). */
    public function confirm(Reservation $reservation, PaymentDetails $payment): Booking;

    public function booking(BookingId $id): Booking;
    public function cancel(BookingId $id, ?string $reason = null): CancellationResult;
}
```

### Value objects (replace primitive obsession + raw arrays)

`ProductId`, `SupplierId`, `RateId`, `BookingId`, `ReservationId`, `Money`, `DateRange`,
`ProductQuery`, `AvailabilityQuery`, `PricingQuery`, `ReservationRequest`, `PaymentDetails`,
`Guest` — immutable, validated, and serialisable.

### Domain models (replace `Result` wrappers)

`Product`, `Rate`, `Availability`, `PricingSchedule`, `Reservation`, `Booking`,
`CancellationResult` — typed accessors, no `get('dotted.path')`.

## How it maps onto today's adapters

| v2 method | Disney (Redeam) | SeaWorld (Redeam) | Universal (SmartOrder) |
| --- | --- | --- | --- |
| `products()` | `getAllProducts()` | `getAllProducts($supplierId)` | `getAllProducts()` (MyProductCatalog) |
| `rates()` | `getProductRates($id)` | `getProductRates($supplierId, $id)` | n/a (encoded in catalog) |
| `availability()` | `checkAvailabilities()` | `checkAvailabilities($supplierId, …)` | `findEvents()` |
| `pricing()` | `getProductPricingSchedule()` | `getProductPricingSchedule($supplierId, …)` | derived from catalog |
| `reserve()` | `createNewHold()` | `createNewHold()` | `placeOrder()` (confirmed immediately) |
| `confirm()` | `createNewBooking()` | `createNewBooking()` | identity |
| `cancel()` | `deleteBooking()` (PUT cancel) | `deleteBooking()` | `canCancelOrder()` + `cancelOrder()` |

`supplier_id` becomes part of the adapter's construction/context rather than a per-call argument, so
all parks share one signature. Direct-order providers implement `confirm()` as an identity over the
`Reservation` returned by `reserve()`.

## Migration path

1. Ship v2 contracts + value objects + domain models alongside the current drop-in adapters.
2. Provide a `NormalisedAdapter` decorator that wraps each existing provider adapter and adapts the
   drop-in surface to `BookingAdapter` (per the mapping table above).
3. Deprecate direct use of the provider-named methods in a minor release.
4. Promote `BookingAdapter` to the primary API in the next major release; keep the raw adapters
   available for advanced/escape-hatch use.

No backend changes are implied by this document — see the project plan and
[[../../.claude/plans]] for the staged adoption strategy.
