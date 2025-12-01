<?php

namespace CodeCreatives\LaravelRedeam\Commands;

use Illuminate\Console\Command;

class LaravelRedeamCommand extends Command
{
    public $signature = 'laravel-redeam';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
