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

        if ($compiledPath = $this->data->get('compiled-path')) {
            $blade->setCompiledPath($compiledPath);
        }

        if ($this->app->runningUnitTests()) {
            $blade->setCompiledPath(base_path('tests/.compiled'));
        }

        return $blade;
    }
}
