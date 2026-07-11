<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\Product;
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use Illuminate\Support\Facades\Http;

class ResultSerializationTest extends AdapterTestCase
{
    private function adapter(): DisneyRedeamAdapter
    {
        return new DisneyRedeamAdapter([
            'api_key' => 'key-super-secret',
            'api_secret' => 'secret-super-secret',
            'supplier_id' => 'sup-1',
        ]);
    }

    public function test_serialized_result_excludes_adapter_and_credentials(): void
    {
        $product = (new Product(['id' => 'p1', 'name' => 'Ticket']))->withAdapter($this->adapter());

        $serialized = serialize($product);

        $this->assertFalse(str_contains($serialized, 'key-super-secret'));
        $this->assertFalse(str_contains($serialized, 'secret-super-secret'));

        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Product::class, $unserialized);
        $this->assertSame('p1', $unserialized->getId());
        $this->assertSame('Ticket', $unserialized->getName());
    }

    public function test_unserialized_product_get_rates_degrades_to_empty_array(): void
    {
        Http::fake();

        $product = (new Product(['id' => 'p1', 'name' => 'Ticket']))->withAdapter($this->adapter());

        $unserialized = unserialize(serialize($product));

        $this->assertSame([], $unserialized->getRates());

        Http::assertNothingSent();
    }
}
