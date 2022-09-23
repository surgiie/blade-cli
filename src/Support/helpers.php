<?php

use Surgiie\BladeCLI\Support\ConsoleApplication;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return ConsoleApplication::getInstance();
        }

        return ConsoleApplication::getInstance()->make($abstract, $parameters);
    }
}