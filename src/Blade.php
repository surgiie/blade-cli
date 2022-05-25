<?php

namespace BladeCLI;

use ErrorException;
use InvalidArgumentException;
use BladeCLI\Support\FileFinder;
use BladeCLI\Support\FileFactory;
use Illuminate\Events\Dispatcher;
use BladeCLI\Support\FileCompiler;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use BladeCLI\Support\FileCompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use BladeCLI\Support\Concerns\NormalizesPaths;
use BladeCLI\Support\Exceptions\FileAlreadyExistsException;
use BladeCLI\Support\Exceptions\UndefinedVariableException;

class Blade
{
    use NormalizesPaths;
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
     * The path to the file being rendered.
     *
     * @var string
     */
    protected string $filePath;

    /**
     * The render file factory.
     *
     * @var \BladeCLI\Support\FileFactory
     */
    protected FileFactory $fileFactory;

    /**
     * The file finder.
     *
     * @var \BladeCLI\Support\FileFinder
     */
    protected FileFinder $fileFinder;

    /**
     * Metadata about the current render file.
     *
     * @var array
     */
    protected array $metadata = [];

    /**
     * The options for rendering.
     *
     * @var array
     */
    protected array $options = [];

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

        $this->options = $options;

        $this->fileFinder = new FileFinder($this->filesystem, []);

        $this->setFilePath($filePath);

        $resolver = new EngineResolver();

        @mkdir($this->getCompiledPath());
        $resolver->register("blade", function () {
            return new FileCompilerEngine(new FileCompiler($this->filesystem, $this->getCompiledPath()));
        });

        $this->fileFactory = new FileFactory(
            $resolver,
            $this->fileFinder,
            new Dispatcher($this->container)
        );
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
     * Set the render file path.
     *
     * @param string $filePath
     * @return static
     */
    public function setFilePath(string $filePath)
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException(
                "File $filePath is not a file."
            );
        }

        $this->filePath = $filePath;

        // save some basic metadata about the file to reference.
        $this->metadata = $this->deriveFileMetaData($this->filePath);
        // update paths on finder
        $this->fileFinder->setPaths([$this->metadata["dirname"]]);

        return $this;
    }
    /**
     * Determine some useful metadata about the file to utilize.
     *
     * @param string
     * @return array
     */
    protected function deriveFileMetaData(string $path)
    {
        $file = basename($path);

        $extension = pathinfo($file)["extension"] ?? "";

        return [
            "basename" => $file,
            "extension" => $extension,
            "filename_no_extension" => basename($path, ".$extension"),
            "dirname" => dirname(realpath($path)),
        ];
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
                throw new UndefinedVariableException(
                    "Undefined variable \$$match[1] on line $line. Did you pass the --$match[1] option or use camel case for this variable?"
                );
            }
        };
    }

    /**
     * Check if the current file we're process should render.
     *
     * @return boolean
     */
    protected function shouldRender()
    {
        // if the file we are rendering is the rendered file location, then we are processing
        // an already rendered file. This happens on subsequent render calls on a directory
        // where we saved a render file previously.
        return realpath($this->filePath) != $this->getSaveLocation();
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
     * Get the save location directory.
     *
     * @return string
     */
    protected function getSaveDirectory()
    {
        return $this->getOption('save-directory',  $this->metadata['dirname']);
    }

    /**
     * Render the template file.
     *
     * @param array $data
     * @return \BladeCLI\Support\RenderFile|bool
     */
    public function render(array $data = [])
    {
        if (!$this->shouldRender()) {
            return false;
        }

        $extension = $this->metadata["extension"];

        $this->fileFactory->addExtension($extension, "blade");

        $filename = $this->metadata["basename"];

        $template = $this->fileFactory->make($filename, $data);

        set_error_handler($this->getRenderErrorHandler());

        $contents = $template->render();

        restore_error_handler();

        $this->saveRenderedContents($contents);

        // cleanup .compiled
        $this->filesystem->deleteDirectory($this->getCompiledPath(), preserve: true);

        return $template;
    }


    /**
     * Get the file name for the rendered file.
     *
     * @return string
     */
    protected function getFileRenderedName()
    {
        $extension = $this->metadata["extension"];

        $filename = $this->metadata["filename_no_extension"];

        return str_replace('.rendered', "", $filename) . ".rendered" . ($extension ? ".$extension" : "");
    }

    /**
     * Get the default save location relative path for the rendered file.
     *
     * @return string
     */
    protected function getDefaultRelativeSaveFileLocation()
    {
        $dir = $this->metadata["dirname"];

        return $dir . DIRECTORY_SEPARATOR . $this->getFileRenderedName();
    }

    /**
     * Get the absolute save location path.
     *
     * @return string
     */
    public function getSaveLocation()
    {
        $saveTo = $this->removeTrailingSlash($this->getSaveDirectory() ?? "");
        if (!$saveTo) {
            $path = $this->getDefaultRelativeSaveFileLocation();
        } else {
            $path = $saveTo . DIRECTORY_SEPARATOR . $this->getFileRenderedName();
        }

        return $this->normalizePath($path);
    }

    /**
     * Ensure the save directory exists.
     *
     * @return void
     */
    protected function ensureSaveDirectoryExists()
    {
        $dir = $this->getSaveDirectory();

        @mkdir(
            $dir,
            recursive: true
        );
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
     * Save the contents of a rendered file.
     *
     * @param string $contents
     * @return bool
     */
    protected function saveRenderedContents(string $contents)
    {
        $this->ensureSaveDirectoryExists();

        $saveTo = $this->getSaveLocation();

        if (file_exists($saveTo) && $this->getOption('force', false) !== true) {
            throw new FileAlreadyExistsException("The file $saveTo already exists.");
        }

        $success = $this->filesystem->put($saveTo, $contents);

        if (!$success) {
            throw new ErrorException("Could not write/save file to: $saveTo");
        }

        return $success;
    }
}
