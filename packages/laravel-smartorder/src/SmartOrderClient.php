<?php

namespace CodeCreatives\LaravelSmartOrder;

class SmartOrderClient
{
    protected SmartOrderApiClient $client;

    public function __construct()
    {
        $this->client = new SmartOrderApiClient();
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function getAllProducts(array $parameters = []): array
    {
        return $this->client->get(
            'smartorder/MyProductCatalog',
            $parameters
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getAvailableMonths(): array
    {
        return collect(range(0, 11))
            ->map(function ($i) {
                $month = now()->startOfMonth()->addMonths($i);

                return [
                    'class' => $month->format('Y-m'),   // "2022-07",
                    'text' => $month->format('F, Y'),   // "July 2022",
                    'value' => $month->format('Y-m-d'), // "2022-07-01",
                ];
            })
            ->toArray();
    }

    public function findEvents(array $parameters): ?array
    {
        return $this->client->post(
            'smartorder/FindEvents',
            $parameters
        );
    }

    public function placeOrder(array $parameters): ?array
    {
        return $this->client->post(
            'smartorder/PlaceOrder',
            $parameters
        );
    }

    public function getExistingOrder(array $parameters): ?array
    {
        return $this->client->get(
            'smartorder/GetExistingOrderId',
            $parameters
        );
    }

    public function canCancelOrder(array $parameters): ?array
    {
        return $this->client->get(
            'smartorder/CanCancelOrder',
            $parameters
        );
    }

    public function cancelOrder(array $parameters): ?array
    {
        return $this->client->get(
            'smartorder/CancelOrder',
            $parameters
        );
    }
}
