### Blade CLI

![tests](https://github.com/surgiie/blade-cli/actions/workflows/tests.yml/badge.svg)

Compile and save files from the command line using Laravel's blade engine.
### Installation

You may install the binary via composer globally:

`composer global require surgiie/blade-cli`

Then be sure the global composer packages path is executable:

```bash
# may vary on systems:
export PATH=~/.config/composer/vendor/bin:$PATH
```

### Installing Entire App

You are also welcome to install the entire app if you dont want to use the standalone binary via composer global install by downloading a specific tag version release from releases and make available in `$PATH`:

```
# in ~/.bashrc or equivalent
PATH=/usr/local/bin/blade-cli:$PATH
```

Install dependencies:

`composer install`

Confirm is executable:

```
blade
```

### CLI Completion

You may optionally source the provided completion script for terminal completion:

```bash
source /path/to/cli/root/completion
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
                --name="Bob" \
                --relationship="Uncle" \
                --favorite-food="Pizza" \
                --include-address
```

This will render and save the file to the same directory as a file named `person.rendered.yml`


### Custom Filename

All files will get saved to the same directory as the file being rendered as `<filename>.rendered.<extension>` or simply `<filename>.rendered`. This is to avoid overwriting the file you are rendering. If you wish to save the file as a custom file name or change the directory, use the `--save-to` option to specify a file path to write the file to:

```
blade render ./person.yml \
            ...
            --save-to="/home/bob/custom-name.yml"

```

**Note** - The blade class will attempt to automatically ensure the parent directories exist if it can write them otherwise an error is thrown due to lack of permissions.

### Variable Data

There are 3 options for passing variable data to your files being rendered, in precedence order from **lowest to highest** :


1. Using json files via the `--from-json` option to pass a path to a json file. This maybe passed multiple times to load from many files.

2. Using env files via the `--from-env` option to pass a path a `.env` file. This maybe passed multiple times to load from many files.

3. Lastly as you saw in the earlier example above, through arbitrary command line options to the `render` command. `--example-var=value`


#### Variable Naming Convention

Command line options, env and json file keys can be defined in any naming convention you prefer, but your actual variable reference **MUST** be camel case. This is because php doesnt support kebab cased variables and since this is often the format for command line options, all variables will automatically get converted to data using camel case. For example, if you pass an option or define a variable name in your files in any of these formats: `favorite-food`, `favoriteFood`, or `favorite_food`, the variable for that option will be referenced
as `$favoriteFood` in your files.

#### Variable Types

These are the current supported way to pass variables for different types/purposes:

##### String/Single Value Variables

Use single option key/value format for passing variables for single string values:

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
blade render ./person.yml \
                --name="Bob" \
                --relationship="Uncle" \
                --favorite-food="Pizza" \
                --include-address \
                --force # force overwrite person.rendered.yml if it already exists.

```


### Dry Run/Show Rendered Contents

If you would like to output the contents of a rendered file to your terminal and not actually save the file, you may add the `--dry-run` flag when rendering a single file:

`blade render example.yaml --some-var=example --dry-run`

This will echo/output the rendered contents of `example.yaml` only.


### Processing an entire directory of files

You may also pass the path to a directory instead of a single file. This might be useful if you like to group template files in a directory and want to render them all with a single command:

`blade render ./templates --save-dir="/home/bob/templates" --some-data=foo`

**Note** This will prompt you for confirmation, you may skip confirmation by adding the `--force` flag.

**Note** When rendering an entire directory the `--save-dir` option is **required** so that the cli exports all rendered files to a separate directory than the one being processed. The directory the files get saved in will mirror the directory structure of the directory being processed.  In the above example `/home/bob/templates` will have a directory structure that matches `./templates`.
### Direct Use/Manually Rendering

If you wish to use the api directly, you may utilize the Blade class directly in your apps:

```php

use Surgiie\BladeCLI\Blade;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;

$blade = new Blade(
    container: new Container,
    filesystem: new Filesystem,
    filePath: '/path/to/file/to/render',
    options: [
        'force'=> true, // force overwrite existing rendered file
        'save-to'=>'save-to' // optional file path to save file as.
    ]
);

// render and save the file with this data/vars
$contents = $blade->render([
    'var'=>'example'
]);

// you may prevent the file from being saved, by passing false to the 2nd argument of the render method
// this is useful if you wish to process the contents of the rendered file yourself and do specific custom tasks.
$contents = $blade->render(
    ['var'=>'example'],
    false
);

```
### Contribute

Contributions are always welcome in the following manner:

-   Issue Tracker
-   Pull Requests
-   Discussions

### License

The project is licensed under the MIT license.
