### Blade CLI

![tests](https://github.com/surgiie/blade-cli/actions/workflows/tests.yml/badge.svg)

Use Laravel's blade engine as a CLI for rendering files.


### Introduction

This package customizes and extends several of the `Illuminate\View` classes used by the blade engine to be able to use 
simple blade features/directives (i.e `@if`, `@include`, `@foreach`, etc.) on files. That said, the more advanced 
features of the engine are out of scope of what this package was meant for and may not be supported, keep that in mind if you are 
attempting more advanced usages.

### Installation

Download specific tag version release from releases and make available in $PATH:

```
# in ~/.bashrc or equivalent
PATH=/usr/local/bin/blade-cli:$PATH
```

Install dependencies:
```
composer install
```

Confirm is executable:
```
blade 
```

Or if you want to use the api directly as a package, you can install with composer:

`composer require surgiie/blade-cli`

and use the class directly


```php

use BladeCLI\Blade;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;

$blade = new Blade(
    container: new Container,
    filesystem: new Filesystem,
    filePath: '/path/to/file/to/render',
    options: [
        'force'=> true, // force overwrite existing rendered file
        'save-directory'=>'save-to-dir' // optional directory to save rendered file to. Default is current directory.
    ]
);

// render the file with this data/vars
$blade->render([
    'var'=>'example'
]);

```

### Use
Lets work through an example, given this file exists in your current directory (person.yml):

```yaml
name: {{ $name }}
relationship: {{ $relationship }}
favorite_food: {{ $favoriteFood }}
@if($includeAddress)
address: "123 example lane"
@endif
```

You may render that file as follows:

```bash

blade render ./person.yml \
                --name="Bob"
                --relationship="Uncle"
                --favorite-food="Pizza"
                --include-address
```

This will render and save the file to the same directory as `person.rendered.yml`


### Custom Save Directory

All files will get saved to the current directory as `<filename>.rendered.<extension>` or simply `<filename>.rendered` if the file does not have an extension.
If you wish to save to a custom directory use the `--save-directory='custom-dir'` option to specify a directory to write the file to:

```
php blade render ./person.yml \
                --name="Bob"
                --relationship="Uncle"
                --favorite-food="Pizza"
                --include-address
                --save-directory="rendered-files/"

```


The blade class will attempt to automatically ensure the directory exists if it can write to it. In the above example the the result of `./person.yml` would get written
to `./rendered-files/person.rendered.yml`.

### Variable Data

There are 2 options for passing variable data to your files being rendered:

1. As you saw in the earlier example above, the first method is through options to the `render` command. `--example-var=value`

2. Using json files via the `--from-json` option to pass a path to a json file. This maybe passed multiple times to load from many files. Note that options take precedence over the data loaded from json files.


#### Variable Naming Convention

Options or keys in your json file can be defined in any naming convention you prefer, but your actual variable reference should be camel case.
This is because php doesnt support kebab cased variables which is often the format for command line options. That said, since camel case is usually standard, that is the format we decided to stick with. Your options will automatically get converted to data using camel case. To clarify a bit:

Either one of these option formats can be used `--favorite-food`, `--favoriteFood`, `--favorite_food` to reference a `{{ $favoriteFood }}` variable in your file.


#### Variable Types

These are the current supported way to pass variables for different types/purposes:

##### String/Single Value Variables

Use simple option key/value format for passing variables for single/string values:

`--foo=bar --bar=baz`

##### Array Value Variables

For array variables, just pass the option more than once:

`--names=Steve --names=Ricky --names=Bob`

##### True Boolean Value Variables

For boolean true variables, just pass the option with no value:

`--should-do-thing`

**Note** Since variable options are dynamic the "negate/false" options are not supported. Instead do something like this in your files `{{ $shouldDoSomething ?? false }}` to default
to false and then use true options to "negate" the value.

### Force write

If you try to render a file that already exists an exception will be raised, you may consider force write via the `--force` flag.

```
php blade render ./person.yml \
                --name="Bob"
                --relationship="Uncle"
                --favorite-food="Pizza"
                --include-address
                --force # force overwrite person.rendered.yml if it already exists.

```

### Processing an entire directory of files

You may also pass the path to a directory instead of a single file. This might be useful if you like to group template files in a directory and

want to render them all with a single command:

`php blade render templates/ --some-data=foo`

**Note** This will prompt you for confirmation.

#### Force process directory

You may skip confirmation of rendering a directory's files with the `--force` flag:

`php blade render templates/ --some-data=foo --force`


#### Custom Directory:

By default, files will get saved to the current directory the file being rendered is in, you may specify
a custom directory to save rendered files in with the same `--save-directory` option specified earlier:


`php blade render templates/ --some-data=foo --save-directory="/home/bob/templates/"`

**Note** When using a custom directory to save to, the directory specified will have files saved to mirror the directory being processed. In this example `/home/bob/files/` will have a directory structure that matches `templates/`.
