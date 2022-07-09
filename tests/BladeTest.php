<?php

namespace Surgiie\BladeCLI\Tests;

use Surgiie\BladeCLI\Blade;
use Surgiie\BladeCLI\Tests\TestCase;
use Surgiie\BladeCLI\Tests\Files\TestJsonFile;
use Surgiie\BladeCLI\Tests\Files\TestTextFile;
use Surgiie\BladeCLI\Tests\Files\TestYamlFile;
use Surgiie\BladeCLI\Tests\Files\TestNginxFile;
use Surgiie\BladeCLI\Tests\Files\TestIncludeFile;
use Surgiie\BladeCLI\Support\Exceptions\FileNotFoundException;
use Surgiie\BladeCLI\Support\Exceptions\FileAlreadyExistsException;
use Surgiie\BladeCLI\Support\Exceptions\UndefinedVariableException;

class BladeTest extends TestCase
{
    /**
     * Tests cleanup.
     */
    public function tearDown(): void
    {
        Blade::tearDown();
    }

    /**
     * Fake blade function call.
     *
     * @param string $directory
     * @return void
     */
    public function fake(string $directory = "testing")
    {
        return Blade::fake(__DIR__."/$directory");
    }

    /**
     * @test
     */
    public function file_must_exist()
    {
        $this->fake();

        $testFile = new TestNginxFile();

        $this->expectException(FileNotFoundException::class);

        $this->renderCommand(['file' => Blade::testPath($testFile->filename())]);
    }

    /**
     * @test
     */
    public function it_can_render_files()
    {
        $this->fake();

        $testFile = new TestYamlFile();

        Blade::putTestFile('example.yaml',  $testFile->content());
        $this->renderCommand(
            ['file' => Blade::testPath('example.yaml')],
            $testFile->options()
        );

        Blade::assertRendered("example.rendered.yaml", $testFile->expectedContent());
    }

    /**
    * @test
    */
    public function it_throws_exception_if_rendered_file_already_exists()
    {
        $this->fake();

        $testFile = new TestYamlFile();

        Blade::putTestFile('example.yaml',  $testFile->content());
        Blade::putTestFile('example.rendered.yaml',  $testFile->expectedContent());
        $this->expectException(FileAlreadyExistsException::class);

        $this->renderCommand(
            ['file' => Blade::testPath('example.yaml')],
            $testFile->options()
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_on_undefined_variables()
    {
        $this->fake();

        $testFile = new TestTextFile();

        Blade::putTestFile($testFile->filename(),  $testFile->content());

        $this->expectException(UndefinedVariableException::class);

        $this->renderCommand(['file' => Blade::testPath($testFile->filename())]);
    }

    /**
     * @test
     */
    public function it_can_load_data_from_json_files()
    {
        $this->fake();

        $testFile = new TestJsonFile();
        $filename = $testFile->filename();

        $info = pathinfo($filename);
        $basename = $info['filename'];
        $ext = $info['extension'];

        Blade::putTestFile($filename,  $testFile->content());

        Blade::putTestFile($jsonFile = $testFile->filename().".json",  json_encode($testFile->jsonFileData()));

        $this->renderCommand(
            ['file' => Blade::testPath($testFile->filename())],
            ['--from-json=' . Blade::testPath($jsonFile)]
        );

        Blade::assertRendered($basename.".rendered".".$ext", $testFile->expectedContent());
    }

    /**
     * @test
     */
    public function it_can_render_files_in_custom_directory()
    {
        $this->fake();

        $testFile = new TestIncludeFile();

        Blade::putTestFile($testFile->filename(),  $testFile->content());
        Blade::putTestFile($testFile->getIncludeFile()->filename(),  $testFile->getIncludeFile()->content());

        $this->renderCommand(
            ['file' => Blade::testPath($testFile->filename())],
            array_merge($testFile->getIncludeFile()->options(), ['--save-directory=custom/'])
        );

        Blade::assertRendered("custom/".$testFile->filename(), $testFile->expectedContent());
    }

    /**
     * @test
     */
    public function it_can_render_all_files_in_directory()
    {
        $this->fake();

        $jsonFile = new TestJsonFile();
        $yamlFile = new TestYamlFile();

        Blade::putTestFile("templates/".$jsonFile->filename(),  $jsonFile->content());
        Blade::putTestFile("templates/".$yamlFile->filename(),  $yamlFile->content());

        $this->renderCommand(
            ['file' => Blade::testPath('templates')],
            array_merge($jsonFile->options(), $yamlFile->options(), ['--save-directory=rendered/', '--force'])
        );

        Blade::assertRendered("rendered/".$jsonFile->filename(), $jsonFile->expectedContent());
        Blade::assertRendered("rendered/".$yamlFile->filename(), $yamlFile->expectedContent());
    }
}
