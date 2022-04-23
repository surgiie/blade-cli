### Blade CLI

Use Laravel blade engine as a CLI for rendering files from templates.


### Introduction

This package customizes/extends several classes used by the blade engine to be able to use simple features (i.e variable, @if, @includes, @foreach, etc.)
on files. That said, the more advanced features are the engine are out of scope of what this package is meant for and may not be supported.

### Installation

TODO
### Use

Best way to describe use is through example:

Given this file exists in your current directory (person.yml):

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

php blade render ./person.yml \
                --name="Bob"
                --relationship="Uncle"
                --favorite-food="Pizza"
                --include-address
```


#### Render Variables

Variables for your files are passed as options where the files reference variables in camel case versions of the options you pass.

As you may have noticed in the above example variables in the file to be rendered (person.yml) are defined in camel case while the options were not.
Since options are often defined as kebab/slug case but php does not support kebab variables, ALL variables in your template/render files should be
defined as camel case or an exception for undefined variables will be thrown. Since this package parses the command line options for dynamic options,
you have the flexibility to define options in kebab, camel, or snake case and it will be extracted automatically to a camel case variable at render time.

So for example the `--favorite-pizza` option could of been `--favorite_pizza` or `--favoritePizza`.