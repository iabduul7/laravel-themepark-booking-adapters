<?php

namespace iabduul7\ThemeParkBooking\Tests;

trait SkipsTestsForMissingDependencies
{
    /**
     * Skip test if required classes don't exist
     */
    protected function skipIfClassMissing(string|array $classes, string $reason = null): void
    {
        $classes = is_array($classes) ? $classes : [$classes];
        
        foreach ($classes as $class) {
            if (!class_exists($class)) {
                $reason = $reason ?: "Class {$class} not implemented yet";
                $this->markTestSkipped($reason);
                return;
            }
        }
    }

    /**
     * Skip test if API configuration is missing
     */
    protected function skipIfApiConfigMissing($configPaths, string $reason = null): void
    {
        $configPaths = is_array($configPaths) ? $configPaths : [$configPaths];
        
        foreach ($configPaths as $configPath) {
            if (empty(config($configPath))) {
                $reason = $reason ?: "API configuration for {$configPath} not provided";
                $this->markTestSkipped($reason);
                return;
            }
        }
    }

    /**
     * Skip test if environment variables are missing
     */
    protected function skipIfEnvMissing(string|array $envVars, string $reason = null): void
    {
        $envVars = is_array($envVars) ? $envVars : [$envVars];
        
        foreach ($envVars as $envVar) {
            if (empty(env($envVar))) {
                $reason = $reason ?: "Environment variable {$envVar} not set";
                $this->markTestSkipped($reason);
                return;
            }
        }
    }

    /**
     * Skip test if class method doesn't exist
     */
    protected function skipIfMethodMissing(string $class, string $method, string $reason = null): void
    {
        if (!class_exists($class) || !method_exists($class, $method)) {
            $reason = $reason ?: "Method {$class}::{$method}() not implemented yet";
            $this->markTestSkipped($reason);
        }
    }

    /**
     * Skip test if trait is not used by class
     */
    protected function skipIfTraitMissing(string $class, string $trait, string $reason = null): void
    {
        if (!class_exists($class) || !in_array($trait, class_uses_recursive($class))) {
            $reason = $reason ?: "Trait {$trait} not used by {$class}";
            $this->markTestSkipped($reason);
        }
    }
}