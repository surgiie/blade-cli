<?php

namespace BladeCLI;

use SplFileInfo;
use BladeCLI\Support\FileFinder;
use BladeCLI\Support\FileFactory;
use Illuminate\Events\Dispatcher;
use BladeCLI\Support\FileCompiler;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use BladeCLI\Support\FileCompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use BladeCLI\Support\Exceptions\FileNotFoundException;
use BladeCLI\Support\Exceptions\CouldntWriteFileException;
use BladeCLI\Support\Exceptions\FileAlreadyExistsException;
use BladeCLI\Support\Exceptions\UndefinedVariableException;
use PHPUnit\Framework\Assert as PHPUnit;

class Blade
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
     * Data about testing/faking render calls.
     *
     * @var array
     */
    protected static array $testing = [
        'directory'=>null,
        'test-files'=>[]
    ];

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
     * Specifies testing is being done and
     * files should be written to the given directory.
     *
     * @param string $directory
     * @return void
     */
    public static function fake(string $directory)
    {
        self::$testing['directory'] = $directory;

        @mkdir(self::$testing['directory'], recursive: true);
    }

    /**
     * Perform testing cleanup in tear down.
     *
     * @return void
     */
    public static function tearDown()
    {
        if(self::isFaked()){
            (new Filesystem)->deleteDirectory(self::$testing['directory']);
            self::$testing['test-files'] = [];
            self::$testing['directory'] = null;
        }
    }
    /**
     * Generate a path to the testing directory.
     *
     * @param string $path
     * @return string|void
     */
    public static function testPath(string $path)
    {
        if(self::isFaked()){
            $path = trim($path, "\\/");
        
            return self::$testing['directory'].DIRECTORY_SEPARATOR.$path;
        }
    }
    /**
     * Write test file to testing directory.
     *
     * @param string $file
     * @param string $contents
     * @return void
     */
    public static function putTestFile(string $file, string $contents)
    {
        if(self::isFaked()){
            file_put_contents($file = self::testPath($file), $contents);
            self::$testing['test-files'][] = $file;
        }
    }
    /**
     * Assert file was rendered.
     *
     * @param string $file
     * @param null|string $expected
    * @return void
     */
    public static function assertRendered(string $file, string $expected = null)
    {
        if(self::isFaked()){
            clearstatcache();

            $path = self::testPath($file);

            PHPUnit::assertTrue(
                !in_array($path, self::$testing['test-files']) && file_exists($path), "Unable to find rendered file at [{$path}]."
            );

            if(!is_null($expected)){
                PHPUnit::assertEquals(
                    $expected, file_get_contents($path), "Rendered file $file does not match expected content."
                );
            }
        }
    }
    /**
     * Check if the rendering is being faked.
     *
     * @return boolean
     */
    public static function isFaked()
    {
        return !is_null(self::$testing['directory']);
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
    public function setFilePath(string $filePath)
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
        if ($saveDir = $this->getOption('save-directory')) {
            $saveDir = rtrim($saveDir, "\\/");
        }
        // todo use test directory.

        if(self::isFaked() && $saveDir){
            return self::testPath($saveDir);
        }

        return $saveDir ?: $this->getFileDirectory();
    }

    /**
     * Get the file name that will be used for the saved file.
     *
     * @return string
     */
    protected function getSavedFileName()
    {
        $saveDirectory = $this->getOption('save-directory');

        if ($saveDirectory) {
            return $this->file->getFilename();
        }

        $basename = rtrim($this->file->getBasename($ext = $this->file->getExtension()), '.');

        return $basename . ".rendered" . ($ext ? ".$ext" : '');
    }

    /**
     * Get the absolute realpath to where the file was rendered.
     *
     * @return string
     */
    public function getSaveLocation()
    {
        $directory = $this->getSaveDirectory();

        $path = $directory . DIRECTORY_SEPARATOR . $this->getSavedFileName();

        return realpath($path) ?: $path;
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
                    "Undefined variable \$$match[1] on line $line."
                );
            }
        };
    }

    /**
     * Save the contents of the rendered file.
     *
     * @param string $contents
     * @throws \BladeCLI\Support\Exceptions\FileAlreadyExistsException 
     * @throws \BladeCLI\Support\Exceptions\CouldntWriteFileException
     * @return bool
     */
    protected function saveRenderedContents(string $contents)
    {
        $this->ensureSaveDirectoryExists();

        $saveTo = $this->getSaveLocation();

        if (file_exists($saveTo) && $this->getOption('force', false) !== true) {

            $saveTo = realpath($saveTo);

            throw new FileAlreadyExistsException("The file $saveTo already exists.");
        }

        $success = $this->filesystem->put($saveTo, $contents);

        if (!$success) {
            $saveTo = realpath($saveTo);
            throw new CouldntWriteFileException("Could not write/save file to: $saveTo");
        }

        return $success;
    }

    /**
     * Ensure the save directory exists.
     *
     * @return bool
     */
    protected function ensureSaveDirectoryExists()
    {
        $dir = $this->getSaveDirectory();

        return @mkdir(
            $dir,
            recursive: true
        );
    }

    /**
     * Render the file with the given data.
     *
     * @param array $data
     * @return \BladeCLI\Support\File|bool
     */
    public function render(array $data = [])
    {
        if (!$this->shouldRender()) {
            return false;
        }

        $renderFile = $this->getFileFactory()->make($this->file->getFilename(), $data);

        set_error_handler($this->getRenderErrorHandler());

        $contents = $renderFile->render();

        restore_error_handler();

        $this->saveRenderedContents($contents);

        $this->filesystem->deleteDirectory($this->getCompiledPath(), preserve: true);

        return $renderFile;
    }
}
