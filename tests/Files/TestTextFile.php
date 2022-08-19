<?php

namespace Surgiie\BladeCLI\Tests\Files;

use Surgiie\BladeCLI\Tests\Support\TestingFile;

class TestTextFile extends TestingFile
{
    /**
     * The filename.
     */
    public function filename(): string
    {
        return 'test_text.txt';
    }

    /**
     * The content of the file.
     */
    public function content(): string
    {
        return <<<EOL
        @foreach (\$names as \$name)
            @if (\str_starts_with(\$name, "Uncle"))
        -   {{\$name}}
            @endif
        @endforeach
        EOL;
    }

    /**
     * The data options for rendering.
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
     * The data to write to test loading data from env|json files.
     */
    public function fileData(): array
    {
        return [
            'names' => [
                'Uncle Bob',
                'Uncle Billy',
                'Boe',
            ],
        ];
    }

    /**
     * The expected rendered content of the file.
     */
    public function expectedContent(): string
    {
        return <<<EOL
        -   Uncle Bob
        -   Uncle Billy
        EOL;
    }
}
