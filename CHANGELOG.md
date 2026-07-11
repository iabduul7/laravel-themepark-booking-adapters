# Changelog

All notable changes to `laravel-themepark-booking-adapters` will be documented in this file.

## 4.1.0 - 2026-07-12

### Added

- `DisneyRedeamAdapter::getParkAvailabilityData()` — the raw park-availability feed, decoded exactly
  as the observability endpoint returns it (each date maps to a status plus a parks map keyed by
  park name). This is the boundary-correct primitive: shaping the feed for display belongs to the
  consuming app.

### Deprecated

- `DisneyRedeamAdapter::getParkAvailability()` — the transformed calendar shape (full/partial/none
  rollup, reversed park order, `®` stripping, `nice_date` with embedded HTML) is presentation logic,
  not provider data. Kept as a drop-in parity wrapper over `getParkAvailabilityData()`; will be
  removed in the next major version.

### Fixed

- Result DTOs no longer serialize their adapter back-reference — queued DTOs (e.g. a sync job
  type-hinting `Product`) previously embedded provider credentials in queue payloads. Unserialized
  DTOs degrade to adapter-less behaviour (adapter-backed accessors like `Product::getRates()` return
  an empty result instead of erroring).
- `Product::getRates()` no longer throws a `TypeError` for SeaWorld/United Parks products — it now
  delegates to the adapter's supplier-aware rate lookup instead of assuming the Disney-style
  `getProductRates(string $productId, ...)` signature.
- Date parameters across the Redeam adapters (`checkAvailability()`, `checkAvailabilities()`,
  `getProductAvailability()`, `getProductPricingSchedule()`, `getProductRatePricingSchedule()`, and
  Disney's `getParkAvailability()`) now accept any `DateTimeInterface`, not just
  `Illuminate\Support\Carbon`. A base `Carbon\Carbon` instance previously failed the class check and
  was silently coerced via `__toString()` into the wrong format before reaching the API.
- `getProductRatePricingSchedule()` called without a `$rateId` now returns the full multi-rate
  schedule again, matching upstream — a `?? ''` coalesce was breaking `Arr::get()`'s "a null key
  returns the whole array" behaviour.
- `CacheTokenRepository::storeToken()` no longer hands the cache a non-positive TTL, which Laravel
  silently treats as `forget()`. Tokens shorter than the 60 second safety buffer are now simply
  never cached, instead of appearing to cache and then vanishing.
- Disney's `getParkAvailability()` now actually strips `®` from park names — the previous
  encode/replace/decode dance never matched, because `json_encode()` escapes `®` before the
  `str_replace()` runs.
- The SmartOrder 401 self-heal retry no longer mints a redundant third OAuth token when the token
  cache is disabled — the token minted for the retry is now reused by the retried request instead
  of being discarded.

### Changed

- The SmartOrder token cache key now derives from the adapter's provider name plus a credentials
  fingerprint (a short hash of host, `client_username` and `customer_id`), instead of just the
  provider name, so two same-provider adapter instances with different credentials no longer share
  a cached token.
- The Universal adapter now requires `customer_id` at construction, alongside `client_username` and
  `client_secret` — a missing value previously dropped `customerId` out of every request silently
  instead of failing fast.
- `Contracts\Capabilities\ProvidesTicketArtifacts::tickets()` now accepts `?array` — the natural
  `$adapter->tickets($adapter->placeOrder(...))` pattern previously risked a `TypeError`, since
  `placeOrder()`/`getExistingOrder()` return `?array`.
- Removed the never-read `providers.*.enabled` config keys (`DISNEY_ENABLED`, `SEAWORLD_ENABLED`,
  `UNIVERSAL_ENABLED`).
- Removed the dead `Database\Factories` autoload mapping (the directory does not exist).

### Docs

- README now documents `deleteBooking()`'s raw-Response return.

## 4.0.0 - 2026-06-28

### Changed (breaking)

- **HTTP failures are now surfaced as exceptions instead of empty arrays.** Every read and write
  throws `Exceptions\ThemeParkApiException` on a 4xx/5xx response, carrying the HTTP status
  (`getCode()`) and the decoded response body (`getResponseData()`). Previously a failed request was
  silently decoded to `[]`, so callers could not distinguish "no data" from "request failed". The
  retry policy is unchanged — idempotent reads are still retried on connection drops / 5xx, writes
  are never retried; only the *final* response is inspected.
- Single-entity lookups now raise the dedicated 404 exceptions: `getProduct()` (Disney/SeaWorld)
  throws `ThemeParkApiException::productNotFound()`, and the SmartOrder `getExistingOrder()` throws
  `orderNotFound()`, when the provider returns 404.

### Fixed

- `validateCredentials()` now genuinely returns `false` on an auth failure (401/403) — previously it
  could only ever return `true`, because transport errors were swallowed.
- The SmartOrder OAuth token request (`/connect/token`) and the post-401 self-heal retry now check
  the response status, so a non-401 error no longer masquerades as a generic "failed to obtain token"
  / empty body.
- The Disney park-availability endpoint is now issued as a retried, status-checked read (previously
  unretried with a silent `?? []`).

### Internal / CI

- Added contract tests asserting failure propagation across all three adapters, plus unit coverage
  for `Support\Redeam\OptionCodeResolver` and the SeaWorld supplier endpoints (52 tests total).

## 3.0.0 - 2026-06-21

### Changed (breaking)

- **Raised the PHP floor to `^8.2`** (dropped PHP 8.1) and **moved to Laravel 12/13 only**
  (`illuminate/contracts ^12.0|^13.0`, dropping Laravel 10 and 11). Rationale:
  `CVE-2026-48019` (CRLF injection in the framework's default email rule) affects every
  Laravel release below 12.60 / 13.10 with **no back-patch to the 10.x or 11.x lines**, and the
  last Pest/PHPUnit toolchain that supported PHP 8.1 was itself pinned to a CVE-affected PHPUnit.
  Staying on the security-patched Laravel line is only possible on PHP 8.2+. The production
  footprint (`illuminate/contracts`, `guzzlehttp/guzzle`, `spatie/laravel-package-tools`) now
  audits clean across the whole matrix.

### Internal / CI

- Upgraded the dev toolchain to Laravel 12/13: `orchestra/testbench ^10|^11`,
  `phpunit/phpunit ^11.5.3|^12.0`, `larastan/larastan ^3.0`, `phpstan/phpstan-* ^2.0`,
  `nunomaduro/collision ^8.0`, `brianium/paratest ^7.4`.
- Removed the unused Pest dependency (`pestphp/pest*`) — every test is plain PHPUnit; `composer test`
  now runs `phpunit` directly.
- Modernised `phpunit.xml.dist` to the current schema and made coverage **on-demand** (a plain
  `composer test` no longer forces coverage, so jobs without a driver don't trip `failOnWarning`).
- Refreshed the CI matrices to PHP 8.2/8.3/8.4 × Laravel 12/13 and applied the latest Pint style
  rules.

## 2.0.0 - 2026-06-21

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
