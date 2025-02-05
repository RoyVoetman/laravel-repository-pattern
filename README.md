<img src="https://www.cs-library.com/images/packages/repository-pattern.svg" width="100%">

Middleware for Eloquent Models

[![Latest Version](https://img.shields.io/packagist/v/royvoetman/laravel-repository-pattern.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-repository-pattern)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/royvoetman/laravel-repository-pattern.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-repository-pattern)

## Table of Contents
  * [Introduction](#introduction)
  * [Installation](#installation)
  * [Repositories](#repositories)
    * [Defining repositories](#defining-repositories)
    * [Inserting & Updating Models](#inserting--updating-models)
    * [Deleting Models](#deleting-models)
  * [Pipes](#pipes)
    * [Defining Pipes](#defining-pipes)
    * [Before & After Pipes](#before--after-pipes)
    * [Using Pipes](#using-pipes)
    * [Pipe Groups](#pipe-groups)
  * [Transactions](#transactions)
  * [Pipe Parameters](#pipe-parameters)
  * [Changelog](#changelog)

## Introduction 
This package provides a convenient mechanism for grouping data manipulation logic, which is equivalent to Laravel's native HTTP middleware.
However, to prevent any confusion with HTTP middleware hereafter this mechanism will be referred to in its more general form called a `pipeline`.
In fact, Laravel provides its own Pipeline implementation which is used by middleware under the hood.

A pipeline is a design pattern that composes several different classes (`pipes`) and applies them consecutively. All pipes receive a so-called passable and result in a so-called returnable.
In the context of HTTP middleware, the passable is the HTTP request object and the returnable is the HTTP response object. 
Conversely, in the context of a repository, the `model-data array` is classified as the passable and the resulting Eloquent model object is the returnable.
Typically, each pipe will filter or alter the passable that is sent through the pipeline.
As a result, creating an easily extensible architecture where each pipe concerns itself with one task.
For example, this package provides a pipe that automatically hashes passwords before saving them to the database.
Thus, centralizing your password hashing logic and thereby removing responsibility from your other classes.

Additional pipes can be written to perform a variety of tasks besides modifying column values.
A translation pipe might save all translations for certain columns to a separate translations table.
A transaction pipe might run specific groups of queries in a database transaction.
There are a few pipes already included in this package, including pipes for password hashing and database transactions.
All of these default pipes will be elaborated upon, along with information on how to define your own pipes.

## Installation

```bash
composer require royvoetman/laravel-repository-pattern
```

## Repositories

### Defining repositories

First, create a class that extends the `RoyVoetman\Repositories\Repository` class.
Second, the repository should be made aware of what model it is associated with by equating the `$model` field to the fully qualified class name of the model.
Finally, pipes can be defined by stating them in the `$pipes` field.

```php
class BooksRepository extends Repository
{
    protected string $model = Book::class;

    protected array $pipes = [
        'save' => [Translate::class],
        'delete' => [DeleteTranslations::class]
    ];
}
```

#### Generator command

```bash
php artisan make:repository BooksRepository
```

### Inserting & Updating Models

`save(array $data, Model $model = null): ?Model`

#### Inserts

To create a new database record, instantiate the associated repository, pass the model attributes as an associative array, then call the save method.

```php
$book = (new BooksRepository())->save([
  'name' => 'Laravel',
  'author' => 'Taylor Otwell'
]);
```

#### Updates

The save method may also be used to update models that already exist in the database.
To update a model, you should retrieve it, pass any model attributes you which to update in combination with the retrieved model, and then call the save method.

```php
$book = Book::find(1);

$updatedBook = (new BooksRepository())->save([
  'name' => 'Laravel!',
  'author' => 'Taylor Otwell'
], $book);
```

### Deleting Models

`delete(Model $model): bool`

To delete a model, retrieve the model, pass the model to the repository, and then call the delete method.

```php
$book = Book::find(1);

(new BooksRepository())->delete($book);
```

## Pipes

### Defining Pipes

To create a new pipe, use the `make:pipe` generator command:
```bash
php artisan make:pipe HashPassword
```

This command will place a new `HashPassword` class within your `app/Repositories/Pipes` directory.
In this pipe, we will check if a password key has been defined. If so, the password will be hashed and replaced with the plain text password.

```php
class HashPassword
{
    public function handle($data, Closure $next): Model
    {
        if(Arr::has($data, 'password')) {
            $data['password'] = bcrypt($data['password']);
        }

        return $next($data);
    }
}
```

However pipes that are applied to a **delete** action receive an Eloquent Model as the first parameter instead of a `$data` array with model-data.
To create a delete pipe, add the `--delete` option to the generator command.
```bash
php artisan make:pipe RemoveBookRelations --delete
```

```php
class RemoveBookRelations
{
    public function handle(Model $book, Closure $next)
    {
        $book->author()->delete();
        $book->reviews()->delete();

        return $next($book);
    }
}
```

### Before & After Pipes
Whether a pipe runs before or after the insertion/update/deletion of the model depends on the pipe itself.
For example, the following pipe would perform some task before any data manipulations are made persistent:

```php
class BeforePipe
{
    public function handle($data, Closure $next)
    {
        // Perform actions on the model-data
        // e.g. hashing passwords

        return $next($data);
    }
}
```

However, this pipe would perform its task after the data manipulations are made persisted:

```php
class AfterPipe
{
    public function handle($data, Closure $next): Model
    {
        $model = $next($data);

        // Perform actions on Eloquent model
        // e.g. saving relationships

        return $model;
    }
}
```

### Using Pipes
This package defines the following actions that can be used in the `$pipes` array of your repository. 
These arrays will automatically be applied when the corresponding actions occur:

| Action | Applied when |
|---------|---|
| `create`| A new model is created |
| `update`| An existing model is updated  |
| `save`  | A model is being created or updated |
| `delete`| A model is deleted  |

```php
class BooksRepository extends Repository
{
    protected array $pipes = [
        'create' => [...],
        'update' => [...],
        'save' => [...],
        'delete' => [...]
    ];
    
    ...
}
```

Alternatively pipes can be defined at runtime by using the `with` method.

```php
(new BooksRepository())->with(Translate::class)->create([
    ...
]);
```

### Pipe Groups
Sometimes you may want to group several pipes under a single key to make them easier to apply. You may do this using the `$pipeGroups` field in your repository. For example, you may want to apply special logic when saving a VIP user as opposed to a regular user:

```php
class UsersRepository extends Repository
{
    protected string $model = Book::class;

    protected array $pipeGroups = [
        'vip' => [
            AddVipPermissions::class,
            EnrollToVipChannel::class
        ]
    ];
}
```

You may then apply the group by calling the `withGroup` method.
```php
$user = (new UsersRepository())->withGroup('vip')->save([
  'name' => 'Roy Voetman',
  'email' => 'info@example.com',
  ...
]);
```

## Transactions
This package provides a transaction pipe which can be used to run a certain pipeline in a database transaction.
For example, the `UsesTransaction` interface could be implemented by the repository to indicate that every pipeline should run in a transaction.

```php
class BooksRepository extends Repository implements UsesTransaction
{
    protected string $model = Book::class;
}
```

By implementing `UsesTransaction` the case in which inserting a record or saving the translations raises an exception will not cause data inconsistencies.
In fact, when an exception is raised the transaction will be rolled back.  

Furthermore, the `transaction()` method could be used to specify that only the current pipeline should be run inside a transaction.

```php
$book = $books->transaction()->save([
  'name' => 'Laravel',
  'author' => 'Taylor Otwell'
]);
```

> Caution: the `Transaction` pipe can also be used by adding it to the `$pipes` field. However, since the pipes are run consecutively it should be the first pipe in the array. The techniques discussed above automatically prepend the pipe to the beginning of the pipe stack.

## Pipe Parameters
Pipes can also receive additional parameters. 
For example, if want to apply the transaction pipe with a specific number of retries, you could pass an integer indicating the retries as an additional argument.

To clarify, the transaction pipe is defined as follows:
```php
class Transaction
{
    public function handle($passable, \Closure $next, int $attempts)
    {
        return DB::transaction(fn () => $next($passable), $attempts);
    }
}
```

Pipe parameters may be specified when defining the `$pipes` array by separating the class name and parameters with a :. 
Multiple parameters should be delimited by commas:

```php
class BooksRepository extends Repository
{
    protected array $pipes = [
        'save' => [
            '\RoyVoetman\Repositories\Pipes\Transaction:3'
        ],
    ];
    
    ...
}
```

However, pipe parameters can also be defined at runtime by using the `with` method on the repository.

```php
(new BooksRepository())
    ->with('\RoyVoetman\Repositories\Pipes\Transaction:3')
    ->create([
        ...
    ]
);
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Contributions are **welcome** and will be fully **credited**. We accept contributions via Pull Requests on [Github](https://github.com/RoyVoetman/laravel-repository-pattern).

### Pull Requests

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).
- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.
- **Create feature branches** - Don't ask us to pull from your master branch.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

