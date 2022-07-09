<?php

namespace Surgiie\BladeCLI\Tests\Files;

use Surgiie\BladeCLI\Tests\Support\TestingFile;

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
     * Get the main file being included.
     *
     * @return \Surgiie\BladeCLI\Tests\Support\Contracts\TestableFile
     */
    public function getIncludeFile()
    {
        return $this->mainFile;
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
