<?php

namespace Surgiie\BladeCLI;

use BadMethodCallException;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\Engines\EngineResolver;
use PHPUnit\Framework\Assert as PHPUnit;
use SplFileInfo;
use Surgiie\BladeCLI\Support\Concerns\NormalizesPaths;
use Surgiie\BladeCLI\Support\Exceptions\CouldntWriteFileException;
use Surgiie\BladeCLI\Support\Exceptions\FileAlreadyExistsException;
use Surgiie\BladeCLI\Support\Exceptions\FileNotFoundException;
use Surgiie\BladeCLI\Support\Exceptions\PermissionException;
use Surgiie\BladeCLI\Support\Exceptions\UndefinedVariableException;
use Surgiie\BladeCLI\Support\File;
use Surgiie\BladeCLI\Support\FileCompiler;
use Surgiie\BladeCLI\Support\FileCompilerEngine;
use Surgiie\BladeCLI\Support\FileFactory;
use Surgiie\BladeCLI\Support\FileFinder;

class Blade
{
    use NormalizesPaths;

    /**
     * Get the engine name for resolver registration.
     */
    public const ENGINE_NAME = "blade";
    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * Simple cache property to avoid recalculations.
     */
    protected array $cache = ['save-directory' => null];

    /**
     * The filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * File info about the file being rendered.
     */
    protected ?SplFileInfo $fileInfo = null;

    /**
     * The file factory instance.
     */
    protected ?FileFactory $fileFactory = null;

    /**
     * The engine resolver instance.
     */
    protected ?EngineResolver $resolver = null;

    /**
     * The file compiler instance.
     */
    protected ?FileCompiler $fileCompiler = null;

    /**
     * The file compiler engine instance.
     */
    protected ?FileCompilerEngine $compilerEngine = null;

    /**
     * The file finder instance.
     */
    protected ?FileFinder $fileFinder = null;

    /**
     * The render file instance.
     */
    protected ?File $file = null;

    /**
     * Data about testing/faking render calls.
     */
    protected static array $testing = [
        'directory' => null,
        'test-render-files' => [],
    ];

    /**
     * Construct a new Blade instance and configure engine.
     */
    public function __construct(Container $container, Filesystem $filesystem, ?string $filePath = null, array $options = [])
    {
        $this->container = $container;

        $this->filesystem = $filesystem;

        $this->setOptions($options);

        if (! is_null($filePath)) {
            $this->setFilePath($filePath);
        }

        $this->makeCompiledDirectory();

        $this->resolver = $this->getEngineResolver();

        $this->resolver->register(self::ENGINE_NAME, function () {
            return $this->getCompilerEngine();
        });
    }

    /**
     * Set the testing directory for rendered files.
     */
    public static function fake(string $directory): void
    {
        self::$testing['directory'] = rtrim($directory, DIRECTORY_SEPARATOR);

        @mkdir(self::$testing['directory'], recursive: true);
    }

    /**
     * Perform testing cleanup.
     */
    public static function tearDown(): void
    {
        if (self::isFaked()) {
            (new Filesystem())->deleteDirectory(self::$testing['directory']);
            self::$testing['test-render-files'] = [];
            self::$testing['directory'] = null;
        }
    }

    /**
     * Generate a path to the testing directory.
     *
     * @return void|string
     */
    public static function testPath(string $path = "")
    {
        if (self::isFaked()) {
            $path = trim($path, "\\/");

            return rtrim(self::$testing['directory'] . DIRECTORY_SEPARATOR . $path, DIRECTORY_SEPARATOR);
        }
    }

    /**
     * Write a test file to testing directory.
     */
    public static function putTestFile(string $file, string $contents): void
    {
        if (self::isFaked()) {
            $path = self::testPath($file);
            @mkdir(dirname($path), recursive: true);
            file_put_contents($path, $contents);
            self::$testing['test-render-files'][] = $path;
        }
    }

    /**
     * Assert a file was rendered.
     */
    public static function assertRendered(string $file, string $expected = null): void
    {
        if (self::isFaked()) {
            clearstatcache();

            $path = self::testPath($file);

            PHPUnit::assertTrue(
                ! in_array($path, self::$testing['test-render-files']) && file_exists($path),
                "Unable to find rendered file at [{$path}]."
            );

            if (! is_null($expected)) {
                PHPUnit::assertEquals(
                    $expected,
                    file_get_contents($path),
                    "Rendered file $file does not match expected content."
                );
            }
        }
    }

    /**
     * Assert a file was not rendered.
     */
    public static function assertNotRendered(string $file): void
    {
        if (self::isFaked()) {
            clearstatcache();

            $path = self::testPath($file);

            PHPUnit::assertTrue(
                ! in_array($path, self::$testing['test-render-files']) && ! file_exists($path),
                "Found rendered file when expected not to: [{$path}]."
            );
        }
    }

    /**
     * Check if the rendering is being faked.
     */
    public static function isFaked(): bool
    {
        return ! is_null(self::$testing['directory']);
    }

    /**
     * Return the set file finder.
     */
    public function getFileFinder(): FileFinder
    {
        if (! is_null($this->fileFinder)) {
            return $this->fileFinder;
        }

        return $this->fileFinder = new FileFinder($this->filesystem, []);
    }

    /**
     * Return the set engine resolver.
     */
    protected function getEngineResolver(): EngineResolver
    {
        if (! is_null($this->resolver)) {
            return $this->resolver;
        }

        return $this->resolver = new EngineResolver();
    }

    /**
     * Return set file factory instance.
     */
    protected function getFileFactory(): FileFactory
    {
        if (! is_null($this->fileFactory)) {
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
     */
    protected function getCompilerEngine(): FileCompilerEngine
    {
        if (! is_null($this->compilerEngine)) {
            return $this->compilerEngine;
        }

        return $this->compilerEngine = new FileCompilerEngine($this->getFileCompiler());
    }

    /**
     * Return the set file compiler instance.
     */
    protected function getFileCompiler(): FileCompiler
    {
        if (! is_null($this->fileCompiler)) {
            return $this->fileCompiler;
        }

        return $this->fileCompiler = new FileCompiler($this->filesystem, $this->getCompiledPath());
    }

    /**
     * Get the current render file instance.
     */
    public function getfileInstance(): null|File
    {
        return $this->file;
    }

    /**
     * Get the compiled path to where compiled files go.
     */
    protected function getCompiledPath(): string
    {
        return __DIR__ . "/../.compiled";
    }

    /**
     * Make the directory where compiled files go.
     */
    public function makeCompiledDirectory(): bool
    {
        return @mkdir($this->getCompiledPath());
    }

    /**
     * Set the path to the file being rendered.
     *
     * @throws \Surgiie\BladeCLI\Support\Exceptions\FileNotFoundException
     */
    public function setFilePath(string $filePath): static
    {
        if (self::isFaked()) {
            $filePath = self::testPath($filePath);
        }

        if (! file_exists($filePath)) {
            throw new FileNotFoundException(
                "File $filePath does not exist."
            );
        }

        $this->fileInfo = new SplFileInfo($filePath);

        $this->getFileFinder()->setPaths([$this->getFileDirectory()]);

        $this->getFileFactory()->addExtension($this->fileInfo->getExtension(), self::ENGINE_NAME);

        foreach (array_keys($this->cache) as $key) {
            $this->cache[$key] = null;
        }

        return $this;
    }

    /**
     * Get the realpath directory of the file.
     */
    public function getFileDirectory(): string
    {
        return dirname($this->fileInfo->getRealPath());
    }

    /**
     * Set the options for rendering.
     */
    public function setOptions(array $options): static
    {
        $this->options = array_filter($options, function ($v) {
            return ! is_null($v);
        });

        return $this;
    }

    /**
     * Get an option value or default.
     */
    protected function getOption(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    /**
     * Check if the rendered file destination is for the file itself.
     */
    protected function destinationIsFileBeingRendered(): bool
    {
        // if the file we are rendering is the rendered file location, then we are processing
        // an already rendered file. This happens on subsequent render calls on a directory
        // where we saved a render file previously.
        return $this->fileInfo->getRealPath() != $this->getSaveLocation();
    }

    /**
     * Get the directory the rendered file will be saved to.
     */
    protected function getSaveDirectory(): string
    {
        if (! is_null($this->cache['save-directory'])) {
            return $this->cache['save-directory'];
        }

        $saveDir = $this->getOption('save-as');
        if (! $saveDir) {
            return  $this->getFileDirectory();
        }

        $saveDir = rtrim($saveDir, "\\/");

        $saveDir = $this->normalizePath($saveDir);
        $filename = basename($saveDir);

        $saveDir = str_replace($filename, "", $saveDir);

        if (self::isFaked()) {
            $saveDir = self::testPath($saveDir);
        }
        // allow ~ syntax and expand accordingly
        if (Str::startsWith($saveDir, "~" . DIRECTORY_SEPARATOR)) {
            $home = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? getenv("USERPROFILE") : getenv("HOME");
            $saveDir = rtrim($home, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($saveDir, "~" . DIRECTORY_SEPARATOR);
        }

        return $this->cache['save-directory'] = in_array($saveDir, ['./', '', '.\\']) ? getcwd() : $saveDir;
    }

    /**
     * Get the default file name that will be used for the saved file.
     */
    protected function getDefaultSaveFileName(): string
    {
        $basename = rtrim($this->fileInfo->getBasename($ext = $this->fileInfo->getExtension()), '.');

        return $basename . ".rendered" . ($ext ? ".$ext" : '');
    }

    /**
     * Get the absolute path to where the file was rendered.
     */
    public function getSaveLocation(): string
    {
        $ds = DIRECTORY_SEPARATOR;
        $saveAs = $this->getOption('save-as', $this->getDefaultSaveFileName());

        if (empty($saveAs)) {
            throw new BadMethodCallException("The save-as option value is empty, no save file locaton given.");
        }

        $basename = basename($saveAs);
        $derivedDirectory = $this->normalizePath($this->getSaveDirectory());

        if ($derivedDirectory != $ds) {
            $derivedDirectory = rtrim($derivedDirectory, $ds) . $ds;
        }

        $path = rtrim($derivedDirectory . ltrim($basename, $ds), $ds);

        return realpath($path) ?: $path;
    }

    /**
     * Return a custom error handler for when we render a file.
     */
    protected function getRenderErrorHandler(): Closure
    {
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
     * @throws \Surgiie\BladeCLI\Support\Exceptions\FileAlreadyExistsException
     * @throws \Surgiie\BladeCLI\Support\Exceptions\CouldntWriteFileException
     */
    protected function saveRenderedContents(string $contents): bool
    {
        $this->ensureSaveDirectoryExists();

        $saveTo = $this->getSaveLocation();

        if (! is_writable($saveDirectory = $this->getSaveDirectory())) {
            throw new PermissionException("The save directory $saveDirectory is not writable.");
        }

        $success = $this->filesystem->put($saveTo, $contents);

        if ($success === false) {
            $saveTo = realpath($saveTo);

            throw new CouldntWriteFileException("Could not write/save file to: $saveTo");
        }

        return $success;
    }

    /**
     * Ensure the save directory exists.
     */
    protected function ensureSaveDirectoryExists(): bool
    {
        $dir = $this->getSaveDirectory();

        return @mkdir(
            $dir,
            recursive: true
        );
    }

    /**
     * Render the file with the given data and optionally save file.
     */
    public function render(array $data = [], bool $save = true): string
    {
        if (is_null($this->fileInfo)) {
            throw new BadMethodCallException("A file path has not been set, nothing to render.");
        }

        $saveAs = $this->getSaveLocation();

        if (file_exists($saveAs) && $this->getOption('force', false) !== true) {
            $saveAs = realpath($saveAs);

            throw new FileAlreadyExistsException("The file $saveAs already exists.");
        }

        if (is_dir($saveAs) && $this->getOption('save-as')) {
            throw new BadMethodCallException("Your save file is an existing directory, use path to a non-existing file.");
        }

        if (! $this->destinationIsFileBeingRendered()) {
            throw new BadMethodCallException("Your save file location is for the file being rendered. Use different save filename.");
        }

        $this->file = $this->getFileFactory()->make($this->fileInfo->getFilename(), $data);

        set_error_handler($this->getRenderErrorHandler());

        $contents = $this->file->render();

        restore_error_handler();

        if ($save) {
            $this->saveRenderedContents($contents);
        }

        $this->filesystem->deleteDirectory($this->getCompiledPath(), preserve: true);

        return $contents;
    }
}
