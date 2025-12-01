<?php

namespace CodeCreatives\LaravelRedeam;

use CodeCreatives\LaravelRedeam\Result\PriceSchedule;
use CodeCreatives\LaravelRedeam\Result\Product;
use CodeCreatives\LaravelRedeam\Result\Rate;
use CodeCreatives\LaravelRedeam\Result\RatePriceSchedule;
use CodeCreatives\LaravelRedeam\Result\Supplier;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LaravelRedeamForUnitedParks
{
    protected RedeamApiClientForUnitedParks $client;

    protected string $supplier_ids;

    public function __construct()
    {
        $this->client = new RedeamApiClientForUnitedParks;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getAllSuppliers(array $parameters = [])
    {
        return $this->parseArrayData(
            Arr::get($this->client->get(
                'suppliers',
                $parameters
            ), 'suppliers', []),
            Supplier::class
        );
    }

    public function parseArrayData($data, $class)
    {
        return array_map(function ($item) use ($class) {
            return $this->parseData($item, $class);
        }, $data);
    }

    public function parseData($data, $class)
    {
        return new $class($data);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getSupplier(string $supplier_id, array $parameters = []): array
    {
        return $this->parseData(
            Arr::get($this->client->get(
                "suppliers/$supplier_id",
                $parameters
            ), 'supplier', []),
            Supplier::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getAllProducts(string $supplier_id, array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->client->get(
                "suppliers/$supplier_id/products",
                $parameters
            ), 'products', []),
            Product::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProduct(string $supplier_id, string $product_id, array $parameters = []): Product
    {
        return $this->parseData(
            Arr::get($this->client->get(
                "suppliers/$supplier_id/products/$product_id",
                $parameters
            ), 'product', []),
            Product::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductRates(string $supplier_id, string $product_id, array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->client->get(
                "suppliers/$supplier_id/products/$product_id/rates",
                $parameters
            ), 'rates', []),
            Rate::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductRate(
        string $supplier_id,
        string $product_id,
        string $rate_id,
        array $parameters = []
    ): Rate {
        return $this->parseData(
            Arr::get($this->client->get(
                "suppliers/$supplier_id/products/$product_id/rates/$rate_id",
                $parameters
            ), 'rate', []),
            Rate::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailability(
        string $supplier_id,
        string $product_id,
        Carbon|string $at,
        int $qty,
        array $parameters = []
    ): array {
        $at = $at instanceof Carbon
            ? $at->toISOString()
            : $at;

        return $this->client->get(
            "suppliers/$supplier_id/products/$product_id/availability",
            array_merge(
                $parameters,
                [
                    'at' => $at,
                    'qty' => $qty,
                ]
            )
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function checkAvailabilities(
        string $supplier_id,
        string $product_id,
        Carbon|string $start,
        Carbon|string $end,
        array $parameters = []
    ): array {
        $start = $start instanceof Carbon
            ? $start->toISOString()
            : $start;

        $end = $end instanceof Carbon
            ? $end->toISOString()
            : $end;

        return $this->client->get(
            "suppliers/$supplier_id/products/$product_id/availabilities",
            array_merge(
                $parameters,
                [
                    'start' => $start,
                    'end' => $end,
                ]
            )
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductAvailability(
        string $supplier_id,
        string $product_id,
        Carbon|string $at,
        int $qty,
        array $parameters = []
    ): array {
        $at = $at instanceof Carbon
            ? $at->toISOString()
            : $at;

        return $this->client->get(
            "suppliers/$supplier_id/products/$product_id/availability",
            array_merge(
                $parameters,
                [
                    'at' => $at,
                    'qty' => $qty,
                ]
            )
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function getProductPricingSchedule(
        string $supplier_id,
        string $product_id,
        Carbon|string $start_date,
        Carbon|string $end_date,
        array $parameters = []
    ): PriceSchedule {
        $start_date = $start_date instanceof Carbon
            ? $start_date->toDateString()
            : $start_date;

        $end_date = $end_date instanceof Carbon
            ? $end_date->toDateString()
            : $end_date;

        return $this->parseData(
            $this->client->get(
                "suppliers/$supplier_id/products/$product_id/pricing/schedule",
                array_merge(
                    $parameters,
                    [
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                    ]
                )
            ),
            PriceSchedule::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductRatePricingSchedule(
        string $supplier_id,
        string $product_id,
        Carbon|string $start_date,
        Carbon|string $end_date,
        ?string $rate_id = null,
        array $parameters = []
    ): RatePriceSchedule {
        $start_date = $start_date instanceof Carbon
            ? $start_date->toDateString()
            : $start_date;

        $end_date = $end_date instanceof Carbon
            ? $end_date->toDateString()
            : $end_date;

        return $this->parseData(
            Arr::get($this->client->get(
                "suppliers/$supplier_id/products/$product_id/pricing/schedule",
                array_merge(
                    $parameters,
                    [
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'rate_id' => $rate_id,
                    ]
                )
            ), $rate_id, []),
            RatePriceSchedule::class
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createNewHold(array $data): array
    {
        return $this->client->post('holds', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getHold(string $hold_id): array
    {
        return $this->client->get("holds/$hold_id"); // todo -> parseData with Hold::class
    }

    public function deleteHold(string $hold_id): array
    {
        return $this->client->delete("holds/$hold_id");
    }

    /**
     * @return array<string, mixed>
     */
    public function createNewBooking(array $data): array
    {
        return $this->client->post('bookings', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBooking(string $booking_id): array
    {
        return $this->client->get("bookings/$booking_id");
    }

    /**
     * @throws Exception
     */
    public function deleteBooking($booking_id)
    {
        return $this->client->put("bookings/cancel/$booking_id");
    }

    public function getOptionCode(int $days, ?string $name = null): ?string
    {
        $formatedName = Str::replace([
            "$days-Day ",
            "$days-Park ",
            "$days Day ",
            "$days-Day Disney's ",
        ], '', $name);

        $formatedName = Str::replaceLast(' Adult', '', $formatedName);
        $formatedName = Str::replaceLast(' Child', '', $formatedName);

        $optionCode = null;

        if (Str::endsWith($formatedName, [
            'Animal Kingdom Theme Park Ticket',
            'Hollywood Studios Theme Park Ticket',
            'EPCOT Theme Park Ticket',
            'Magic Kingdom Theme Park Ticket',
            'Admission to 1 Park Per Day',
        ]) ||
            Str::startsWith($formatedName, 'Walt Disney World') ||
            Str::contains($formatedName, ['Base Ticket', 'Magic Ticket', 'Discover'])) {
            $optionCode = 'base';
        } elseif (Str::contains($formatedName, [
            'Park Hopper Option',
            'w/Park Hopper',
        ])) {
            $optionCode = 'park-hopper';
        } elseif (Str::contains($formatedName, [
            'Park Hopper Plus Option',
            'w/Park Hopper Plus',
        ])) {
            $optionCode = 'park-hopper-plus';
        } elseif (Str::contains($formatedName, [
            'Water Park and Sports Option',
            'w/Water Park and Sports',
        ])) {
            $optionCode = 'water-park-and-sports';
        } elseif (Str::endsWith($formatedName, ['Water Park Ticket'])) {
            $optionCode = 'water-park-without-blockout-dates';
        } elseif (Str::endsWith($formatedName, ['Water Park Ticket with Blockout Dates'])) {
            $optionCode = 'water-park-with-blockout-dates';
        }

        if (Str::endsWith($formatedName, 'Discover Disney Ticket')) {
            $optionCode = "discover_$optionCode";
        }
        if (Str::startsWith($formatedName, ['FL Resident', 'FL Res.'])) {
            $optionCode = "fl_resident_$optionCode";
        }
        if (Str::startsWith($formatedName, ['Magic Your Way', 'Magic Ticket'])) {
            $optionCode = "magic_$optionCode";
        }

        return $optionCode;
    }

    public function getCommissionPercentage(int $days, ?string $optionCode = null): float
    {
        if (is_null($optionCode)) {
            return 0.0;
        }

        $commissions = $this->getCommissionPercentages($days);
        $floridaTicketCommissions = $this->getFloridaCommissionPercentages($days);

        $commission = 0.0;
        if (Str::startsWith($optionCode, 'fl_resident')) {
            $optionCode = Str::replace('fl_resident_', '', $optionCode);
            $commission = Arr::get($floridaTicketCommissions, "$optionCode-percentage", 0.00);
        } else {
            $commission = Arr::get($commissions, "$optionCode-percentage", 0.00);
        }

        return $commission;
    }

    private function getCommissionPercentages($days)
    {
        return config("walt_disney.commission.normal.$days");
    }

    private function getFloridaCommissionPercentages($days)
    {
        return config("walt_disney.commission.florida.$days");
    }
}
