<?php

namespace CodeCreatives\LaravelSmartOrder\Commands;

use Illuminate\Console\Command;

class LaravelSmartOrderCommand extends Command
{
    public $signature = 'laravel-smartorder';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
