<?php

namespace BladeCLI;

use BladeCLI\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use BladeCLI\Support\Exceptions\FileAlreadyExistsException;
use BladeCLI\Support\Exceptions\FileNotFoundException;

class BladeTest extends TestCase
{


    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        @mkdir($templateDir = static::getTestTemplatesPath());
        // write test files to test rendering
        $fs = new Filesystem;
        $fs->deleteDirectory($templateDir, preserve: true);

        static::processTestFiles(function($testFile){
            $templateDir = static::getTestTemplatesPath();

            $path = $templateDir.DIRECTORY_SEPARATOR.$testFile->filename();

            if(file_exists($path)){
                throw new FileAlreadyExistsException("Duplicate filename on test file: $path");
            }

            file_put_contents($path, $testFile->content());
        });
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass(): void
    {
        $fs = new Filesystem;
        $fs->deleteDirectory(static::getTestTemplatesPath());
    }

    /**
     * @test
     */
    public function file_must_exist()
    {
        $this->expectException(FileNotFoundException::class);

        $this->renderCommand(['file'=>"i-dont-exist.error"]);
    }
    /**
     * @test
     */
    public function it_can_render_files()
    {
        $templatesDir = static::getTestTemplatesPath();
        $this->renderCommand(['file'=>$templatesDir]);

        // todo
        $this->assertTrue(true);
    }
}
