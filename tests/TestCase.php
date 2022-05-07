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
     * Make a file/directory path relative to tests directory
     * for testing file rendering locations.
     *
     * @param string $dirOrFile
     * @return string
     */
    protected function makeTestFilePath(string $dirOrFile = "default"): string
    {
        return $this->normalizePath(__DIR__ . DIRECTORY_SEPARATOR . $dirOrFile);
    }

    /**
     * Iterate the test file classes using the given callback.
     *
     * @param callable $callback
     * @return void
     */
    protected function iterateTestFileClasses(callable $callback): void
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
     * Assert we rendered a file.
     *
     * @param TestableFile $file
     * @param string $directory - relative to tests.
     * @return void
     */
    protected function assertFileWasRendered(TestableFile $file, string $directory = "default"): void
    {
        $parts = explode('.', $file->filename());

        $extension = $parts[1] ?? '';

        $renderedFilePath = $this->makeTestFilePath(
            $directory . DIRECTORY_SEPARATOR . $parts[0] . ".rendered" . ($extension ? ".$extension" : ""),
        );

        // assert we have a rendered file
        $this->assertTrue(file_exists($renderedFilePath));
        // and that it's expected content matches what was rendered.
        $this->assertEquals($file->expectedContent(), file_get_contents($renderedFilePath));
    }

    /**
     * Execute render command via command tester.
     *
     * @param array $input
     * @param array $options
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    protected function renderCommand(array $input, array $options = []): CommandTester
    {
        // specify options to use statically for testing
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
