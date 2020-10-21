# Laravel Repository Pattern
Middleware for Eloquent Models

[![Latest Version](https://img.shields.io/packagist/v/royvoetman/laravel-repository-pattern.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-repository-pattern)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/royvoetman/laravel-repository-pattern.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-repository-pattern)

## Introduction 
This package provides a convenient mechanism of grouping data manipulation logic which is equivalent to Laravel's native HTTP middleware.
However, to prevent any confusion with HTTP middleware hereafter this mechanism will be referred to in its more general form called a `pipeline`.
In fact, Laravel provides its own Pipeline implementation which is used by middleware under the hood.

A pipeline is composed out of several different pipes and a so-called passable.
In the context of HTTP middleware the passable is the HTTP response object. 
Conversely, in the context of Eloquent the model-data is classified as the passable.
Typically, each pipe will filter or alter the passable that is send trough your pipeline.
As a result creating an easily extensible design pattern where each pipe is defined in its own self-contained class.
For example, this package provides a pipe that automatically hashes password before saving them to the database.
Thus, centralizing your password hashing logic and thereby removing responsibility from your other classes.

Additional pipes can be written to perform a variety of tasks besides modifying column values.
A translation pipe might save all translations for certain columns to a separate translations table.
A transaction pipe might run specific groups of queries in a database transaction.

There are a few pipes already included in this package, including pipes for password hashing and database transactions.
All of these default pipes will be elaborated up on in the Pipes section in addition to information on how to define your own pipes.

## Installation

```bash
composer require royvoetman/laravel-repository-pattern
```

## Repositories

### Defining repositories

First, create a class that extends the `RoyVoetman\Repositories\Repository` class.
Second, the repository should be made aware of what model it is associated to by equating the $model field to the fully qualified classname of the model 
Finally, pipes that should always run can be defined by setting the `$pipes` field.

```php
class BooksRepository extends Repository
{
    /**
     * @var string
     */
    protected $model = Book::class;

    /**
     * @var string[]
     */
    protected $pipes = [Translate::class];
}
```

#### Generator command

```bash
php artisan make:repository BooksRepository
```

### Inserting & Updating Models

`save(array $data, Model $model = null): ?Model`

#### Inserts

To create a new record in the database, create the associated repository for the related model, pass the model attributes as an associative array, then call the save method.

```php
$book = (new BooksRepository())->save([
  'name' => 'Laravel',
  'author' => 'Taylor Otwell'
]);
```

#### Updates

The save method may also be used to update models that already exist in the database.
To update a model, you should retrieve it, pass any model attributes you which to update in combination with the retrieve model, and then call the save method.

```php
$book = Book::find(1);

$updatedBook = (new BooksRepository())->save([
  'name' => 'Laravel!',
  'author' => 'Taylor Otwell'
], $book);
```

### Deleting models

`delete(Model $model): bool`

To delete a model, retrieve the model, pass the model to the repository, and then call the delete method.

```php
$book = Book::find(1);

(new BooksRepository())->delete($book);
```

## Pipes

### Defining Pipes

To create a new pipe, use the make:pipe generator command:
```bash
php artisan make:pipe HashPassword
```

This command will place a new HashPassword class within your app/Repositories/Pipes directory.
In this pipe, we will check if a password key has been defined. If so, the password will be hashed and override the plaintext password.

```php
class HashPassword
{
    public function handle($data, Closure $next)
    {
        if(Arr::has($data, 'password')) {
            $data['password'] = bcrypt($data['password']);
        }

        return $next($data);
    }
}
```

#### Before & After Pipes
Whether a pipe runs before or after the insertion/update/deletion of the model depends on the pipe itself.
For example, the following middleware would perform some task before any data manipulations are made persistent:

```php
class BeforePipe
{
    public function handle($data, Closure $next)
    {
        // Perform action

        return $next($data);
    }
}
```

However, this pipe would perform its task after the data manipulations are made persisted:

```php
class AfterPipe
{
    public function handle($data, Closure $next)
    {
        $model = $next($data);

        // Perform action

        return $model;
    }
}
```

### Pipe Groups
WIP

### Pipe Parameters
WIP

### Pipe Closures
WIP

## Transactions
This package provides a transaction pipe which can be used to run a certain pipeline into a database transaction.
For example, the `UsesTransaction` interface could be implemented by the repository to indicate that every pipeline should run in a transaction.

```php
class BooksRepository extends Repository implements UsesTransaction
{
    /**
     * @var string
     */
    protected $model = Book::class;

    /**
     * @var string[]
     */
    protected $pipes = [Translate::class];
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

### Specify attempts
WIP

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

