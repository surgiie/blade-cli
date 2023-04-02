<?php

namespace App\Commands;

use App\Support\BaseCommand;
use Illuminate\Filesystem\Filesystem;
use Surgiie\Console\Concerns\LoadsEnvFiles;
use Surgiie\Console\Concerns\LoadsJsonFiles;

class ClearCommand extends BaseCommand
{
    use LoadsJsonFiles, LoadsEnvFiles;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'clear {--compiled-path= : Custom directory for cached/compiled files. }';

    /**Allow arbitrary options to be passed to the command. */
    protected bool $arbitraryOptions = true;

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Clear the cached compiled files directory.';

    /**
     * Execute the console command and clear compiled directory.
     *
     * @return int
     */
    public function handle()
    {
        $task = $this->runTask('Clear compiled files directory', function () {
            $fs = new Filesystem;
            $fs->deleteDirectory($this->blade()->getCompiledPath());
        }, finishedText: 'Cleared compiled files directory');

        return $task->succeeded() ? 0 : 1;
    }
}
