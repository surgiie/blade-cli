<?php

beforeEach(fn () => blade_cli_test_cleanup());

it('throws error when file doesnt exist', function () {
    $path = blade_cli_test_path('idontexist');

    $this->artisan('render', ['path' => $path])
        ->expectsOutputToContain("The file or directory path 'storage/framework/testing/mock/idontexist' does not exist.")
        ->assertExitCode(1);
});

it('throws error when there are undefined variables', function () {
    $path = blade_cli_test_path('example.yaml');

    put_blade_cli_test_file('example.yaml', <<<'EOL'
    name: {{ $name }}
    EOL);

    $cmd = $this->artisan('render', ['path' => $path]);

    $cmd->expectsOutputToContain('Undefined variable $name on line 1.')
        ->assertExitCode(1);
});

it('can render file', function () {
    $path = blade_cli_test_path('example.yaml');

    put_blade_cli_test_file('example.yaml', <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    pets:
        @foreach($dogs as $dog)
        - {{ $dog }}
        @endforeach
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--dogs' => [
            'Rex',
            'Charlie',
        ],
    ]);

    $renderedPath = blade_cli_test_path('example.rendered.yaml');

    expect(is_file($renderedPath))->toBeTrue();

    expect(file_get_contents($renderedPath))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    pets:
        - Rex
        - Charlie
    EOL);
});

it('can render file with custom save path', function () {
    $path = blade_cli_test_path('example.yaml');

    put_blade_cli_test_file('example.yaml', <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    pets:
        @foreach($dogs as $dog)
        - {{ $dog }}
        @endforeach
    EOL);

    $renderedPath = blade_cli_test_path('custom-file');

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--save-to' => $renderedPath,
        '--dogs' => [
            'Rex',
            'Charlie',
        ],
    ]);

    expect(is_file($renderedPath))->toBeTrue();

    expect(file_get_contents($renderedPath))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    pets:
        - Rex
        - Charlie
    EOL);
});

it('save directory is required when rendering directory', function () {
    mkdir(blade_cli_test_path('directory'));

    put_blade_cli_test_file('directory/example.yaml', <<<'EOL'
    favorite_food: {{ $favoriteFood }}
    EOL);

    $this->artisan('render', [
        'path' => blade_cli_test_path('directory'),
        '--favorite-food' => 'Pizza',
        '--force' => true,
    ])->expectsOutputToContain('The --save-to directory option is required when rendering all files in a directory');
});

it('save directory must not be directory being processed', function () {
    mkdir(blade_cli_test_path('directory'));

    put_blade_cli_test_file('directory/example.yaml', <<<'EOL'
    favorite_food: {{ $favoriteFood }}
    EOL);

    $this->artisan('render', [
        'path' => blade_cli_test_path('directory'),
        '--save-to' => blade_cli_test_path('directory'),
        '--favorite-food' => 'Pizza',
        '--force' => true,
    ])->expectsOutputToContain('The path being processed is also the --save-to directory, use a different save directory.');
});

it('can render files in directory', function () {
    mkdir(blade_cli_test_path('directory'));

    put_blade_cli_test_file('directory/example.yaml', <<<'EOL'
    favorite_food: {{ $favoriteFood }}
    EOL);

    put_blade_cli_test_file('directory/nested/example2.yaml', <<<'EOL'
    name: {{ $name }}
    EOL);

    $this->artisan('render', [
        'path' => blade_cli_test_path('directory'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--force' => true,
        '--save-to' => blade_cli_test_path('save-dir'),
    ]);

    $path = blade_cli_test_path('save-dir/example.yaml');
    expect(is_file($path))->toBeTrue();
    expect(file_get_contents($path))->toBe(<<<'EOL'
    favorite_food: Pizza
    EOL);

    $path = blade_cli_test_path('save-dir/nested/example2.yaml');
    expect(is_file($path))->toBeTrue();
    expect(file_get_contents($path))->toBe(<<<'EOL'
    name: Bob
    EOL);
});

it('exits if file already exits', function () {
    $path = blade_cli_test_path('example.yaml');

    put_blade_cli_test_file('example.yaml', $content = <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    pets:
        @foreach($dogs as $dog)
        - {{ $dog }}
        @endforeach
    EOL);

    put_blade_cli_test_file('example.rendered.yaml', $content);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--dogs' => [
            'Rex',
            'Charlie',
        ],
    ])->expectsOutputToContain("The rendered file 'storage/framework/testing/mock/example.rendered.yaml' already exists")
        ->assertExitCode(1);
});

it('can load variable data from json files', function () {
    $path = blade_cli_test_path('example.yaml');

    put_blade_cli_test_file('example.yaml', <<<'EOL'
    name: {{ $name }}
    EOL);

    put_blade_cli_test_file('vars.json', <<<'EOL'
    {
        "name": "Doug"
    }
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--from-json' => blade_cli_test_path('vars.json'),
    ])->assertExitCode(0);

    expect(file_get_contents(blade_cli_test_path('example.rendered.yaml')))->toBe(<<<'EOL'
    name: Doug
    EOL);
});

it('can load variable data from env files', function () {
    $path = blade_cli_test_path('example.yaml');

    put_blade_cli_test_file('example.yaml', <<<'EOL'
    name: {{ $name }}
    EOL);

    put_blade_cli_test_file('.env.vars', <<<'EOL'
    NAME=Doug
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--from-env' => blade_cli_test_path('.env.vars'),
    ])->assertExitCode(0);

    expect(file_get_contents(blade_cli_test_path('example.rendered.yaml')))->toBe(<<<'EOL'
    name: Doug
    EOL);
});
