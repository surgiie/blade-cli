<?php

namespace BladeCLI;

use BladeCLI\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use BladeCLI\Tests\Files\TestJsonFile;
use BladeCLI\Support\Exceptions\FileNotFoundException;
use BladeCLI\Support\Exceptions\FileAlreadyExistsException;
use BladeCLI\Support\Exceptions\UndefinedVariableException;

class BladeTest extends TestCase
{

    /**
     * The relative (to tests dir) directories being tested against.
     *
     * @var array
     */
    protected array $testDirectories = [
        'default',
        'custom'
    ];
    /**
     * Tests setup
     */
    public function setUp(): void
    {
        // write test files to test rendering
        $fs = new Filesystem;

        foreach ($this->testDirectories as $dir) {
            @mkdir($templateDir = $this->makeTestFilePath($dir));

            $fs->deleteDirectory($templateDir, preserve: true);

            $this->iterateTestFileClasses(function ($testFile) use ($dir) {
                $templateDir =  $this->makeTestFilePath($dir);

                $path = $templateDir . DIRECTORY_SEPARATOR . $testFile->filename();
                if (file_exists($path)) {
                    throw new FileAlreadyExistsException("Duplicate filename on test file: $path");
                }

                file_put_contents($path, $testFile->content());
            });
        }
    }

    /**
     * Tests cleanup.
     */
    public function tearDown(): void
    {
        $fs = new Filesystem;
        foreach ($this->testDirectories as $dir) {
            $fs->deleteDirectory($this->makeTestFilePath($dir));
        }
    }

    /**
     * @test
     */
    public function file_must_exist()
    {
        $this->expectException(FileNotFoundException::class);

        $this->renderCommand(['file' => "i-dont-exist.error"]);
    }

    /**
     * @test
     */
    public function it_throws_exception_on_undefined_variables()
    {
        $testFile = new TestJsonFile;

        $this->expectException(UndefinedVariableException::class);

        $path = "default/" . $testFile->filename();

        $this->renderCommand(['file' => $this->makeTestFilePath($path)]);
    }


    /**
     * @test
     */
    public function it_can_load_data_from_json_files()
    {
        $this->iterateTestFileClasses(function ($testFile) {
            $path = "default/" . $testFile->filename();

            $json = json_encode($testFile->jsonFileData());

            file_put_contents($jsonFilePath = $this->makeTestFilePath($path . ".json"), $json);

            $this->renderCommand(
                ['file' => $this->makeTestFilePath($path)],
                ['--from-json=' . $jsonFilePath]
            );

            $this->assertFileWasRendered($testFile);
        });
    }


    /**
     * @test
     */
    public function it_can_render_files()
    {
        $this->iterateTestFileClasses(function ($testFile) {
            $path = "default/" . $testFile->filename();

            $this->renderCommand(
                ['file' => $this->makeTestFilePath($path)],
                $testFile->options()
            );

            $this->assertFileWasRendered($testFile);
        });
    }


    /**
     * @test
     */
    public function it_can_render_files_in_custom_directory()
    {
        $this->iterateTestFileClasses(function ($testFile) {
            $path = "default/" . $testFile->filename();
            $this->renderCommand(
                ['file' => $this->makeTestFilePath($path)],
                array_merge(['--save-directory=' . $this->makeTestFilePath("custom"), '--force'], $testFile->options())
            );

            $this->assertFileWasRendered($testFile, "custom");
        });
    }
}
