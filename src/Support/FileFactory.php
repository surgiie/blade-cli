<?php
namespace BladeCLI\Support;

use Illuminate\Support\Arr;
use Illuminate\View\Factory;
use InvalidArgumentException;
use BladeCLI\Support\RenderFile;

class FileFactory extends Factory
{
    /**
     * Disable dota notation normalization.
     *
     * @param string $name
     * @return void
     */
    protected function normalizeName($name)
    {
        return $name;
    }

    /**
     * Get the extension used by the view file.
     *
     * @param  string  $path
     * @return string|null
     */
    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);

        $extension = Arr::first($extensions, function ($value) use ($path) {
            return str_ends_with($path, "." . $value);
        });

        return $extension ?? "";
    }
    /**
     * Get the appropriate view engine for the given path.
     *
     * @param  string  $path
     * @return \Illuminate\Contracts\View\Engine
     *
     * @throws \InvalidArgumentException
     */
    public function getEngineFromPath($path)
    {
        $extension = $this->getExtension($path);
        if (!array_key_exists($extension, $this->extensions)) {
            throw new InvalidArgumentException("Unrecognized extension in file: {$path}.");
        }

        $engine = $this->extensions[$extension];

        return $this->engines->resolve($engine);
    }

    /**
     * Create a new view instance from the given arguments.
     *
     * @param  string  $view
     * @param  string  $path
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @return \Illuminate\Contracts\View\View
     */
    protected function viewInstance($view, $path, $data)
    {
        return new RenderFile($this, $this->getEngineFromPath($path), $view, $path, $data);
    }
}
