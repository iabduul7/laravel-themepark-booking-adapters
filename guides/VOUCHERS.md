# Vouchers — the provider/app boundary

A voucher has two halves, and they sit on opposite sides of the package boundary:

1. **The redeemable artifact** (the scannable/redeemable identifier the provider returns) — this is
   **provider-native data**. *Where* it lives, its *cardinality*, and its *native format* differ per
   provider, so the package adapters own extracting it.
2. **The rendered voucher** (barcode/QR image, branded PDF, terms, delivery) — this is **app
   presentation + infrastructure**. It varies per customer/brand, not per provider, so it stays in the
   consuming app.

The package ships layer 1 only. Layer 2 is demonstrated as a *reference* renderer in the test app
(`kbug-adaptor-testing`), mirroring how `knowbeforeugo-backend` does it — but consuming **only** the
package's typed artifacts, never re-parsing a provider response.

## Layer 1 — provider-native voucher data (in the package)

`ProvidesTicketArtifacts::tickets(array $response): Collection<int, TicketArtifact>`, implemented by
all three adapters. Pass the raw `createNewBooking`/`getBooking` (Redeam) or `placeOrder` (SmartOrder)
response; get back normalized [`TicketArtifact`](../src/DataTransferObjects/Results/TicketArtifact.php)
DTOs.

| Provider | Source field (in the response) | Cardinality | Format | Redemption |
| --- | --- | --- | --- | --- |
| **Disney** | `booking.ext["supplier.reference"]` (a literal dotted key) | **1 per order** | CODE39 | will-call |
| **SeaWorld** | `booking.tickets[].barcode.value` (+ `leadTraveler`) | **1 per guest** | CODE39 | scan |
| **Universal** | `createdTicketResponses[].visualID` (+ `plu`, names, `validityRules`) | **1 per ticket** | CODE39¹ | scan |

¹ Universal Epic Universe is scanned as QR upstream, but `placeOrder` carries no product type, so the
package defaults the format hint to CODE39; the app upgrades Epic to QR via the catalog
(`isEpicAccess`). This is the one format decision that is genuinely app-side.

`TicketArtifact` exposes: `getIdentifier()`, `getFormat()` (`FORMAT_CODE39`/`FORMAT_QR`),
`getRedemption()` (`REDEMPTION_WILL_CALL`/`REDEMPTION_SCAN`), `getTravelerName()`, `getProductName()`,
`getPlu()`, `getValidFrom()`, `getValidTo()`, `getStatus()`, `getProvider()`, plus `isQr()` /
`isWillCall()`.

```php
$booking = $disney->createNewBooking([...]);        // raw provider response
$artifacts = $disney->tickets($booking);            // Collection<TicketArtifact>
$ref = $artifacts->first()->getIdentifier();        // e.g. "FZUB79111111"
```

Why this is "part of the Providers": Disney hides its redeemable value in `ext["supplier.reference"]`
(a will-call pickup ref — Disney returns **no** per-traveler barcode), SeaWorld returns a scannable
barcode per guest, and Universal returns a `visualID` per ticket inline. The knowledge of where each
lives — and that GET on a Disney "canonical" product just aliases back to the public product, so the
identifier always comes from the booking response — is provider-specific and belongs in the adapters.

## Layer 2 — rendering (in the app)

Everything below is app-side and deliberately **not** in the package, because it is presentation +
KBYG-specific + infrastructure:

- **Barcode/QR image generation** (`milon/barcode` → `data:image/png;base64`).
- **Templates** (Blade): will-call confirmation vs turnstile ticket vs EZ-Ticket; legal terms,
  redemption instructions, park lists, branding, banner colours. The banner/variant selection
  (HHN/express/park-to-park) is KBYG categorisation — the same app-only labelling as promo/HHN.
- **PDF** (`barryvdh/laravel-dompdf`), **storage** (disk/path), **delivery** (mail attachment).

Reference implementation: [`app/Services/Vouchers/VoucherRenderer.php`](../../testing/kbug-adaptor-testing/app/Services/Vouchers/VoucherRenderer.php)
+ `resources/views/vouchers/document.blade.php`. It takes the package's `TicketArtifact` collection and
a tiny `{reference, customer}` context, and produces the PDF. The redemption *mode* drives the
template (will-call vs scan) — that mode comes from the artifact; the wording is the app's.

## Proof

- **Package** — `tests/Adapters/TicketArtifactTest.php`: extraction for all three providers off
  fixtures matching the real sandbox shapes.
- **App (CI)** — `tests/Feature/VoucherTest.php`: package `tickets()` → `VoucherRenderer` → asserts the
  identifier, barcode image, redemption mode and a real `%PDF` file.
- **App (live)** — `php artisan booking:smoke --voucher` renders a real PDF from a live booking/order
  (Disney will-call ref + Universal visualID), then auto-cancels the booking.

## Backend swap-in note

The backend's three `generateVoucher()` methods each re-read the provider response inline
(`booking.ext`, `createdTicketResponses`, `tickets[]`). When it adopts this package, that extraction
collapses to `$adapter->tickets($response)`; the existing Blade templates + dompdf + mail pipeline stay
exactly as they are — only the "where is the identifier" logic moves into the package.
