<?php

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in(__DIR__);
/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// expect()->extend('toBeOne', function () {
//     return $this->toBe(1);
// });

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**The directory we use to put test files.*/
function blade_cli_test_path(string $path = '')
{
    return rtrim(storage_path('framework/testing'.'/mock'.'/'.$path));
}

/**Cleanup steps.*/
function blade_cli_test_cleanup()
{
    @mkdir($mockDir = blade_cli_test_path(), recursive: true);

    $fs = new Filesystem;

    $fs->deleteDirectory($mockDir, preserve: true);
}

/**
 * Write a test file to testing directory.
 */
function put_blade_cli_test_file(string $file, string $contents)
{
    $file = trim($file, '/');

    $path = blade_cli_test_path('/'.$file);

    @mkdir(dirname($path), recursive: true);

    file_put_contents($path, $contents);
}
