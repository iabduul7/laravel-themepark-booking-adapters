<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\TicketArtifact;
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\SeaWorld\SeaWorldRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\Universal\UniversalSmartOrder2Adapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;

/**
 * Contract tests for the provider-native voucher data layer: tickets() normalises
 * each provider's booking/order response into redeemable TicketArtifacts. The
 * fixtures mirror real sandbox captures — Disney's will-call supplier reference
 * (ext."supplier.reference"), SeaWorld's per-guest barcodes, Universal's
 * createdTicketResponses[].visualID — so the extraction paths match production shapes.
 */
class TicketArtifactTest extends AdapterTestCase
{
    private function disney(): DisneyRedeamAdapter
    {
        return new DisneyRedeamAdapter(['api_key' => 'k', 'api_secret' => 's', 'supplier_id' => '20']);
    }

    private function seaworld(): SeaWorldRedeamAdapter
    {
        return new SeaWorldRedeamAdapter(['api_key' => 'k', 'api_secret' => 's']);
    }

    private function universal(): UniversalSmartOrder2Adapter
    {
        return new UniversalSmartOrder2Adapter([
            'client_username' => 'u', 'client_secret' => 's', 'customer_id' => 1, 'token_cache' => false,
        ]);
    }

    public function test_disney_extracts_a_single_will_call_reference_from_booking_ext(): void
    {
        // "supplier.reference" is a literal dotted key (real Redeam Disney shape).
        $response = ['booking' => [
            'status' => 'OPEN',
            'customer' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'],
            'items' => [['rate' => ['name' => 'FL Res. 4-Day Ticket']]],
            'ext' => [
                'supplier.reference' => 'FZUB79111111',
                'disney-ticketStartDate' => '2026-08-22',
                'disney-ticketEndDate' => '2026-08-29',
            ],
        ]];

        $tickets = $this->disney()->tickets($response);

        $this->assertCount(1, $tickets);
        $artifact = $tickets->first();
        $this->assertInstanceOf(TicketArtifact::class, $artifact);
        $this->assertSame('FZUB79111111', $artifact->getIdentifier());
        $this->assertSame(TicketArtifact::FORMAT_CODE39, $artifact->getFormat());
        $this->assertSame(TicketArtifact::REDEMPTION_WILL_CALL, $artifact->getRedemption());
        $this->assertTrue($artifact->isWillCall());
        $this->assertSame('Ada Lovelace', $artifact->getTravelerName());
        $this->assertSame('FL Res. 4-Day Ticket', $artifact->getProductName());
        $this->assertSame('2026-08-22', $artifact->getValidFrom());
        $this->assertSame('2026-08-29', $artifact->getValidTo());
        $this->assertSame('disney', $artifact->getProvider());
    }

    public function test_disney_returns_empty_when_no_supplier_reference_present(): void
    {
        $this->assertCount(0, $this->disney()->tickets(['booking' => ['ext' => []]]));
    }

    public function test_seaworld_extracts_one_scannable_barcode_per_guest(): void
    {
        $response = ['booking' => [
            'status' => 'OPEN',
            'tickets' => [
                ['barcode' => ['value' => 'SW-AAA-1'], 'leadTraveler' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'name' => 'SeaWorld 1-Day'],
                ['barcode' => ['value' => 'SW-BBB-2'], 'leadTraveler' => ['firstName' => 'Alan', 'lastName' => 'Turing'], 'name' => 'SeaWorld 1-Day'],
            ],
        ]];

        $tickets = $this->seaworld()->tickets($response);

        $this->assertCount(2, $tickets);
        $this->assertSame(['SW-AAA-1', 'SW-BBB-2'], $tickets->map->getIdentifier()->all());
        $this->assertSame('Grace Hopper', $tickets->first()->getTravelerName());
        $this->assertSame(TicketArtifact::REDEMPTION_SCAN, $tickets->first()->getRedemption());
        $this->assertSame(TicketArtifact::FORMAT_CODE39, $tickets->first()->getFormat());
        $this->assertSame('seaworld', $tickets->first()->getProvider());
    }

    public function test_universal_extracts_one_artifact_per_created_ticket_response(): void
    {
        $response = [
            'success' => true,
            'galaxyOrderId' => 'G123',
            'createdTicketResponses' => [
                [
                    'plu' => '110111110001', 'visualID' => 'V1', 'firstName' => 'Mary', 'lastName' => 'Shelley',
                    'validityRules' => [['calculatedStartDateTime' => '2026-07-01T00:00:00', 'calculatedExpirationDateTime' => '2026-12-31T23:59:59']],
                ],
                ['plu' => '110111110002', 'visualID' => 'V2', 'firstName' => 'Mary', 'lastName' => 'Shelley'],
            ],
        ];

        $tickets = $this->universal()->tickets($response);

        $this->assertCount(2, $tickets);
        $first = $tickets->first();
        $this->assertSame('V1', $first->getIdentifier());
        $this->assertSame('110111110001', $first->getPlu());
        $this->assertSame('Mary Shelley', $first->getTravelerName());
        $this->assertSame('2026-07-01T00:00:00', $first->getValidFrom());
        $this->assertSame('2026-12-31T23:59:59', $first->getValidTo());
        $this->assertSame('CONFIRMED', $first->getStatus());
        $this->assertSame(TicketArtifact::FORMAT_CODE39, $first->getFormat());
        $this->assertSame('universal', $first->getProvider());
    }

    public function test_empty_responses_yield_no_artifacts(): void
    {
        $this->assertCount(0, $this->seaworld()->tickets([]));
        $this->assertCount(0, $this->universal()->tickets([]));
    }

    public function test_null_response_yields_no_artifacts_for_every_provider(): void
    {
        // placeOrder()/getExistingOrder() return ?array, so tickets() must accept
        // the natural `$adapter->tickets($adapter->placeOrder(...))` pattern without
        // a TypeError when the provider returned no body.
        $this->assertCount(0, $this->disney()->tickets(null));
        $this->assertCount(0, $this->seaworld()->tickets(null));
        $this->assertCount(0, $this->universal()->tickets(null));
    }
}
