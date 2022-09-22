<?php
namespace Surgiie\BladeCLI\Support;

use Symfony\Component\Console\Application as BaseApplication;

class ConsoleApplication extends BaseApplication
{
    /**
     * @var string
     */
    private static $logo = <<<LOGO

    █▄▄ █   ▄▀█ █▀▄ █▀▀   █▀▀ █   █
    █▄█ █▄▄ █▀█ █▄▀ ██▄   █▄▄ █▄▄ █


LOGO;



    /**
     * Get the help menu.
     */
    public function getHelp(): string
    {
        return static::$logo . parent::getHelp();
    }
}
