<?php

namespace Iabduul7\ThemeParkAdapters\Providers\Disney;

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
 * Walt Disney World adapter (Redeam). Drop-in compatible with the upstream
 * CodeCreatives\LaravelRedeam\LaravelRedeamForWaltDisney facade: the supplier is
 * fixed from config and is implicit in every product/rate/availability/schedule call.
 */
class DisneyRedeamAdapter extends AbstractRedeamAdapter implements ProvidesTicketArtifacts
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (! $this->hasRequiredConfig(['api_key', 'api_secret', 'supplier_id'])) {
            throw ThemeParkApiException::invalidCredentials();
        }
    }

    public function getProviderName(): string
    {
        return 'disney';
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
    public function getAllProducts(array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->getRequest("suppliers/{$this->supplierId}/products", $parameters), 'products', []),
            Product::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProduct(string $productId, array $parameters = []): Product
    {
        try {
            $data = $this->getRequest("suppliers/{$this->supplierId}/products/{$productId}", $parameters);
        } catch (ThemeParkApiException $e) {
            throw $e->getCode() === 404 ? ThemeParkApiException::productNotFound($productId) : $e;
        }

        return $this->parseData(Arr::get($data, 'product', []), Product::class);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, Rate>
     */
    public function getProductRates(string $productId, array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->getRequest("suppliers/{$this->supplierId}/products/{$productId}/rates", $parameters), 'rates', []),
            Rate::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductRate(string $productId, string $rateId, array $parameters = []): Rate
    {
        return $this->parseData(
            Arr::get($this->getRequest("suppliers/{$this->supplierId}/products/{$productId}/rates/{$rateId}", $parameters), 'rate', []),
            Rate::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailability(string $productId, Carbon|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof Carbon ? $at->toISOString() : $at;

        return $this->getRequest(
            "suppliers/{$this->supplierId}/products/{$productId}/availability",
            array_merge($parameters, ['at' => $at, 'qty' => $qty])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailabilities(string $productId, Carbon|string $start, Carbon|string $end, array $parameters = []): array
    {
        $start = $start instanceof Carbon ? $start->toISOString() : $start;
        $end = $end instanceof Carbon ? $end->toISOString() : $end;

        return $this->getRequest(
            "suppliers/{$this->supplierId}/products/{$productId}/availabilities",
            array_merge($parameters, ['start' => $start, 'end' => $end])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductAvailability(string $productId, Carbon|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof Carbon ? $at->toISOString() : $at;

        return $this->getRequest(
            "suppliers/{$this->supplierId}/products/{$productId}/availability",
            array_merge($parameters, ['at' => $at, 'qty' => $qty])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductPricingSchedule(
        string $productId,
        Carbon|string $startDate,
        Carbon|string $endDate,
        array $parameters = []
    ): PriceSchedule {
        $startDate = $startDate instanceof Carbon ? $startDate->toDateString() : $startDate;
        $endDate = $endDate instanceof Carbon ? $endDate->toDateString() : $endDate;

        return $this->parseData(
            $this->getRequest(
                "suppliers/{$this->supplierId}/products/{$productId}/pricing/schedule",
                array_merge($parameters, ['start_date' => $startDate, 'end_date' => $endDate])
            ),
            PriceSchedule::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductRatePricingSchedule(
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
                "suppliers/{$this->supplierId}/products/{$productId}/pricing/schedule",
                array_merge($parameters, ['start_date' => $startDate, 'end_date' => $endDate, 'rate_id' => $rateId])
            ), $rateId ?? '', []),
            RatePriceSchedule::class
        );
    }

    public function validateCredentials(): bool
    {
        try {
            $this->getAllProducts();

            return true;
        } catch (ThemeParkApiException) {
            return false;
        }
    }

    /**
     * Disney park-level availability from the public observability endpoint.
     *
     * @return array<string, mixed>
     */
    public function getParkAvailability(Carbon|string $startDate, Carbon|string $endDate): array
    {
        $startDate = $startDate instanceof Carbon ? $startDate->format('Y-m-d') : $startDate;
        $endDate = $endDate instanceof Carbon ? $endDate->format('Y-m-d') : $endDate;

        $url = (string) $this->getConfig('park_availability_url', 'https://dis-obs.redeam.io/disney/park/availability');

        $response = $this->decodeOrThrow(
            $this->retryReads($this->http()->asForm())->get($url, [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])
        );

        return collect($response)
            ->transform(function ($value, $key) {
                $value = (array) $value;
                $status = 'full';
                $count = 0;
                foreach (($value['parks'] ?? []) as $availability) {
                    if ($availability === 'notAvailable') {
                        $status = 'partial';
                        $count++;
                    }
                }

                $parks = (string) json_encode($value['parks'] ?? []);
                $parks = str_replace('®', '', $parks);
                $decoded = json_decode($parks, true);
                $parks = is_array($decoded) ? array_reverse($decoded) : [];

                return [
                    'availability' => $count === 4 ? 'none' : $status,
                    'date' => $key,
                    'nice_date' => Carbon::parse($key)->format('Y M, d') . '<strong>' . Carbon::parse($key)->format('D') . '</strong>',
                    'parks' => $parks,
                ];
            })
            ->toArray();
    }

    /**
     * Walt Disney World is a will-call product: the booking returns a single
     * supplier reference (booking.ext["supplier.reference"]) that the guest presents
     * at the ticket window — there is no per-traveler scannable barcode. One artifact
     * per booking. ("supplier.reference" is a literal key containing a dot, so it is
     * read from the ext array directly, not via a nested dot-path.).
     *
     * @param  array<string, mixed>  $response
     * @return Collection<int, TicketArtifact>
     */
    public function tickets(array $response): Collection
    {
        /** @var array<string, mixed> $booking */
        $booking = Arr::get($response, 'booking', $response);
        /** @var array<string, mixed> $ext */
        $ext = Arr::get($booking, 'ext', []);
        $reference = Arr::get($ext, 'supplier.reference');

        if ($reference === null) {
            return collect();
        }

        /** @var array<string, mixed> $customer */
        $customer = Arr::get($booking, 'customer', []);
        $name = trim(Arr::get($customer, 'firstName', '') . ' ' . Arr::get($customer, 'lastName', ''));

        return collect([new TicketArtifact([
            'provider' => 'disney',
            'identifier' => $reference,
            'format' => TicketArtifact::FORMAT_CODE39,
            'redemption' => TicketArtifact::REDEMPTION_WILL_CALL,
            'traveler_name' => $name !== '' ? $name : null,
            'product_name' => Arr::get($booking, 'items.0.rate.name'),
            'valid_from' => Arr::get($ext, 'disney-ticketStartDate'),
            'valid_to' => Arr::get($ext, 'disney-ticketEndDate'),
            'status' => Arr::get($booking, 'status', 'OPEN'),
        ])]);
    }
}
