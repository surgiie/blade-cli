<?php

namespace BladeCLI;

use InvalidArgumentException;
use Illuminate\Events\Dispatcher;
use Illuminate\View\ViewException;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use BladeCLI\Support\RenderFileFinder;
use BladeCLI\Support\RenderFileFactory;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
use BladeCLI\Support\RenderFileCompilerEngine;

class Blade
{
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
     * The path to the template/directory being rendered.
     *
     * @var string
     */
    protected string $renderFilePath;

    /**
     * The render file factory.
     *
     * @var \BladeCLI\Support\RenderFileFactory
     */
    protected \BladeCLI\Support\RenderFileFactory $fileFactory;
    /**
     * The view finder.
     *
     * @var \BladeCLI\Support\RenderFileFinder
     */
    protected RenderFileFinder $fileFinder;

    /**
     * Metadata about the current render file.
     *
     * @var array
     */
    protected array $metadata = [];
    /**
     * Construct a new Blade instance.
     *
     * @param \Illuminate\Container\Container $container
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @param string $renderFilePath
     */
    public function __construct(
        Container $container,
        Filesystem $filesystem,
        string $renderFilePath
    ) {
        $this->container = $container;

        $this->filesystem = $filesystem;

        $this->setRenderFilePath($renderFilePath);

        $this->fileFinder = new RenderFileFinder($this->filesystem, [$this->metadata["dirname"]]);

        $this->fileFactory = new RenderFileFactory(
            $resolver = $this->getEngineResolver(),
            $this->fileFinder,
            new Dispatcher($this->container)
        );

        $this->compiler = $this->getCompiler();

        $resolver->register("blade", function () {
            return new RenderFileCompilerEngine($this->compiler);
        });
    }

    /**
     * Return the compiler for the blade engine.
     *
     * @return \Illuminate\View\Compilers\BladeCompiler
     */
    public function getCompiler()
    {
        return new BladeCompiler($this->filesystem, $this->getCompiledPath());
    }

    /**
     * Get the compiled path to where rendered files go.
     *
     * @return string
     */
    protected function getCompiledPath()
    {
        return realpath(__DIR__ . "/../.compiled");
    }

    /**
     * Return the engine resolver.
     *
     * @return \Illuminate\View\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return new EngineResolver();
    }

    /**
     * Set the render file path.
     *
     * @param string $renderFilePath
     * @return static
     */
    public function setRenderFilePath(string $renderFilePath)
    {
        if (!is_file($renderFilePath)) {
            throw new InvalidArgumentException(
                "File $renderFilePath is not a file or does not exist."
            );
        }

        $this->renderFilePath = $renderFilePath;

        $file = basename($this->renderFilePath);

        $extension = pathinfo($file)["extension"] ?? "";

        // save some basic metadata about the file to reference.
        $this->metadata = [
            "basename" => $file,
            "extension" => $extension,
            "filename_no_extension" => basename(rtrim($this->renderFilePath, ".$extension")),
            "dirname" => dirname(realpath($this->renderFilePath)),
        ];

        return $this;
    }

    /**
     * Get the error handler to use when we render.
     *
     * @return callable
     */
    protected function getRenderErrorHandler()
    {
        // we will set a custom handler to throw an exception on missing data instead of a warning.
        return function ($severity, $message, $file, $line) {
            if ($severity != E_WARNING) {
                return;
            }
            preg_match('/Undefined variable \$(.*)/', $message, $match);

            if ($match) {
                throw new ViewException(
                    "Undefined variable \$$match[1] on line $line. Did you pass the --$match[1] option or use camel case for variable names?"
                );
            }
        };
    }

    /**
     * Render the template file.
     *
     * @param array $options
     * @param array $data
     * @return \BladeCLI\Support\RenderFile
     */
    public function render(array $options = [], array $data = [])
    {
        $extension = $this->metadata["extension"];

        $this->fileFactory->addExtension($extension, "blade");

        $filename = $this->metadata["filename_no_extension"];

        $template = $this->fileFactory->make($filename, $data);

        set_error_handler($this->getRenderErrorHandler());

        $contents = $template->render();

        restore_error_handler();

        $this->saveRenderedContents($contents, $options);

        $this->filesystem->deleteDirectory($this->getCompiledPath(), preserve: true);

        return $template;
    }

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
        }else{
            return str_replace("\\", "/", $path);
        }
    }

    /**
     * Get the default save location for the rendered result.
     *
     * @param bool $absolute
     * @return string
     */
    public function getDefaultSaveFileLocation(bool $absolute = true)
    {
        $dir = $this->metadata["dirname"];

        $extension = $this->metadata["extension"];

        $filename = $this->metadata["filename_no_extension"];

        if (!$absolute) {
            $dir = dirname($this->renderFilePath);
        }

        return $this->normalizePath(
            $dir . DIRECTORY_SEPARATOR . $filename . ".rendered" . ".$extension"
        );
    }

    /**
     * Save the contents of a rendered file.
     *
     * @param string $contents
     * @param array $options
     * @return bool
     */
    protected function saveRenderedContents(string $contents, array $options)
    {
        $saveTo = $options["save-to"] ?? "";

        if (!$saveTo) {
            $saveTo = $this->getDefaultSaveFileLocation();
        }

        // todo handle custom location (ie make sure lcoation exists, etc.)

        return $this->filesystem->put($saveTo, $contents);
    }
}
