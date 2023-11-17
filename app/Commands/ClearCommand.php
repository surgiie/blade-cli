<?php

namespace App\Commands;

use App\Support\BaseCommand;
use Surgiie\Blade\Blade;
use Surgiie\Console\Concerns\LoadsEnvFiles;
use Surgiie\Console\Concerns\LoadsJsonFiles;

class ClearCommand extends BaseCommand
{
    use LoadsEnvFiles, LoadsJsonFiles;

    /**
     * The command's signature text.
     *
     * @var string
     */
    protected $signature = 'clear {--cache-path= : Custom directory for cached/compiled files. }';

    /**
     * Allow the command to accept arbitrary options.
     */
    protected bool $arbitraryOptions = true;

    /**
     * The command's description text.
     *
     * @var string
     */
    protected $description = 'Clear the cached compiled files directory.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle()
    {
        Blade::setCachePath($this->bladeCachePath());

        $succeeded = Blade::deleteCacheDirectory();

        $this->components->info('Cleared compiled files directory');

        return $succeeded === false ? 1 : 0;
    }
}
