<?php

namespace App\Support;

use Surgiie\Blade\Blade;
use Surgiie\Console\Command;

abstract class BaseCommand extends Command
{
    /**
     * Return the blade cache path to use for cached compiled files.
     *
     * @return string
     */
    protected function bladeCachePath()
    {
        $env = getenv('BLADE_CLI_COMPILED_PATH');

        if ($env) {
            $this->components->warn('The BLADE_CLI_COMPILED_PATH env has been renamed to BLADE_CLI_CACHE_PATH and will be removed in a future release. Use the BLADE_CLI_CACHE_PATH env instead.');

            return $env;
        }

        $env = getenv('BLADE_CLI_CACHE_PATH');

        if ($env) {
            return $env;
        }

        if ($compiledPath = $this->arbitraryData->get('compiled-path')) {
            $this->components->warn('The --compiled-path option is deprecated and will be removed in future release. Use the --cache-path option.');

            return $compiledPath;
        }

        if ($cachePath = $this->data->get('cache-path')) {
            return $cachePath;
        }

        if ($this->app->runningUnitTests()) {
            return base_path('tests/.compiled');
        }

        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.blade-cli';
    }

    /**
     * Return a new instance of the Blade engine.
     */
    protected function blade(): Blade
    {
        Blade::setCachePath($this->bladeCachePath());

        return parent::blade();
    }
}
