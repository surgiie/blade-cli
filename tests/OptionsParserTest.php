<?php

namespace Surgiie\BladeCLI\Tests;

use InvalidArgumentException;
use Surgiie\BladeCLI\Support\OptionsParser;
use Symfony\Component\Console\Input\InputOption;

class OptionsParserTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_parse_dynamic_argv_options_for_input_option_registration()
    {
        $parser = new OptionsParser([
            "--name=bob",
            "--foo=bar",
            "--bar=1",
            "--bar=2",
            "--bar=3",
            "--force",
        ]);

        $parsed = $parser->parse();

        $this->assertEquals([
            "name" => InputOption::VALUE_REQUIRED,
            "foo" => InputOption::VALUE_REQUIRED,
            "bar" => InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            "force" => InputOption::VALUE_NONE,
        ], $parsed);
    }

    /**
     * @test
     */
    public function it_can_parse_dynamic_argv_option_values()
    {
        $parser = new OptionsParser([
            "--name=bob",
            "--foo=bar",
            "--bar=1",
            "--bar=2",
            "--bar=3",
            "--force",
        ]);

        $parsed = $parser->parse(mode: OptionsParser::VALUE_MODE);

        $this->assertEquals([
            "name" => 'bob',
            "foo" => 'bar',
            "bar" => ['1', '2', '3'],
            "force" => true,
        ], $parsed);
    }

    /**
     * @test
     */
    public function it_throws_exception_during_dynamic_options_parsing_if_invalid_token()
    {
        $parser = new OptionsParser([
            "invalid",
            "--name=foo",
        ]);

        $this->expectException(InvalidArgumentException::class);

        $parser->parse();
    }
}
