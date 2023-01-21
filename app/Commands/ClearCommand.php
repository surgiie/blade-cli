<?php

namespace App\Commands;

use Illuminate\Filesystem\Filesystem;
use Surgiie\Console\Command as ConsoleCommand;
use Surgiie\Console\Concerns\LoadsEnvFiles;
use Surgiie\Console\Concerns\LoadsJsonFiles;
use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class ClearCommand extends ConsoleCommand
{
    use WithValidation, WithTransformers, LoadsJsonFiles, LoadsEnvFiles;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'clear';

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
        $task = $this->runTask('Clear compiled files directory', function ($task) {
            $dir = config('app.compiled_path');
            $fs = new Filesystem;
            $fs->deleteDirectory($dir, preserve: true);
        }, finishedText: 'Cleared compiled files directory');

        $task->succeeded() ? 0 : 1;
    }
}
