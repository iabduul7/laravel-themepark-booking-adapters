<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

use Iabduul7\ThemeParkAdapters\Contracts\Capabilities\ProvidesTicketArtifacts;

/**
 * A single redeemable artifact extracted from a provider booking/order response —
 * the provider-native half of a "voucher". This DTO carries only what the provider
 * returns (the scannable/redeemable identifier + its metadata); rendering it into a
 * branded PDF (barcode image, template, terms, delivery) is a consuming-app concern.
 *
 * Each provider exposes the identifier in a different place and shape, which is why
 * extraction lives in the adapters ({@see ProvidesTicketArtifacts}):
 *  - Disney (Redeam):   booking.ext["supplier.reference"] — one will-call reference per order.
 *  - SeaWorld (Redeam): booking.tickets[].barcode.value   — one scannable barcode per guest.
 *  - Universal (SO2):   createdTicketResponses[].visualID — one scannable id per ticket.
 */
class TicketArtifact extends Result
{
    public const FORMAT_CODE39 = 'CODE39';

    public const FORMAT_QR = 'QR';

    /** Redeemed by presenting an id at a will-call window (Disney WDW). */
    public const REDEMPTION_WILL_CALL = 'will-call';

    /** Redeemed by scanning a barcode/QR at a turnstile (SeaWorld, Universal). */
    public const REDEMPTION_SCAN = 'scan';

    /**
     * The scannable/redeemable value — Disney supplier reference, SeaWorld barcode
     * value, or Universal visualID. This is what a barcode/QR is rendered from.
     */
    public function getIdentifier(): ?string
    {
        return $this->get('identifier');
    }

    /**
     * Provider-native barcode format hint: CODE39 by default; QR where the provider
     * requires it (e.g. Universal Epic Universe). The app may override at render time.
     */
    public function getFormat(): string
    {
        return $this->get('format', self::FORMAT_CODE39);
    }

    /**
     * How the artifact is redeemed — will-call pickup (Disney) vs scan at the gate
     * (SeaWorld/Universal). Lets the app pick the right template/instructions.
     */
    public function getRedemption(): string
    {
        return $this->get('redemption', self::REDEMPTION_SCAN);
    }

    public function getTravelerName(): ?string
    {
        return $this->get('traveler_name');
    }

    public function getProductName(): ?string
    {
        return $this->get('product_name');
    }

    /** SmartOrder PLU (Universal only); null for Redeam. */
    public function getPlu(): ?string
    {
        return $this->get('plu');
    }

    public function getValidFrom(): ?string
    {
        return $this->get('valid_from');
    }

    public function getValidTo(): ?string
    {
        return $this->get('valid_to');
    }

    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    public function getProvider(): ?string
    {
        return $this->get('provider');
    }

    public function isQr(): bool
    {
        return $this->getFormat() === self::FORMAT_QR;
    }

    public function isWillCall(): bool
    {
        return $this->getRedemption() === self::REDEMPTION_WILL_CALL;
    }
}
