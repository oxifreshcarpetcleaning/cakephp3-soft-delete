# Alternative CakeSoftDelete Plugin for CakePHP

[![Build status](https://api.travis-ci.org/PGBI/cakephp3-soft-delete.png?branch=master)](https://travis-ci.org/PGBI/cakephp3-soft-delete)

## Purpose

This Cakephp plugin enables you to make your models soft deletable by marking an active
flag on the model "false".

Based on the original version by PGBI: https://github.com/PGBI/cakephp3-soft-delete which
uses `deleted` dates instead of active flags.

## Requirements

This plugins has been developed for cakephp 3.x.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

Update your composer file to include this plugin:

```
composer require oxifreshcarpetcleaning/cakephp3-soft-delete "dev-master"
```

## Configuration

1. Load the plugin:

```
// In /config/bootstrap.php
Plugin::load('SoftDelete');
```

2. Make a model soft deleteable by using SoftDelete trait:

```
// in src/Model/Table/UsersTable.php
...
use SoftDelete\Model\Table\SoftDeleteTrait;

class UsersTable extends Table
{
    use SoftDeleteTrait;
    ...
```

3. Your soft deletable model database table should have a field called `deleted` of type DateTime with NULL as default value. If you want to customise this field you can declare the field in your Table class.

```php
// in src/Model/Table/UsersTable.php
...
use SoftDelete\Model\Table\SoftDeleteTrait;

class UsersTable extends Table
{
    use SoftDeleteTrait;

    protected $softDeleteField = 'deleted_date';
    ...
```

## Use

### Soft deleting records

`delete` function will now soft delete records by setting an `active` flag field from `1` (active) to `0` (inactive).

### Finding records

`find`, `get` or dynamic finders (such as `findById`) will only return active records.
To also return soft deleted records, `$options` must contain `'withInactive'`. Example:

```
// in src/Model/Table/UsersTable.php
$nonSoftDeletedRecords = $this->find('all');
$allRecords            = $this->find('all', ['withInactive']);
```

### Hard deleting records

To hard delete a single entity:
```
// in src/Model/Table/UsersTable.php
$user = $this->get($userId);
$success = $this->hardDelete($user);
```

## Soft deleting & associations

Associations are correctly handled by SoftDelete plugin.

1. Soft deletion will be cascaded to related models as usual. If related models also use SoftDelete Trait, they will be soft deleted.
2. Soft deletes records will be excluded from counter cache.

## Un-deleting (reactivating) records
To hard delete a single entity:
```
// in src/Model/Table/UsersTable.php
$user = $this->get($userId);
$success = $this->activate($user);
```
