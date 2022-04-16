<?php

namespace BladeCLI;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;

class Blade
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * Whether the engine has been configured for rendering.
     *
     * @var boolean
     */
    protected bool $configured = false;

    /**
     * Construct a new Blade instance.
     *
     * @param \Illuminate\Container\Container $container
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     */
    public function __construct(Container $container, Filesystem $filesystem)
    {
        $this->container = $container;

        $this->filesystem = $filesystem;

        $this->engineResolver = $this->getEngineResolver();
    }

    /**
     * Configure the blade engine.
     *
     * @return void
     */
    public function configure()
    {
        $resolver = $this->getEngineResolver();

    }

    /**
     * Return the resolver for
     *
     * @return void
     */
    public function getEngineResolver()
    {
        return new EngineResolver;
    }
}