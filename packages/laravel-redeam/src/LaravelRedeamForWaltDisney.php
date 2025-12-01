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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LaravelRedeamForWaltDisney
{
    protected RedeamApiClientForDisney $client;

    protected string $supplier_id;

    public function __construct()
    {
        $this->client = new RedeamApiClientForDisney();
        $this->supplier_id = config('redeam.disney.supplier_id');
    }

    public function setSupplierId(string $supplier_id): void
    {
        $this->supplier_id = $supplier_id;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getAllSuppliers(array $parameters = []): array
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
    public function getAllProducts(array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->client->get(
                "suppliers/$this->supplier_id/products",
                $parameters
            ), 'products', []),
            Product::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProduct(string $product_id, array $parameters = []): Product
    {
        return $this->parseData(
            Arr::get($this->client->get(
                "suppliers/$this->supplier_id/products/$product_id",
                $parameters
            ), 'product', []),
            Product::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductRates(string $product_id, array $parameters = []): array
    {
        return $this->parseArrayData(
            Arr::get($this->client->get(
                "suppliers/$this->supplier_id/products/$product_id/rates",
                $parameters
            ), 'rates', []),
            Rate::class
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getProductRate(string $product_id, string $rate_id, array $parameters = []): Rate
    {
        return $this->parseData(
            Arr::get($this->client->get(
                "suppliers/$this->supplier_id/products/$product_id/rates/$rate_id",
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
        string $product_id,
        Carbon|string $at,
        int $qty,
        array $parameters = []
    ): array {
        $at = $at instanceof Carbon
            ? $at->toISOString()
            : $at;

        return $this->client->get(
            "suppliers/$this->supplier_id/products/$product_id/availability",
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
            "suppliers/$this->supplier_id/products/$product_id/availabilities",
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
        string $product_id,
        Carbon|string $at,
        int $qty,
        array $parameters = []
    ): array {
        $at = $at instanceof Carbon
            ? $at->toISOString()
            : $at;

        return $this->client->get(
            "suppliers/$this->supplier_id/products/$product_id/availability",
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
                "suppliers/$this->supplier_id/products/$product_id/pricing/schedule",
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
                "suppliers/$this->supplier_id/products/$product_id/pricing/schedule",
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
        if (Str::startsWith($formatedName, ['Florida Resident', 'FL Resident', 'FL Res.'])) {
            $optionCode = "fl_resident_$optionCode";
        }
        if (Str::startsWith($formatedName, ['Magic Your Way', 'Magic Ticket'])) {
            $optionCode = "magic_$optionCode";
        }

        return $optionCode;

        /*$unitIdWithoutDays = Str::substr($unitId, 1);

        // Special Event Cases for 1 and 2 day
        if ($days <= 2) {
            return match ($unitIdWithoutDays) {
                "JORA", "JORB", "JORC", "JORD",
                "0ORA", "0ORB", "0ORC", "0ORD",
                "1ORA", "1ORB", "1ORC", "1ORD",
                "2ORA", "2ORB", "2ORC", "2ORD",
                "3ORA", "3ORB", "3ORC", "3ORD" => 'base',

                "LORA", "LORB", "LORC", "LORD" => 'water-sport',
                "KORA", "KORB", "KORC", "KORD",
                "KOVA", "KOVB" => 'park-hopper',

                "MORA", "MORB", "MORC", "MORD",
                "MOVA", "MOVB" => 'park-hopper-plus',

                "KOA3", "KOA4", "RKOA7", "RKOA8" => 'water-park-with-blockout-dates',
                "KOA1", "KOA2", "RKOA5", "RKOA6" => 'water-park-without-blockout-dates',

                'KOAM', 'KOAN', 'KOAO', 'KOAP' => 'fl_resident_water-park-without-blockout-dates',

                "JWL3", "JWL4" => 'fl_resident_base',
                "LWL3", "LWL4" => 'fl_resident_water-sport',
                "KWL3", "KWL4" => 'fl_resident_park-hopper',
                "MWL3", "MWL4" => 'fl_resident_park-hopper-plus',

                "JZRP", "JZRO" => 'magic_base',
                "LZRP", "LZRO" => 'magic_water-sport',
                "KZRP", "KZRO" => 'magic_park-hopper',
                "MZRP", "MZRO" => 'magic_park-hopper-plus',

                default => null,
            };
        }

        return match ($unitIdWithoutDays) {
            "JORA", "JORB", 'AWB6', 'AWB7' => 'base',
            "LORA", "LORB", 'WWA1', 'WWA2' => 'water-sport',
            "KORA", "KORB" => 'park-hopper',
            "MORA", "MORB" => 'park-hopper-plus',

            "JORC", "JORD", "JWJG", "JWJH",
            "JWL3", "JWL4" => 'fl_resident_base',

            "LORC", "LORD", "LWJG", "LWJH",
            "LWL3", "LWL4" => 'fl_resident_water-sport',

            "KORC", "KORD", "KWJG", "KWJH",
            "KWL3", "KWL4" => 'fl_resident_park-hopper',

            "MORC", "MORD", "MWJG", "MWJH",
            "MJWH", "MWL3", "MWL4" => 'fl_resident_park-hopper-plus',

            "JZRP", "JZRO" => 'magic_base',
            "LZRP", "LZRO" => 'magic_water-sport',
            "KZRP", "KZRO" => 'magic_park-hopper',
            "MZRP", "MZRO" => 'magic_park-hopper-plus',

            "JWG3", "JWG4" => 'fl_resident_explorer_base',
            "LWG3", "LWG4" => 'fl_resident_explorer_water-sport',
            "KWG3", "KWG4" => 'fl_resident_explorer_park-hopper',
            "MWG3", "MWG4" => 'fl_resident_explorer_park-hopper-plus',

            "JWAD", "JWAC" => 'fl_resident_discovery_base',
            "LWAD", "LWAC" => 'fl_resident_discovery_water-sport',
            "KWAD", "KWAC" => 'fl_resident_discovery_park-hopper',
            "MWAD", "MWAC" => 'fl_resident_discovery_park-hopper-plus',

            default => null,
        };*/
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
        /*$unitIdWithoutDays = Str::substr($unitId, 1);
        // Special Cases for 1 and 2 day
        if ($days <= 2) {
            return (float)match ($unitIdWithoutDays) {
                "JORA", "JORB", "JORC", "JORD",
                "0ORA", "0ORB", "0ORC", "0ORD",
                "1ORA", "1ORB", "1ORC", "1ORD",
                "2ORA", "2ORB", "2ORC", "2ORD",
                "3ORA", "3ORB", "3ORC", "3ORD" => Arr::get($commission, 'base_ticket_percentage', 0.00),

                "LORA", "LORB", "LORC", "LORD" => Arr::get(
                    $commission,
                    'water_park_and_sports_option_percentage',
                    0.00
                ),
                "KORA", "KORB", "KORC", "KORD" => Arr::get($commission, 'park_hopper_option_percentage', 0.00),
                "MORA", "MORB", "MORC", "MORD" => Arr::get($commission, 'park_hopper_plus_option_percentage', 0.00),

                default => 0.00,
            };
        }
        return (float)match ($unitIdWithoutDays) {
            "JORA", "JORB" => Arr::get($commission, 'base_ticket_percentage', 0.00),
            "LORA", "LORB" => Arr::get($commission, 'water_park_and_sports_option_percentage', 0.00),
            "KORA", "KORB" => Arr::get($commission, 'park_hopper_option_percentage', 0.00),
            "MORA", "MORB" => Arr::get($commission, 'park_hopper_plus_option_percentage', 0.00),

            "JORC", "JORD", "JWJG", "JWJH" => Arr::get($floridaCommissionPercentage, 'base_ticket_percentage', 0.00),
            "LORC", "LORD", "LWJG", "LWJH" => Arr::get(
                $floridaCommissionPercentage,
                'water_park_and_sports_option_percentage',
                0.00
            ),
            "KORC", "KORD", "KWJG", "KWJH" => Arr::get(
                $floridaCommissionPercentage,
                'park_hopper_option_percentage',
                0.00
            ),
            "MORC", "MORD", "MWJG", "MWJH" => Arr::get(
                $floridaCommissionPercentage,
                'park_hopper_plus_option_percentage',
                0.00
            ),

            default => 0.00, //todo: define default
        };*/
    }

    private function getCommissionPercentages($days)
    {
        return config("walt_disney.commission.normal.$days");
    }

    private function getFloridaCommissionPercentages($days)
    {
        return config("walt_disney.commission.florida.$days");
    }

    public function getParkAvailability(string|Carbon $start_date, string|Carbon $end_date): array
    {
        $start_date = $start_date instanceof Carbon
            ? $start_date->format('Y-m-d')
            : $start_date;

        $end_date = $end_date instanceof Carbon
            ? $end_date->format('Y-m-d')
            : $end_date;

        $url = 'https://dis-obs.redeam.io/disney/park/availability';

        $response = Http::asForm()
            ->timeout('100')
            ->get($url, [
                'startDate' => $start_date,
                'endDate' => $end_date,
            ])
            ->json();

        return collect($response)
            ->transform(function ($value, $key) {
                $status = 'full';
                $count = 0;
                foreach ($value['parks'] as $availability) {
                    if ($availability == 'notAvailable') {
                        $status = 'partial';
                        $count++;
                    }
                }

                $parks = json_encode($value['parks']);
                $parks = str_replace('\u00ae', '', $parks);
                $parks = json_decode($parks, true);
                $parks = array_reverse($parks);

                return [
                    'availability' => $count == 4 ? 'none' : $status,
                    'date' => $key,
                    'nice_date' => \Carbon\Carbon::parse($key)
                        ->format('Y M, d') . '<strong>' . Carbon::parse($key)
                        ->format('D') . '</strong>',
                    'parks' => $parks,
                ];
            })
            ->toArray();
    }
}
