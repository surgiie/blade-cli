<?php

use Surgiie\BladeCLI\Support\Command;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Command::getInstance();
        }

        return Command::getInstance()->make($abstract, $parameters);
    }
}