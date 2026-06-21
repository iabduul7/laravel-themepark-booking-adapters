<?php

namespace Iabduul7\ThemeParkAdapters\Providers\SeaWorld;

use Iabduul7\ThemeParkAdapters\Abstracts\AbstractRedeamAdapter;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\PriceSchedule;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Product;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Rate;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\RatePriceSchedule;
use Iabduul7\ThemeParkAdapters\Contracts\Capabilities\ProvidesTicketArtifacts;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Supplier;
use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\TicketArtifact;
use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * SeaWorld / United Parks adapter (Redeam). Drop-in compatible with the upstream
 * CodeCreatives\LaravelRedeam\LaravelRedeamForUnitedParks facade: the supplier is
 * passed per call (United Parks is multi-supplier), unlike the Disney adapter.
 * Holds and bookings remain top-level and are inherited from the Redeam base.
 */
class SeaWorldRedeamAdapter extends AbstractRedeamAdapter implements ProvidesTicketArtifacts
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (! $this->hasRequiredConfig(['api_key', 'api_secret'])) {
            throw ThemeParkApiException::invalidCredentials();
        }
    }

    public function getProviderName(): string
    {
        return 'seaworld';
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, Supplier>
     */
    public function getAllSuppliers(array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->getRequest('suppliers', $parameters), 'suppliers', []),
            Supplier::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getSupplier(string $supplierId, array $parameters = []): Supplier
    {
        return $this->parseData(
            Arr::get($this->getRequest("suppliers/{$supplierId}", $parameters), 'supplier', []),
            Supplier::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, Product>
     */
    public function getAllProducts(string $supplierId, array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->getRequest("suppliers/{$supplierId}/products", $parameters), 'products', []),
            Product::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProduct(string $supplierId, string $productId, array $parameters = []): Product
    {
        return $this->parseData(
            Arr::get($this->getRequest("suppliers/{$supplierId}/products/{$productId}", $parameters), 'product', []),
            Product::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, Rate>
     */
    public function getProductRates(string $supplierId, string $productId, array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->getRequest("suppliers/{$supplierId}/products/{$productId}/rates", $parameters), 'rates', []),
            Rate::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductRate(string $supplierId, string $productId, string $rateId, array $parameters = []): Rate
    {
        return $this->parseData(
            Arr::get($this->getRequest("suppliers/{$supplierId}/products/{$productId}/rates/{$rateId}", $parameters), 'rate', []),
            Rate::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailability(string $supplierId, string $productId, Carbon|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof Carbon ? $at->toISOString() : $at;

        return $this->getRequest(
            "suppliers/{$supplierId}/products/{$productId}/availability",
            array_merge($parameters, ['at' => $at, 'qty' => $qty])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailabilities(string $supplierId, string $productId, Carbon|string $start, Carbon|string $end, array $parameters = []): array
    {
        $start = $start instanceof Carbon ? $start->toISOString() : $start;
        $end = $end instanceof Carbon ? $end->toISOString() : $end;

        return $this->getRequest(
            "suppliers/{$supplierId}/products/{$productId}/availabilities",
            array_merge($parameters, ['start' => $start, 'end' => $end])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductAvailability(string $supplierId, string $productId, Carbon|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof Carbon ? $at->toISOString() : $at;

        return $this->getRequest(
            "suppliers/{$supplierId}/products/{$productId}/availability",
            array_merge($parameters, ['at' => $at, 'qty' => $qty])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductPricingSchedule(
        string $supplierId,
        string $productId,
        Carbon|string $startDate,
        Carbon|string $endDate,
        array $parameters = []
    ): PriceSchedule {
        $startDate = $startDate instanceof Carbon ? $startDate->toDateString() : $startDate;
        $endDate = $endDate instanceof Carbon ? $endDate->toDateString() : $endDate;

        return $this->parseData(
            $this->getRequest(
                "suppliers/{$supplierId}/products/{$productId}/pricing/schedule",
                array_merge($parameters, ['start_date' => $startDate, 'end_date' => $endDate])
            ),
            PriceSchedule::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductRatePricingSchedule(
        string $supplierId,
        string $productId,
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?string $rateId = null,
        array $parameters = []
    ): RatePriceSchedule {
        $startDate = $startDate instanceof Carbon ? $startDate->toDateString() : $startDate;
        $endDate = $endDate instanceof Carbon ? $endDate->toDateString() : $endDate;

        return $this->parseData(
            Arr::get($this->getRequest(
                "suppliers/{$supplierId}/products/{$productId}/pricing/schedule",
                array_merge($parameters, ['start_date' => $startDate, 'end_date' => $endDate, 'rate_id' => $rateId])
            ), $rateId ?? '', []),
            RatePriceSchedule::class
        );
    }

    public function validateCredentials(): bool
    {
        try {
            $this->getAllSuppliers();

            return true;
        } catch (ThemeParkApiException) {
            return false;
        }
    }

    /**
     * United Parks returns one scannable barcode per guest in booking.tickets[],
     * each with barcode.value and a leadTraveler. Modeled on the backend's
     * RedeamServiceForUnitedParks voucher extraction — the SeaWorld sandbox has no
     * bookable availability (Discovery Cove only) to capture this shape live.
     *
     * @param  array<string, mixed>  $response
     * @return Collection<int, TicketArtifact>
     */
    public function tickets(array $response): Collection
    {
        /** @var array<string, mixed> $booking */
        $booking = Arr::get($response, 'booking', $response);
        $status = Arr::get($booking, 'status', 'OPEN');

        return collect(Arr::get($booking, 'tickets', []))
            ->map(function ($ticket) use ($status): TicketArtifact {
                $ticket = (array) $ticket;
                /** @var array<string, mixed> $traveler */
                $traveler = Arr::get($ticket, 'leadTraveler', []);
                $name = trim(Arr::get($traveler, 'firstName', '') . ' ' . Arr::get($traveler, 'lastName', ''));

                return new TicketArtifact([
                    'provider' => 'seaworld',
                    'identifier' => Arr::get($ticket, 'barcode.value'),
                    'format' => TicketArtifact::FORMAT_CODE39,
                    'redemption' => TicketArtifact::REDEMPTION_SCAN,
                    'traveler_name' => $name !== '' ? $name : null,
                    'product_name' => Arr::get($ticket, 'productName', Arr::get($ticket, 'name')),
                    'valid_from' => Arr::get($ticket, 'start'),
                    'valid_to' => Arr::get($ticket, 'end'),
                    'status' => $status,
                ]);
            })
            ->values();
    }
}
