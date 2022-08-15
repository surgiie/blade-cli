<?php

namespace Surgiie\BladeCLI\Support\Concerns;


trait NormalizesPaths
{
    /**
     * Normalize a path from linux to windows or vice versa.
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path)
    {
        if (DIRECTORY_SEPARATOR == "\\") {
            return str_replace("/", "\\", $path);
        } else {
            return str_replace("\\", "/", $path);
        }
    }
}
