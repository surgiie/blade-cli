<?php

namespace Surgiie\BladeCLI\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Surgiie\BladeCLI\Commands\RenderCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

abstract class TestCase extends BaseTestCase
{
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
