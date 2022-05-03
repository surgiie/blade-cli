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

}