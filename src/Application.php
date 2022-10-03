<?php
namespace Surgiie\BladeCLI;

use Illuminate\Container\Container;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**The container instance.*/
    protected static $containerInstance = null;

    /**
     * The cli loogo art, shown on help menu.
     */
    private static $logo = <<<LOGO

    █▄▄ █   ▄▀█ █▀▄ █▀▀   █▀▀ █   █
    █▄█ █▄▄ █▀█ █▄▀ ██▄   █▄▄ █▄▄ █


LOGO;

    /**Get the container instance.*/
    public static function getContainerInstance()
    {
        if(is_null(static::$containerInstance)){
            return new Container;
        }
        return static::$containerInstance;
    }

    /**Set container instance.*/
    public static function setContainerInstance(Container $container)
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
