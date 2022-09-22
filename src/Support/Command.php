<?php

namespace Surgiie\BladeCLI\Support;

use Surgiie\BladeCLI\Blade;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command as BaseCommand;
use Illuminate\Console\View\Components\Factory as ConsoleViewFactory;

class Command extends BaseCommand
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $laravel;
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * A cached blade instance to utilize.
     *
     * @var Blade|null
     */
    protected ?Blade $bladeInstance = null;

    /**
     * The static app/container instance for app() calls.
     *
     * @var Container|null
     */
    protected static ?Container $appInstance = null;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->laravel = static::$appInstance = new Container();

        $this->filesystem = new Filesystem();

        parent::__construct();

        $this->components = $this->laravel->make(ConsoleViewFactory::class, ['output' => $this->output]);
    }

    /**Get the app instance.*/
    public static function getInstance()
    {
        return static::$appInstance;
    }

    /**
     * Return a render ready blade instance for a given file.
     *
     * @param string $filePath
     * @param array $options
     * @return \Surgiie\BladeCLI\Blade
     */
    protected function blade(string $filePath, array $options = []): Blade
    {
        if (! is_null($this->bladeInstance)) {
            return $this->bladeInstance->setOptions($options)->setFilePath($filePath);
        }

        return $this->bladeInstance = new Blade(
            container: $this->laravel,
            filesystem: $this->filesystem,
            filePath: $filePath,
            options: $options
        );
    }
}
