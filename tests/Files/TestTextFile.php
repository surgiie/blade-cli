<?php

namespace Surgiie\BladeCLI\Tests\Files;

use Surgiie\BladeCLI\Tests\Support\TestingFile;

class TestTextFile extends TestingFile
{
    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'test_text.txt';
    }

    /**
     * The content of the file.
     *
     * @return string
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
     * The data to write to test loading data from json files.
     *
     * @return array
     */
    public function jsonFileData(): array
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
     *
     * @return string
     */
    public function expectedContent(): string
    {
        return <<<EOL
        -   Uncle Bob
        -   Uncle Billy
        EOL;
    }
}
