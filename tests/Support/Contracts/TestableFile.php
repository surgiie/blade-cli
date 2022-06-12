<?php

namespace BladeCLI\Tests\Support\Contracts;

interface TestableFile
{
    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string;

    /**
     * The content of the file.
     *
     * @return string
     */
    public function content(): string;

    /**
     * The data options for rendering.
     *
     * @return array
     */
    public function options(): array;

    /**
     * The expected rendered content of the file.
     *
     * @return string
     */
    public function expectedContent(): string;

    /**
     * The json file data to test loading data
     * from json files. Will write a json file
     * next to the testing file.
     *
     * @return array
     */
    public function jsonFileData(): array;
}
