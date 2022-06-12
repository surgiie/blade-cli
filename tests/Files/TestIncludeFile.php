<?php

namespace BladeCLI\Tests\Files;

use BladeCLI\Tests\Support\TestingFile;

class TestIncludeFile extends TestingFile
{
    /**
     * Construct instance.
     */
    public function __construct()
    {
        $this->mainFile = new TestYamlFile();
    }

    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'test_include.yaml';
    }

    /**
     * The content of the file.
     *
     * @return string
     */
    public function content(): string
    {
        $name = $this->mainFile->filename();

        return <<<EOL
        @include('$name')
        EOL;
    }

    /**
     * The data options for rendering.
     *
     * @return array
     */
    public function options(): array
    {
        return $this->mainFile->options();
    }

    /**
     * The data to write to test loading data from json files.
     *
     * @return array
     */
    public function jsonFileData(): array
    {
        return $this->mainFile->jsonFileData();
    }

    /**
     * The expected rendered content of the file.
     *
     * @return string
     */
    public function expectedContent(): string
    {
        return $this->mainFile->expectedContent();
    }
}
