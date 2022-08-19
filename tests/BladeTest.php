<?php

namespace Surgiie\BladeCLI\Tests;

use BadMethodCallException;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
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
     */
    public function fake(string $directory = "mock"): void
    {
        Blade::fake(__DIR__ . "/$directory");
    }

    /**
     * @test
     */
    public function file_must_exist()
    {
        $this->fake();

        $testFile = new TestNginxFile();

        $this->expectException(FileNotFoundException::class);

        $this->renderCommand(['file' => $testFile->filename()]);
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
            ['file' => 'example.yaml'],
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
            ['file' => 'example.yaml'],
            $testFile->options()
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_if_path_is_not_set()
    {
        $this->fake();

        $blade = new Blade(
            container: new Container,
            filesystem: new Filesystem
        );

        $this->expectException(BadMethodCallException::class);

        $blade->render([]);
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

        $this->renderCommand(['file' => $testFile->filename()]);
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

        Blade::putTestFile($jsonFile = $testFile->filename() . ".json",  json_encode($testFile->fileData()));

        $this->renderCommand(
            ['file' => $testFile->filename()],
            ['--from-json=' . $jsonFile]
        );

        Blade::assertRendered($basename . ".rendered" . ".$ext", $testFile->expectedContent());
    }

    /**
     * @test
     */
    public function it_can_load_data_from_env_files()
    {
        $this->fake();

        $testFile = new TestJsonFile();
        $filename = $testFile->filename();

        $info = pathinfo($filename);
        $basename = $info['filename'];
        $ext = $info['extension'];

        Blade::putTestFile($filename,  $testFile->content());

        $env = [];

        foreach ($testFile->fileData() as $k => $v) {
            $k = strtoupper($k);
            $env[] = "$k=$v";
        }
        Blade::putTestFile($envFile = $testFile->filename() . ".env",  implode("\n", $env));

        $this->renderCommand(
            ['file' => $testFile->filename()],
            ['--from-env=' . $envFile]
        );

        Blade::assertRendered($basename . ".rendered" . ".$ext", $testFile->expectedContent());
    }

    /**
     * @test
     */
    public function it_can_use_custom_save_file_path()
    {
        $this->fake();

        $testFile = new TestJsonFile();

        Blade::putTestFile($testFile->filename(),  $testFile->content());
        Blade::putTestFile($testFile->filename(),  $testFile->content());

        $this->renderCommand(
            ['file' => $testFile->filename()],
            array_merge($testFile->options(), ['--save-as=/custom/custom-name.txt'])
        );

        Blade::assertRendered("custom/custom-name.txt", $testFile->expectedContent());
    }

    /**
     * @test
     */
    public function it_can_render_all_files_in_directory()
    {
        $this->fake();

        $jsonFile = new TestJsonFile();
        $yamlFile = new TestYamlFile();

        Blade::putTestFile("templates/" . $jsonFile->filename(),  $jsonFile->content());
        Blade::putTestFile("templates/" . $yamlFile->filename(),  $yamlFile->content());

        $this->renderCommand(
            ['file' => 'templates'],
            array_merge($jsonFile->options(), $yamlFile->options(), ['--save-dir=rendered/', '--force'])
        );

        Blade::assertRendered("rendered/" . $jsonFile->filename(), $jsonFile->expectedContent());
        Blade::assertRendered("rendered/" . $yamlFile->filename(), $yamlFile->expectedContent());
    }
    /**
     * @test
     */
    public function save_dir_is_required_when_rendering_an_entire_directory()
    {
        $this->fake();

        $jsonFile = new TestJsonFile();
        $yamlFile = new TestYamlFile();

        Blade::putTestFile("templates/" . $jsonFile->filename(),  $jsonFile->content());
        Blade::putTestFile("templates/" . $yamlFile->filename(),  $yamlFile->content());

        $exception_thrown = false;
        try {
            $this->renderCommand(
                ['file' => 'templates'],
                array_merge($jsonFile->options(), $yamlFile->options(), ['--force'])
            );
        } catch (BadMethodCallException $e) {
            $exception_thrown = true;
        }

        $this->assertSame(true, $exception_thrown);
        Blade::assertNotRendered("rendered/" . $jsonFile->filename());
        Blade::assertNotRendered("rendered/" . $yamlFile->filename());
    }
}
