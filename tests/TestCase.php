<?php

namespace BladeCLI\Tests;

use Illuminate\Support\Str;
use BladeCLI\Commands\RenderCommand;
use Symfony\Component\Finder\Finder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get path to test templates.
     *
     * @return string
     */
    protected static function getTestTemplatesPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR."templates";
    }
    /**
     * Process the test files using the given callback.
     *
     * @param callable $callback
     * @return void
     */
    protected static function processTestFiles(callable $callback): void
    {
        $finder = new Finder;

        $files = $finder->in(realpath(__DIR__ . "/Files"))->files();

        foreach ($files as $file) {
            $class = str_replace('.php', '', Str::after($file->getPathName(), "tests".DIRECTORY_SEPARATOR));
            $class = "BladeCLI\Tests\\$class";
            call_user_func($callback, new $class);
        }
    }

    /**
     * Run render command.
     *
     * @param array $input
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    protected function renderCommand(array $input)
    {
        $application = new Application();
        $application->add(new RenderCommand());

        $command = $application->find('render');

        $commandTester = new CommandTester($command);

        $commandTester->execute(array_merge([
            'command' => $command->getName(),
        ], $input));

        return $commandTester;
    }
}
