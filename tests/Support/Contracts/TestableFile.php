<?php

namespace Surgiie\BladeCLI\Tests\Support\Contracts;

interface TestableFile
{
    /**
     * The filename.
     */
    public function filename(): string;

    /**
     * The content of the file.
     */
    public function content(): string;

    /**
     * The data options for rendering.
     */
    public function options(): array;

    /**
     * The expected rendered content of the file.
     */
    public function expectedContent(): string;

    /**
     * The env file data to test loading data from json files.
     *
     * @return array
     */
    public function fileData(): array;
}
