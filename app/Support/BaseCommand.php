<?php

namespace App\Support;

use Surgiie\Blade\Blade;
use Surgiie\Console\Command;

abstract class BaseCommand extends Command
{
    /**
     * Return the Blade instance for rendering files with.
     */
    protected function blade(): Blade
    {
        $blade = parent::blade();

        $env = getenv('BLADE_CLI_COMPILED_PATH');

        if ($env) {
            $blade->setCompiledPath($env);
        }

        // support old compile flag for now, will remove in future release
        if ($compiledPath = $this->arbitraryData->get('compile-path')) {
            $blade->setCompiledPath($compiledPath);
        }

        //prioritize new
        if ($compiledPath = $this->data->get('compiled-path')) {
            $blade->setCompiledPath($compiledPath);
        }

        if ($this->app->runningUnitTests()) {
            $blade->setCompiledPath(base_path('tests/.compiled'));
        }

        return $blade;
    }

    /**
     * Called when there is a successful command call.
     */
    public function succeeded()
    {
        if ($this->arbitraryData->get('compile-path')) {
            $this->newLine();
            if (! $this->arbitraryData->get('supress-warnings')) {
                $this->components->warn('The --compile-path has been renamed to --compiled-path and will be removed in a future release. Use --supress-warnings to silence this warning');
            }
        }
    }
}
