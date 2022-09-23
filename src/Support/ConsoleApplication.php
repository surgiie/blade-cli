<?php
namespace Surgiie\BladeCLI\Support;

use Illuminate\Container\Container;
use Symfony\Component\Console\Application as BaseApplication;

class ConsoleApplication extends BaseApplication
{
    /**The container instance.*/
    protected static $containerInstance = null;
    /**
     * @var string
     */
    private static $logo = <<<LOGO

    █▄▄ █   ▄▀█ █▀▄ █▀▀   █▀▀ █   █
    █▄█ █▄▄ █▀█ █▄▀ ██▄   █▄▄ █▄▄ █


LOGO;

    /**Get the container instance.*/
    public static function getInstance()
    {
        if(is_null(static::$containerInstance)){
            return new Container;
        }
        return static::$containerInstance;
    }

    /**Set container instance.*/
    public static function setInstance(Container $container)
    {
        static::$containerInstance = $container;
    }


    /**
     * Get the help menu.
     */
    public function getHelp(): string
    {
        return static::$logo . parent::getHelp();
    }
}
