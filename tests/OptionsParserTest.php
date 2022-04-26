<?php

namespace BladeCLI;

use BladeCLI\Support\ArgvOptionsParser;
use BladeCLI\Tests\TestCase;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class OptionsParserTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_parse_dynamic_argv_options_for_input_option_registration()
    {
        $parser = new ArgvOptionsParser([
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
    public function it_throws_exception_during_dynamic_options_parsing_if_invalid_token()
    {
        $parser = new ArgvOptionsParser([
            "invalid",
            "--name=foo",
        ]);

        $this->expectException(InvalidArgumentException::class);

        $parser->parse();
    }
}
