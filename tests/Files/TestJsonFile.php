<?php

namespace Surgiie\BladeCLI\Tests\Files;

use Surgiie\BladeCLI\Tests\Support\TestingFile;

class TestJsonFile extends TestingFile
{
    /**
     * The filename.
     */
    public function filename(): string
    {
        return 'test_json.json';
    }

    /**
     * The content of the file.
     */
    public function content(): string
    {
        return <<<EOL
        {
            "{{\$key}}": "{{ \$value }}"
        }
        EOL;
    }

    /**
     * The data options for rendering.
     */
    public function options(): array
    {
        return [
            '--key=name',
            '--value=Zoro',
        ];
    }

    /**
     * The data to write to test loading data from env|json files.
     */
    public function fileData(): array
    {
        return [
            'key' => 'name',
            'value' => 'Zoro',
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
        {
            "name": "Zoro"
        }
        EOL;
    }
}
