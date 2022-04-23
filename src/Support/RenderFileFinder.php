<?php

namespace BladeCLI\Support;

use InvalidArgumentException;
use Illuminate\View\FileViewFinder;

class RenderFileFinder extends FileViewFinder
{
    /**
     * Overwritten to disable dot notation.
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        // allows includes to be rendered.
        $ext = pathinfo($name)["extension"] ?? "";

        if ($ext) {
            $this->addExtension($ext);
        }

        return array_map(function ($extension) use ($name, $ext) {
            if (empty($extension) || $ext) {
                return $name;
            }

            return $name . "." . $extension;
        }, $this->extensions);
    }

    /**
     * Find the given view in the list of paths.
     *
     * @param  string  $name
     * @param  array  $paths
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if ($this->files->exists($viewPath = $path . "/" . $file)) {
                    return $viewPath;
                }
            }
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }
}
