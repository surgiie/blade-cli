<?php

namespace BladeCLI\Tests\Files;

use BladeCLI\Tests\Support\TestingFile;

class TestYamlFile extends TestingFile
{
    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'test_yaml.yaml';
    }

    /**
     * The content of the file.
     *
     * @return string
     */
    public function content(): string
    {
        return <<<EOL
        name: {{ \$name }}
        favorite_food: {{ \$favoriteFood }}
        pets:
            @foreach(\$dogs as \$dog)
            - {{ \$dog }}
            @endforeach
        contact_info:
            phone: 1234567890
            @if(\$includeAddress)
            street_info: 123 Lane.
            @endif
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
            '--name=Bob',
            '--favorite-food=Pizza',
            '--include-address',
            '--dogs=Rex',
            '--dogs=Charlie',
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
            'name' => 'Bob',
            'favorite-food' => 'Pizza',
            'include-address' => true,
            'dogs' => [
                'Rex',
                'Charlie',
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
        name: Bob
        favorite_food: Pizza
        pets:
            - Rex
            - Charlie
        contact_info:
            phone: 1234567890
            street_info: 123 Lane.
        EOL;
    }
}
