<?php

namespace iabduul7\ThemeParkBooking\Commands;

use iabduul7\ThemeParkBooking\Services\BookingManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProductsCommand extends Command
{
    protected $signature = 'themepark:sync-products 
                           {adapter? : The adapter name to sync (optional, syncs all if not specified)}
                           {--force : Force sync even if recent sync exists}
                           {--dry-run : Run without making actual changes}';

    protected $description = 'Sync products from theme park booking providers';

    protected BookingManager $bookingManager;

    public function __construct(BookingManager $bookingManager)
    {
        parent::__construct();
        $this->bookingManager = $bookingManager;
    }

    public function handle(): int
    {
        $adapter = $this->argument('adapter');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🧪 Running in dry-run mode - no actual changes will be made');
        }

        $this->info('🚀 Starting product sync...');

        if ($adapter) {
            return $this->syncSingleAdapter($adapter, $force, $dryRun);
        }

        return $this->syncAllAdapters($force, $dryRun);
    }

    protected function syncSingleAdapter(string $adapter, bool $force, bool $dryRun): int
    {
        try {
            $this->info("📡 Syncing products for adapter: {$adapter}");

            if (! $this->shouldSync($adapter, $force)) {
                $this->warn("⏭️  Skipping {$adapter} - recent sync exists (use --force to override)");

                return self::SUCCESS;
            }

            if ($dryRun) {
                $this->info("✅ Would sync products for {$adapter}");

                return self::SUCCESS;
            }

            $result = $this->bookingManager->syncProducts($adapter);

            if ($result->success) {
                $this->info("✅ {$adapter}: {$result->getSummary()}");

                if ($result->hasWarnings()) {
                    $this->warn("⚠️  Warnings:");
                    foreach ($result->warnings as $warning) {
                        $this->warn("   • {$warning}");
                    }
                }

                return self::SUCCESS;
            }

            $this->error("❌ {$adapter}: Sync failed");
            foreach ($result->errors as $error) {
                $this->error("   • {$error}");
            }

            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error("❌ Failed to sync {$adapter}: {$e->getMessage()}");
            Log::error("Product sync command failed", [
                'adapter' => $adapter,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    protected function syncAllAdapters(bool $force, bool $dryRun): int
    {
        $adapters = $this->bookingManager->getAvailableAdapters();

        if (empty($adapters)) {
            $this->warn('⚠️  No adapters available for sync');

            return self::SUCCESS;
        }

        $this->info("📡 Syncing products for " . count($adapters) . " adapters");

        $results = [];
        $progressBar = $this->output->createProgressBar(count($adapters));
        $progressBar->start();

        foreach ($adapters as $adapter) {
            if ($dryRun) {
                $this->line(" • Would sync {$adapter}");
                $results[$adapter] = true;
                $progressBar->advance();

                continue;
            }

            try {
                if (! $this->shouldSync($adapter, $force)) {
                    $this->line(" • Skipping {$adapter} (recent sync)");
                    $results[$adapter] = true;
                    $progressBar->advance();

                    continue;
                }

                $result = $this->bookingManager->syncProducts($adapter);
                $results[$adapter] = $result->success;

                if ($result->success) {
                    $this->line(" • {$adapter}: {$result->getSummary()}");
                } else {
                    $this->line(" • {$adapter}: Failed - " . implode(', ', $result->errors));
                }

            } catch (\Exception $e) {
                $results[$adapter] = false;
                $this->line(" • {$adapter}: Exception - {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $successful = array_filter($results);
        $failed = array_filter($results, fn ($success) => ! $success);

        $this->info("📊 Sync Summary:");
        $this->info("   ✅ Successful: " . count($successful));

        if (! empty($failed)) {
            $this->error("   ❌ Failed: " . count($failed));
            foreach (array_keys($failed) as $failedAdapter) {
                $this->error("      • {$failedAdapter}");
            }
        }

        return empty($failed) ? self::SUCCESS : self::FAILURE;
    }

    protected function shouldSync(string $adapter, bool $force): bool
    {
        if ($force) {
            return true;
        }

        try {
            $adapterInstance = $this->bookingManager->getAdapter($adapter);
            $lastSync = $adapterInstance->getLastSyncTimestamp();

            if (! $lastSync) {
                return true; // Never synced before
            }

            // Don't sync if last sync was within 1 hour
            $hourAgo = time() - 3600;

            return $lastSync < $hourAgo;

        } catch (\Exception $e) {
            return true; // Sync if we can't determine last sync time
        }
    }
}
