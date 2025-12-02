<?php

declare(strict_types=1);

namespace iabduul7\ThemeParkBooking\Tests\Unit\Utils;

use iabduul7\ThemeParkBooking\Utils\TestHelper;
use iabduul7\ThemeParkBooking\Utils\ConfigManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for PHPStan validation utilities.
 */
class UtilsTest extends TestCase
{
    public function test_test_helper_basic_functionality(): void
    {
        $helper = new TestHelper('test', 42);

        $this->assertSame('test', $helper->getName());
        $this->assertSame(42, $helper->getValue());
        $this->assertSame(10, $helper->calculateSum(4, 6));
    }

    public function test_test_helper_data_processing(): void
    {
        $helper = new TestHelper('processor', 1);

        $input = [
            'name' => 'John',
            'age' => 30,
            'active' => true,
        ];

        $result = $helper->processData($input);

        $this->assertSame('John', $result['name']);
        $this->assertSame('30', $result['age']);
        $this->assertSame('1', $result['active']);
    }

    public function test_test_helper_config_handling(): void
    {
        $helper = new TestHelper('config', 1);

        $this->assertNull($helper->getConfig(null));
        $this->assertSame('config_test', $helper->getConfig('test'));
    }

    public function test_config_manager_basic_operations(): void
    {
        $manager = new ConfigManager();

        $this->assertSame('https://api.example.com', $manager->getApiUrl());
        $this->assertSame(30, $manager->getTimeout());
        $this->assertTrue($manager->isEnabled());

        $features = $manager->getFeatures();
        $this->assertContains('booking', $features);
        $this->assertContains('cancellation', $features);
    }

    public function test_config_manager_validation(): void
    {
        $manager = new ConfigManager();
        $manager->set('api_url', '');
        $manager->set('timeout', -1);

        $errors = $manager->validate();

        $this->assertArrayHasKey('api_url', $errors);
        $this->assertArrayHasKey('timeout', $errors);
        $this->assertSame('API URL is required', $errors['api_url']);
        $this->assertSame('Timeout must be positive', $errors['timeout']);
    }

    public function test_config_manager_custom_values(): void
    {
        $manager = new ConfigManager();
        $manager->set('custom_key', 'custom_value');

        $this->assertSame('custom_value', $manager->get('custom_key'));
        $this->assertSame('default', $manager->get('nonexistent', 'default'));
    }
}
