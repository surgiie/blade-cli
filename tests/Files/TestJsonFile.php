<?php

namespace BladeCLI\Tests\Files;

use BladeCLI\Tests\Support\Contracts\TestableFile;

class TestJsonFile implements TestableFile
{
    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'test_json.yaml';
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
     * The data for rendering.
     *
     * @return array
     */
    public function data(): array
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
