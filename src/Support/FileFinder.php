<?php
namespace BladeCLI\Support;


use Illuminate\View\FileViewFinder;

class FileFinder extends FileViewFinder
{
    /**
     * Overwritten to disable dot notation.
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        return array_map(function ($extension) use ($name) {
            if(empty($extension)){
                return $name;
            }
            return $name.'.'.$extension;
        }, $this->extensions);
    }

}