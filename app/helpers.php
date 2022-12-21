<?php

if (! function_exists('normalize_path')) {
    /**
     * Normalize a file path from unix style to windows style, if needed.
     */
    function normalize_path(string $path)
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return $path;
    }
}
