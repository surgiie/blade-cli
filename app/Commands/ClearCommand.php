<?php

namespace App\Commands;

use App\Support\BaseCommand;
use Surgiie\Blade\Blade;
use Surgiie\Console\Concerns\LoadsEnvFiles;
use Surgiie\Console\Concerns\LoadsJsonFiles;

class ClearCommand extends BaseCommand
{
    use LoadsEnvFiles, LoadsJsonFiles;

    protected $signature = 'clear {--cache-path= : Custom directory for cached/compiled files. }';

    protected bool $arbitraryOptions = true;

    protected $description = 'Clear the cached compiled files directory.';

    public function handle()
    {
        Blade::setCachePath($this->bladeCachePath());

        $succeeded = Blade::deleteCacheDirectory();

        $this->components->info('Cleared compiled files directory');

        return $succeeded === false ? 1 : 0;
    }
}
