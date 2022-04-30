<?php

namespace BladeCLI\Tests\Support\Contracts;

interface TestableFile
{
    /**
     * The content of the file.
     *
     * @return string
     */
    public function content(): string;

    /**
     * The expected rendered content of the file.
     *
     * @return string
     */
    public function expectedContent(): string;

    /**
     * The data for rendering.
     *
     * @return array
     */
    public function data(): array;


    /**
     * The save location.
     *
     * @return array
     */
    public function saveLocation(): array;
}