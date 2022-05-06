<?php

namespace BladeCLI\Tests;

use Illuminate\Support\Str;
use BladeCLI\Commands\RenderCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Application;
use BladeCLI\Support\Concerns\NormalizesPaths;
use BladeCLI\Support\OptionsParser;
use PHPUnit\Framework\TestCase as BaseTestCase;
use BladeCLI\Tests\Support\Contracts\TestableFile;
use Symfony\Component\Console\Tester\CommandTester;

abstract class TestCase extends BaseTestCase
{
    use NormalizesPaths;
    /**
     * Get path to test templates.
     *
     * @return string
     */
    protected static function getTestTemplatesPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . "templates";
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
            $class = str_replace(['.php', '/'], ['', '\\'], Str::after($file->getPathName(), "tests" . DIRECTORY_SEPARATOR));
            $class = "BladeCLI\Tests\\$class";
            call_user_func($callback, new $class);
        }
    }


    /**
     * Make a absolute file path to the template/test files.
     *
     * @param string $path
     * @return string
     */
    protected function makeAbsoluteTestFilePath(string $path)
    {
        $templateDir = static::getTestTemplatesPath();

        return $this->normalizePath($templateDir . DIRECTORY_SEPARATOR . $path);
    }


    /**
     * Execute render command via command tester.
     *
     * @param array $input
     * @param array $options
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    protected function renderCommand(array $input, array $options = [])
    {
        // specify we options to use statically for testing
        RenderCommand::useOptions($options);

        $application = new Application();

        $application->add(new RenderCommand());

        $command = $application->find('render');

        $tester = new CommandTester($command);
        $tester->execute(
            array_merge([
                'command' => $command->getName(),
            ], $input),
        );

        return $tester;
    }
}
