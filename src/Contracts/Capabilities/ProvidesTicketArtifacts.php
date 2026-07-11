<?php

namespace Iabduul7\ThemeParkAdapters\Contracts\Capabilities;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\TicketArtifact;
use Illuminate\Support\Collection;

/**
 * Normalises a provider's booking/order response into redeemable {@see TicketArtifact}s.
 *
 * This is the provider-native half of voucher support: every provider returns the
 * redeemable identifier in a different place (Disney supplier reference, SeaWorld
 * per-guest barcode, Universal visualID), so the knowledge of WHERE it lives and its
 * native format belongs in the adapters. Turning artifacts into branded PDFs (barcode
 * images, templates, storage, delivery) stays in the consuming app.
 */
interface ProvidesTicketArtifacts
{
    /**
     * @param  array<string, mixed>|null  $response  the raw response from createNewBooking/getBooking (Redeam) or placeOrder (SmartOrder)
     * @return Collection<int, TicketArtifact>
     */
    public function tickets(?array $response): Collection;
}
