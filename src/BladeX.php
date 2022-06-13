<?php

namespace BladeCLI;

use BladeCLI\Support\Exceptions\FileNotFoundException;
use SplFileInfo;
use BladeCLI\Support\FileFinder;
use BladeCLI\Support\FileFactory;
use Illuminate\Events\Dispatcher;
use BladeCLI\Support\FileCompiler;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use BladeCLI\Support\FileCompilerEngine;
use Illuminate\View\Engines\EngineResolver;

class BladeX
{

    /**
     * Get the engine name for resolver registration.
     * 
     * @var string
     */
    const ENGINE_NAME = "blade";
    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected Container $container;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * The file being rendred.
     * 
     * @var \SplFileInfo
     */
    protected \SplFileInfo $file;

    /**
     * The file factory instance.
     *
     * @var \BladeCLI\Support\FileFactory
     */
    protected ?FileFactory $fileFactory = null;
    
    /**
     * The engine resolver instance.
     *
     * @var \Illuminate\View\Engines\EngineResolver|nul
     */
    protected ?EngineResolver $resolver = null;

    /**
     * The file compiler instance.
     *
     * @var \BladeCLI\Support\FileCompiler|null
     */
    protected ?FileCompiler $fileCompiler = null;

    /**
     * The file compiler engine instance.
     *
     * @var \BladeCLI\Support\FileCompilerEngine|null
     */
    protected ?FileCompilerEngine $compilerEngine = null;

    /**
     * The file finder instance.
     *
     * @var \BladeCLI\Support\FileFinder|null
     */
    protected ?FileFinder $fileFinder = null;

    /**
     * Flag specifying testing directory.
     *
     * @var string|null
     */
    protected static ?string $testingDirectory = null;

    /**
     * Construct a new Blade instance and configure engine.
     *
     * @param \Illuminate\Container\Container $container
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @param string $filePath
     * @param array $options
     */
    public function __construct(Container $container, Filesystem $filesystem, string $filePath, array $options)
    {
        $this->container = $container;

        $this->filesystem = $filesystem;

        $this->setOptions($options);

        $this->setFilePath($filePath);

        $this->makeCompiledDirectory();

        $this->resolver = $this->getEngineResolver();

        $this->resolver->register(self::ENGINE_NAME, function () {
            return $this->getCompilerEngine();
        });
    }

    /**
     * The test directory files will be written to.
     *
     * @param string $directory
     * @return void
     */
    public static function fake(string $directory)
    {
    }

    /**
     * Return the set file finder.
     *
     * @return \BladeCLI\Support\FileFinder
     */
    public function getFileFinder()
    {
        if (!is_null($this->fileFinder)) {
            return $this->fileFinder;
        }

        return $this->fileFinder = new FileFinder($this->filesystem, []);
    }

    /**
     * Return the set engine resolver.
     *
     * @return \Illuminate\View\Engines\EngineResolver
     */
    protected function getEngineResolver()
    {
        if (!is_null($this->resolver)) {
            return $this->resolver;
        }
        return $this->resolver = new EngineResolver();
    }

    /**
     * Return set file factory instance.
     *
     * @return \BladeCLI\Support\FileFactory
     */
    protected function getFileFactory()
    {
        if (!is_null($this->fileFactory)) {
            return $this->fileFactory;
        }

        return $this->fileFactory = new FileFactory(
            $this->getEngineResolver(),
            $this->getFileFinder(),
            new Dispatcher($this->container)
        );
    }

    /**
     * Return set compiler engine instance.
     *
     * @return \BladeCLI\Support\FileCompilerEngine
     */
    protected function getCompilerEngine()
    {
        if (!is_null($this->compilerEngine)) {
            return $this->compilerEngine;
        }

        return $this->compilerEngine = new FileCompilerEngine($this->getFileCompiler());
    }

    /**
     * Return the set file compiler instance.
     *
     * @return \BladeCLI\Support\FileCompiler
     */
    protected function getFileCompiler()
    {
        if (!is_null($this->fileCompiler)) {
            return $this->fileCompiler;
        }

        return $this->fileCompiler = new FileCompiler($this->filesystem, $this->getCompiledPath());
    }

    /**
     * Get the compiled path to where compiled files go.
     *
     * @return string
     */
    protected function getCompiledPath()
    {
        return __DIR__ . "/../.compiled";
    }

    /**
     * Make the directory where compiled files go.
     *
     * @return bool
     */
    public function makeCompiledDirectory()
    {
        return @mkdir($this->getCompiledPath());
    }

    /**
     * Set the path to the file being rendered.
     *
     * @param string $filePath
     * @throws \BladeCLI\Support\Exceptions\FileNotFoundException
     * @return static
     */
    protected function setFilePath(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException(
                "File $filePath does not exists."
            );
        }

        $this->file = new SplFileInfo($filePath);

        $this->getFileFinder()->setPaths([$this->getFileDirectory()]);

        $this->getFileFactory()->addExtension($this->file->getExtension(), self::ENGINE_NAME);

        return $this;
    }

    
    /**
     * Get the realpath directory of the file.
     *
     * @return string
     */
    public function getFileDirectory()
    {
        return dirname($this->file->getRealPath());
    }

    /**
     * Set the options for rendering.
     *
     * @return static
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get an option value or default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getOption($key, $default = null)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    /**
     * Check if the file should be rendered.
     *
     * @return bool
     */
    protected function shouldRender()
    {
        // if the file we are rendering is the rendered file location, then we are processing
        // an already rendered file. This happens on subsequent render calls on a directory
        // where we saved a render file previously.
        return $this->file->getRealPath() != $this->getSaveLocation();
    }

    /**
     * Get the directory the rendered file will be saved to.
     *
     * @return string
     */
    protected function getSaveDirectory()
    {
        // todo

        // current directory
        return $this->getFileDirectory();
    }
    /**
     * Render the file with the given data.
     *
     * @param array $data
     * @return \BladeCLI\Support\File|bool
     */
    public function render(array $data = [])
    {
        dd("RENDER", $this->getSaveDirectory());
        // if (! $this->shouldRender()) {
        //     return false;
        // }

        // $extension = $this->metadata["extension"];

        // $this->fileFactory->addExtension($extension, "blade");

        // $filename = $this->metadata["basename"];

        // $template = $this->fileFactory->make($filename, $data);

        // set_error_handler($this->getRenderErrorHandler());

        // $contents = $template->render();

        // restore_error_handler();

        // $this->saveRenderedContents($contents);

        // // cleanup .compiled
        // $this->filesystem->deleteDirectory($this->getCompiledPath(), preserve: true);

        // return $template;
    }
}
