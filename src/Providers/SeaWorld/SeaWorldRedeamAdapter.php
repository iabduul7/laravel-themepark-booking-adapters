<?php

namespace Iabduul7\ThemeParkAdapters\Providers\SeaWorld;

use DateTimeInterface;
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
        try {
            $data = $this->getRequest("suppliers/{$supplierId}/products/{$productId}", $parameters);
        } catch (ThemeParkApiException $e) {
            throw $e->getCode() === 404 ? ThemeParkApiException::productNotFound($productId) : $e;
        }

        return $this->parseData(Arr::get($data, 'product', []), Product::class);
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
     * Entry point for {@see Product::getRates()}. United Parks products carry their
     * own supplierId, so a product-only call can still resolve the correct
     * supplier without the caller re-supplying it.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<int, Rate>
     */
    public function ratesFor(Product $product, array $parameters = []): array
    {
        $supplierId = $product->getProductSupplierId() ?? $this->supplierId;

        if ($supplierId === null) {
            throw ThemeParkApiException::apiError('Cannot resolve the supplier for product rates: the product payload has no supplierId and no supplier id is set on the adapter.');
        }

        return $this->getProductRates($supplierId, $product->getId(), $parameters);
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
    public function checkAvailability(string $supplierId, string $productId, DateTimeInterface|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof DateTimeInterface ? Carbon::instance($at)->toISOString() : $at;

        return $this->getRequest(
            "suppliers/{$supplierId}/products/{$productId}/availability",
            array_merge($parameters, ['at' => $at, 'qty' => $qty])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailabilities(string $supplierId, string $productId, DateTimeInterface|string $start, DateTimeInterface|string $end, array $parameters = []): array
    {
        $start = $start instanceof DateTimeInterface ? Carbon::instance($start)->toISOString() : $start;
        $end = $end instanceof DateTimeInterface ? Carbon::instance($end)->toISOString() : $end;

        return $this->getRequest(
            "suppliers/{$supplierId}/products/{$productId}/availabilities",
            array_merge($parameters, ['start' => $start, 'end' => $end])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductAvailability(string $supplierId, string $productId, DateTimeInterface|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof DateTimeInterface ? Carbon::instance($at)->toISOString() : $at;

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
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        array $parameters = []
    ): PriceSchedule {
        $startDate = $startDate instanceof DateTimeInterface ? Carbon::instance($startDate)->toDateString() : $startDate;
        $endDate = $endDate instanceof DateTimeInterface ? Carbon::instance($endDate)->toDateString() : $endDate;

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
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        ?string $rateId = null,
        array $parameters = []
    ): RatePriceSchedule {
        $startDate = $startDate instanceof DateTimeInterface ? Carbon::instance($startDate)->toDateString() : $startDate;
        $endDate = $endDate instanceof DateTimeInterface ? Carbon::instance($endDate)->toDateString() : $endDate;

        return $this->parseData(
            // A null $rateId is intentional: Arr::get() with a null key returns the
            // whole array, i.e. the full multi-rate schedule.
            Arr::get($this->getRequest(
                "suppliers/{$supplierId}/products/{$productId}/pricing/schedule",
                array_merge($parameters, ['start_date' => $startDate, 'end_date' => $endDate, 'rate_id' => $rateId])
            ), $rateId, []),
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
     * @param  array<string, mixed>|null  $response
     * @return Collection<int, TicketArtifact>
     */
    public function tickets(?array $response): Collection
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
