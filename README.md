# Blade CLI
The Blade CLI allows you to compile and save any textual files from the command line using Laravel's Blade engine.

![tests](https://github.com/surgiie/blade-cli/actions/workflows/tests.yml/badge.svg)

## Installation
To install the binary, use composer globally:

`composer global require surgiie/blade-cli`

## Use
As an example, let's say you have a file named `person.yml` in your current directory with the following content:

```yaml
name: {{ $name }}
relationship: {{ $relationship }}
favorite_food: {{ $favoriteFood }}
@if($includeAddress)
address: 123 example lane
@endif
```
You can render this file using the following command:

```bash
blade render ./person.yml \
                --name="Bob" \
                --relationship="Uncle" \
                --favorite-food="Pizza" \
                --include-address

```
This will render the file and save it in the same directory with the name `person.rendered.yml` with the following contents:

```yaml
name: Bob
relationship: Uncle
favorite_food: Pizza
address: 123 example lane

```


## Rendering With Docker:

If you don't have or want to install php, you can run render files using the provided script which will run the cli render command in a temporary docker container and use volumes to mount the neccessary files and then sync them back to your host machine:


```bash
cd /tmp

wget https://raw.githubusercontent.com/surgiie/blade-cli/master/docker

chmod +x ./docker

mv ./docker /usr/local/bin/blade

blade <path> --var="example"
```



## Custom Filename
By default, all files will be saved to the same directory as the file being rendered with the name `<filename>.rendered.<extension>` or simply `<filename>.rendered`, to prevent overwriting the original file. To use a custom file name or change the directory, use the `--save-to` option to specify a file path:

```bash
blade render ./person.yml \
            ...
            --save-to="/home/bob/custom-name.yml"
```
**Note**: The cli will automatically create the necessary parent directories if it has permission, otherwise an error will be thrown.

## Variable Data
There are three options for passing variable data to your files being rendered, in order of precedence:

- Use YAML files with the `--from-yaml` option and pass a path to the file. This option can be used multiple times to load from multiple files.
- Use JSON files with the `--from-json` option and pass a path to the file. This option can be used multiple times to load from multiple files.
- Use env files with the `--from-env` option and pass a path to the .env file. This option can be used multiple times to load from multiple files.
- Use arbitrary command line options with the render command, like `--example-var=value`.


## Variable Naming Convention

Your env, YAML, and JSON file keys can be defined in any naming convention, but the actual variable references MUST be in camel case. This is because PHP does not support kebab case variables and since this is the format used in command line options, all variables will automatically be converted to camel case. For example, if you pass an option or define a variable name in your files in any of these formats: `favorite-food`, `favoriteFood`, or `favorite_food`, the variable for that option should be referenced as `$favoriteFood` in your files.

### Command Line Variable Types
The following types of variables are currently supported:

- String/Single Value Variables: Use a single option key/value format, e.g. `--foo=bar --bar=baz`
- Array Value Variables: Pass the option multiple times, e.g. `--names=Steve --names=Ricky --names=Bob`
- True Boolean Value Variables: Pass the option with no value, e.g. `--should-do-thing`

**Note**: Since variable options are dynamic, "negate/false" options are not supported. Instead, use something like `{{ $shouldDoSomething ?? false }}` in your files to default to false and use true options to "negate" the value.

## Force Write
If you try to render a file that already exists, an exception will be raised. To force overwrite an existing file, use the --force flag:

```bash
blade render ./person.yml \
                --name="Bob" \
                --relationship="Uncle" \
                --favorite-food="Pizza" \
                --include-address \
                --force # force overwrite person.rendered.yml if it already exists.
```
## Dry Run/Show Rendered Contents
To view the contents of a rendered file without saving it, use the --dry-run flag when rendering a single file:

`blade render example.yaml --some-var=example --dry-run`

This will display the contents of example.yaml on the terminal without saving it.

## Processing an entire directory of files
You can also pass a directory path instead of a single file when running the command. This can be useful when you want to render multiple template files at once.

`blade render ./templates --save-dir="/home/bob/templates" --some-data=foo`

**Note**: This command will prompt you for confirmation. To skip confirmation, add the `--force` flag.

**Note**: When rendering an entire directory, the `--save-dir` option is required to export all rendered files to a separate directory. The directory structure of the directory being processed will be mirrored in the directory where the files are saved. In the above example, `/home/bob/templates` will have the same directory structure as `./templates`.


## Custom Compiled Directory

When compiling a file down to plain php, the compiled file is stored by default in `/tmp/.compiled`, if you wish to use a custom directory for these files, you may use the `--compiled-path` option:

`blade render myfile --var=foo --compiled-path="/custom/directory"`

When clearing the directory, this will also be required:

`blade clear --compiled-path="/custom/directory"`

Or you can persist the path via the `BLADE_CLI_COMPILED_PATH` environment variable if you dont wish to pass it to every command call.

### Contribute

Contributions are always welcome in the following manner:

-   Issue Tracker
-   Pull Requests
-   Discussions

### License

The project is licensed under the MIT license.
