<?php

namespace BladeCLI\Tests\Files;

use BladeCLI\Tests\Support\TestingFile;

class TestJsonFile extends TestingFile
{
    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'test_json.json';
    }

    /**
     * The content of the file.
     *
     * @return string
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
     *
     * @return array
     */
    public function options(): array
    {
        return [
            '--key=name',
            '--value=Zoro',
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
