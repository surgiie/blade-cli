<?php

namespace Surgiie\BladeCLI\Support\Concerns;

trait NormalizesPaths
{
    /**
     * Normalize a path from linux to windows or vice versa.
     */
    protected function normalizePath(string $path): string
    {
        if (DIRECTORY_SEPARATOR == "\\") {
            return str_replace("/", "\\", $path);
        } else {
            return str_replace("\\", "/", $path);
        }
    }
}
