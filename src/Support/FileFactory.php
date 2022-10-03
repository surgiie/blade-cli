<?php

namespace Surgiie\BladeCLI\Support;

use Illuminate\View\Factory;
use Surgiie\BladeCLI\Blade;

class FileFactory extends Factory
{
    /**
     * Disable dot notation normalization.
     */
    protected function normalizeName($name)
    {
        return $name;
    }

    /**
     * Get the extension used by the view file.
     */
    protected function getExtension($path)
    {
        return pathinfo($path)['extension'] ?? '';
    }

    /**
     * Get the appropriate view engine for the given path.
     */
    public function getEngineFromPath($path)
    {
        return $this->engines->resolve(Blade::ENGINE_NAME);
    }

    /**
     * Create a new view instance from the given arguments.
     */
    protected function viewInstance($view, $path, $data)
    {
        return new File($this, $this->getEngineFromPath($path), $view, $path, $data);
    }
}
