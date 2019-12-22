# monolyth/lodger
Codger recipes for Monolyth components

## Installation
Using Composer (recommended):

```sh
$ composer require --dev monolyth/lodger
```

## Usage
See [the Codger documentation](https://codger.monomelodies.nl) for general
usage.

## Included recipes

### `lodger/module`
Generates an entire Monolyth-style module. `--skip-SOMETHING` options are
provided to skip certain components, e.g. `--skip-model` to not generate a
model.

### `lodger/model`
Generate a model. By default properties are added according to a corresponding
database table; use `--skip-prefill` to skip this. Optionally specify
`--ornament` to make the model [Ornament-compatible](https://ornament.monomelodies.nl).

### `lodger/repository`
Generate a repository. Though not technically a part of Monolyth (since we
don't want to dictate how you handle your data), in practice Monolyth projects
tend to use repositories for all (database) storage operations - i.e., insert,
update, delete and select.

Generated repositories hold basic common methods such as `save`, `all` and
`find`.

### `lodger/view`
Generate a generic (page) view.

### `lodger/listing/view` and `lodger/listing/template`
Generate a view and template for a "listing page". This contains basically the
output of a repository's `all()` call, with clickable links.

### `lodger/detail/view` and `lodger/detail/template`
Generate a view and template for a "detail view". This is where you end up when
you click on one of the listing links ;)

### `lodger/controller`
Generate a generic CRUD controller. The generate `create`, `update` and `delete`
methods work out of the box with the corresponding repository, but of course do
nothing in the realm of permission checking.

### `lodger/form`
Generate a Formulaic form to go with the module.

### `lodger/sass`
Like repositories, SASS is of course not technically part of Monolyth - you can
write your CSS however you like. But it is the de facto standard these days.

