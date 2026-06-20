# Changelog

All notable changes to `laravel-themepark-booking-adapters` will be documented in this file.

## Unreleased

### Changed (breaking)

- Consolidated the package onto the independent `Iabduul7\ThemeParkAdapters` namespace and **removed
  the legacy `iabduul7\ThemeParkBooking` namespace** (its adapters, `BookingAdapterInterface`, `Data`
  DTOs, `Services`, `ThemeParkBookingManager`/`ThemeParkBookingServiceProvider`, `ThemeParkBooking`
  facade, HTTP clients, Eloquent models, scope concerns, commands and utilities).
- The package is now **API-integration only**: the bundled order-details migrations and Eloquent
  models were removed. Persistence, queue/sync jobs and voucher rendering belong in the consuming
  application.

### Added

- Drop-in, signature-compatible provider adapters for all three production parks:
  `Providers\Disney\DisneyRedeamAdapter`, `Providers\SeaWorld\SeaWorldRedeamAdapter` and
  `Providers\Universal\UniversalSmartOrder2Adapter`, resolvable via `ThemePark::provider()`.
- Family base classes `Abstracts\AbstractRedeamAdapter` and `Abstracts\AbstractSmartOrderAdapter`
  with resilient transport (retry on idempotent reads, no retry on writes) built on the Laravel
  `Http` facade; SmartOrder OAuth2 with token caching and 401 self-heal.
- Capability interfaces `Contracts\Capabilities\SupportsHolds` and `SupportsEvents`.
- Provider-native voucher **data** layer: `Contracts\Capabilities\ProvidesTicketArtifacts` with
  `tickets(array $response)` on all three adapters, normalising each provider's redeemable artifact
  (Disney `ext."supplier.reference"`, SeaWorld `tickets[].barcode.value`, Universal
  `createdTicketResponses[].visualID`) into `DataTransferObjects\Results\TicketArtifact` DTOs. Voucher
  *rendering* (barcode images, templates, PDF, delivery) remains in the consuming app — see
  `guides/VOUCHERS.md`.
- Typed result objects under `DataTransferObjects\Results\*` (`Supplier`, `Product`, `Rate`,
  `PriceSchedule`, `RatePriceSchedule`, `Availability`, `Hold`, `Booking`).
- Opt-in Walt Disney World ticket option-code helper `Support\Redeam\OptionCodeResolver`
  (exposed on the Redeam adapters as `getOptionCode()`). Commission/margin resolution is
  intentionally left to the consuming application — it is operator pricing, not provider data.
- `Http::fake()` adapter contract tests covering endpoints, auth, retry, 401 self-heal and DTOs.
- `guides/CLEANER_API_REFERENCE.md` describing the proposed normalised v2 interface.

### Fixed

- The Universal adapter now targets the correct production SmartOrder endpoints
  (`/smartorder/MyProductCatalog`, `/smartorder/FindEvents`, `/smartorder/PlaceOrder`,
  `/smartorder/{GetExistingOrderId,CanCancelOrder,CancelOrder}`) and injects `customer_id`,
  replacing the previous non-functional `/Product/GetAll`-style endpoints.
- Provider adapters now satisfy their interface/return-type contracts (previously a fatal type
  mismatch left the independent namespace non-instantiable and unautoloaded).
