<?php

namespace BladeCLI;

use InvalidArgumentException;
use BladeCLI\Support\FileFinder;
use BladeCLI\Support\FileFactory;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use BladeCLI\Support\CompilerEngine;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;

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
     * The path to the template/directory being rendered.
     *
     * @var string
     */
    protected string $renderFilePath;

    /**
     * The view factory.
     *
     * @var FileFactory
     */
    protected \BladeCLI\Support\FileFactory $fileFactory;
    /**
     * The view finder.
     *
     * @var \BladeCLI\Support\FileFinder
     */
    protected \BladeCLI\Support\FileFinder $fileFinder;

    /**
     * Construct a new Blade instance.
     *
     * @param \Illuminate\Container\Container $container
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @param string $renderFilePath
     */
    public function __construct(Container $container, Filesystem $filesystem, string $renderFilePath)
    {
        $this->container = $container;

        $this->filesystem = $filesystem;

        $this->setRenderFilePath($renderFilePath);

        $this->fileFinder = new FileFinder(
            $this->filesystem,
            [
                dirname(realpath($this->renderFilePath))
            ]
        );

        $this->engineResolver = $this->getEngineResolver();

        $this->eventDispatcher = new Dispatcher($this->container);

        $this->fileFactory = new FileFactory($this->engineResolver, $this->fileFinder, $this->eventDispatcher);

        $this->compiler = $this->getCompiler();

        $this->engineResolver->register('blade', function () {
            return new CompilerEngine($this->compiler);
        });

    }

    /**
     * Set the render file path.
     *
     * @param string $renderFilePath
     * @return static
     */
    public function setRenderFilePath(string $renderFilePath)
    {
        if(!is_file($renderFilePath)){
            throw new InvalidArgumentException("File $renderFilePath is not a file or does not exist.");
        }

        $this->renderFilePath = $renderFilePath;

        return $this;
    }

    /**
     * Render the template file.
     *
     * @param array $options
     * @return void
     */
    public function render(array $options = [])
    {
        $extension = pathinfo($this->renderFilePath)['extension'] ?? '';

        $this->fileFactory->addExtension($extension, 'blade');

        $template = $this->fileFactory->make(rtrim($this->renderFilePath, ".$extension"), []);

        dd($template->render());
        // todo save rendered file to location.

        return $template;
    }

    /**
     * Return the compiler for the blade engine.
     *
     * @return \Illuminate\View\Compilers\BladeCompiler
     */
    public function getCompiler()
    {
        return new BladeCompiler($this->filesystem, realpath(__DIR__.'/../.compiled'));
    }

    /**
     * Return the engine resolver.
     *
     * @return \Illuminate\View\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return new EngineResolver;
    }
}