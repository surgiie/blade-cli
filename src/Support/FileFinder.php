<?php

namespace BladeCLI\Support;

use Illuminate\View\FileViewFinder;
use InvalidArgumentException;

class FileFinder extends FileViewFinder
{
    /**
     * The file extensions.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Set the active file paths.
     *
     * @param  array  $paths
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->paths = array_map([$this, 'resolvePath'], $paths);

        return $this;
    }

    /**
     * Overwritten to disable dot notation.
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        // allows includes to be rendered on the fly.
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
        try{
            return parent::findInPaths($name, $paths);
        }catch(InvalidArgumentException){
            throw new InvalidArgumentException("File [{$name}] not found.");
        }
    }
}
