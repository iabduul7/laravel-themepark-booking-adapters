<?php

namespace CodeCreatives\LaravelSmartOrder;

use App\Enums\ProductProvidersEnum;
use App\Models\Identifier;
use App\Models\Ticket;
use Arr;
use Illuminate\Support\Str;

class LaravelSmartOrder
{
    protected SmartOrderClient $client;

    protected int $cacheTTL = 18000; // seconds

    public function __construct()
    {
        $this->client = new SmartOrderClient();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllCalendarProductsWithPrices($code): array
    {
        return collect($this->getAllProducts())
            ->filter(fn ($product) => Str::startsWith($product['plu'], $code))
            ->toArray();
    }

    public function getAllProducts()
    {
        $products = collect(Arr::get($this->getAllCalendarProducts(), 0, []))
            ->filter(fn ($obj) => Arr::get($obj, 'salesProgramId', 0) == 4638)
            ->values()
            ->toArray();

        return Arr::get($products, 0, []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllCalendarProducts(): array
    {
        $start_date = today()->startOfMonth()->format('Y-m-d');
        $end_date = today()
            ->startOfMonth()
            ->addYear()
            ->format('Y-m-d');

        $products = $this->client->getAllProducts([
            'startDateInclusive' => $start_date,
            'endDateInclusive' => $end_date,
            //            'retrieveOnly' => 'future',
            //            'retrieveOnly' => 'current',
        ]);

        return collect($products)
            ->values()
            ->toArray();
    }

    public function insertIdentifiers()
    {
        $productsData = Arr::get($this->getAllProducts(), 'productCatalogEntries', []);
        //        $promoProductsData = Arr::get($this->getAllPromoProducts(), 'productCatalogEntries', []);
        $promoProductsData = Arr::get($this->getNewPromoProducts(), 'productCatalogEntries', []);

        $products = collect(array_merge($productsData, $promoProductsData));

        $pluIds = $products->map(function ($item) {
            return $item['plu'];
        });

        $identifiers = Identifier::whereNotIn('code', $pluIds->toArray())
            ->where('type', ProductProvidersEnum::SMARTORDER2)
            ->where('status', 1)
            ->get();

        if (count($identifiers) > 0) {
            $unusedIdentifiers = $identifiers->pluck('code')->toArray();

            Ticket::where('api_provider', ProductProvidersEnum::SMARTORDER2)
                ->whereIn('api_identifier', $unusedIdentifiers)
                ->update(['api_identifier' => null, 'is_available' => 0, 'is_visible' => 0]);

            Identifier::whereNotIn('code', $pluIds->toArray())
                ->where('type', ProductProvidersEnum::SMARTORDER2)
                ->update(['status' => 0]);
        }

        foreach ($products as $product) {
            Identifier::updateOrCreate(['code' => $product['plu']], [
                'name' => $product['productName'],
                'code' => $product['plu'],
                'type' => ProductProvidersEnum::SMARTORDER2,
                'status' => 1,
            ]);

            Ticket::where('api_provider', ProductProvidersEnum::SMARTORDER2)
                ->where('api_name', $product['productName'])
                ->update(['api_identifier' => $product['plu'], 'is_available' => 1, 'is_visible' => 1]);
        }

        return true;
    }

    /*public function getAllPromoProducts()
    {
        $products = collect(Arr::get($this->getAllCalendarProducts(), 0, []))
            ->filter(function ($obj) {
                $productEntries = $obj['productCatalogEntries'];

                return filled($productEntries) &&
                    Str::startsWith(collect($obj['productCatalogEntries'])->first()['plu'], '1803');
            })
            ->values()
            ->toArray();

        return Arr::get($products, 0, []);
    }*/

    public function getNewPromoProducts()
    {
        $products = collect(Arr::get($this->getAllCalendarProducts(), 0, []))
            ->filter(fn ($obj) => Str::startsWith(collect($obj['productCatalogEntries'])->first()['plu'], '1803'))
            ->values()
            ->toArray();

        return Arr::get($products, 0, []);
    }

    public function getAllHHNProducts()
    {
        $products = collect(Arr::get($this->getAllCalendarProducts(), 0, []))
            ->filter(fn ($obj) => $obj['salesProgramId'] == 3552)
            ->values()
            ->toArray();

        return Arr::get($products, 0, []);
    }
}
