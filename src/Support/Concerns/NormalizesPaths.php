<?php

namespace BladeCLI\Support\Concerns;

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
    /**
     * Remove a trailing slash from end of path.
     *
     * @param string $path
     * @return string
     */
    protected function removeTrailingSlash(string $path)
    {
        return rtrim($path, "\\/");
    }
    /**
     * Remove a leading slash from start of path.
     *
     * @param string $path
     * @return string
     */
    protected function removeLeadingSlash(string $path)
    {
        return ltrim($path, "\\/");
    }
}
