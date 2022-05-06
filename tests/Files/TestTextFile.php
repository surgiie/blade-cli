<?php

namespace BladeCLI\Tests\Files;

use BladeCLI\Tests\Support\Contracts\TestableFile;

class TestTextFile implements TestableFile
{
    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'test_text.yaml';
    }

    /**
     * The content of the file.
     *
     * @return string
     */
    public function content(): string
    {
        return <<<EOL
        Uncles:
            @foreach (\$names as \$name)
                @if (\str_starts_with(\$name, "Uncle"))
            -   {{\$name}}
                @endif
            @endforeach
        EOL;
    }

    /**
     * The data options for rendering.
     *
     * @return array
     */
    public function options(): array
    {
        return [
            '--names=Uncle Bob',
            '--names=Uncle Billy',
            '--names=Boe',
        ];
    }

    /**
     * The expected rendered content of the file.
     *
     * @return string
     */
    public function expectedContent(): string
    {
        return <<<EOL
        Uncles:
            -   Uncle Bob
            -   Uncle Billy
        EOL;
    }
}
