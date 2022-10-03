<?php

use Surgiie\BladeCLI\Application;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Application::getContainerInstance();
        }

        return Application::getContainerInstance()->make($abstract, $parameters);
    }
}