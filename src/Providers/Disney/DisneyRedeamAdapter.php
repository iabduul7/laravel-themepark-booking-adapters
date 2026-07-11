<?php

namespace Iabduul7\ThemeParkAdapters\Providers\Disney;

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
     * Entry point for {@see Product::getRates()} — takes the Product DTO directly
     * instead of requiring the caller to re-supply its id.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<int, Rate>
     */
    public function ratesFor(Product $product, array $parameters = []): array
    {
        return $this->getProductRates($product->getId(), $parameters);
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
    public function checkAvailability(string $productId, DateTimeInterface|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof DateTimeInterface ? Carbon::instance($at)->toISOString() : $at;

        return $this->getRequest(
            "suppliers/{$this->supplierId}/products/{$productId}/availability",
            array_merge($parameters, ['at' => $at, 'qty' => $qty])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailabilities(string $productId, DateTimeInterface|string $start, DateTimeInterface|string $end, array $parameters = []): array
    {
        $start = $start instanceof DateTimeInterface ? Carbon::instance($start)->toISOString() : $start;
        $end = $end instanceof DateTimeInterface ? Carbon::instance($end)->toISOString() : $end;

        return $this->getRequest(
            "suppliers/{$this->supplierId}/products/{$productId}/availabilities",
            array_merge($parameters, ['start' => $start, 'end' => $end])
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductAvailability(string $productId, DateTimeInterface|string $at, int $qty, array $parameters = []): array
    {
        $at = $at instanceof DateTimeInterface ? Carbon::instance($at)->toISOString() : $at;

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
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        array $parameters = []
    ): PriceSchedule {
        $startDate = $startDate instanceof DateTimeInterface ? Carbon::instance($startDate)->toDateString() : $startDate;
        $endDate = $endDate instanceof DateTimeInterface ? Carbon::instance($endDate)->toDateString() : $endDate;

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
                "suppliers/{$this->supplierId}/products/{$productId}/pricing/schedule",
                array_merge($parameters, ['start_date' => $startDate, 'end_date' => $endDate, 'rate_id' => $rateId])
            ), $rateId, []),
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
    public function getParkAvailability(DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): array
    {
        $startDate = $startDate instanceof DateTimeInterface ? Carbon::instance($startDate)->format('Y-m-d') : $startDate;
        $endDate = $endDate instanceof DateTimeInterface ? Carbon::instance($endDate)->format('Y-m-d') : $endDate;

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

                // The feed keys each entry by park name ("Magic Kingdom® Park" => "available"),
                // so the ® mark has to be stripped from the keys, not the values.
                $parks = [];
                foreach ((is_array($value['parks'] ?? null) ? $value['parks'] : []) as $name => $availability) {
                    $name = is_string($name) ? str_replace('®', '', $name) : $name;
                    $parks[$name] = is_string($availability) ? str_replace('®', '', $availability) : $availability;
                }
                $parks = array_reverse($parks);

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
     * @param  array<string, mixed>|null  $response
     * @return Collection<int, TicketArtifact>
     */
    public function tickets(?array $response): Collection
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
