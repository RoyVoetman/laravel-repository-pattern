# Laravel Repository Pattern

## Introduction
An elegant interface to create, update and delete models with relational dependence.

The power of using this Repository pattern is that all database DML (`create`, `update`, `delete`) commands will be handled within transactions. This will prevent that multiple queries with relational dependence on each other do not result in inconsistent data. 

[![Latest Version](https://img.shields.io/packagist/v/royvoetman/laravel-repository-pattern.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-repository-pattern)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/royvoetman/laravel-repository-pattern.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-repository-pattern)

## Installation

```bash
composer require royvoetman/laravel-repository-pattern
```

## Getting started

Create a class that extends the `RoyVoetman\Repositories\Repository` class. The corresponding model should be type-hinted within the class constructor and passed to the parent constructor. The parent constructor accepts all classes that extend from the `Illuminate\Database\Eloquent\Model` class.

```php
namespace App\Repositories;

use App\Models\Book;
use RoyVoetman\Repositories\Repository;

/**
 * Class BooksRepository
 *
 * @package App\Repositories
 */
class BooksRepository extends Repository
{
    /**
     * BooksRepository constructor.
     *
     * @param Book $model
     */
    public function __construct(Book $model)
    {
        parent::__construct($model);
    }
}

```

### Generator command

**Usage**
```bash
php artisan make:repository BooksRepository
```

**Model option**
```bash
php artisan make:repository BooksRepository --model=Book
```

## Instantiation

The easiest way to instantiate a Repository is by resolving it from Laravel’s IoC container. This is because our type-hinted model will then be automatically injected. This can be done in two ways, type-hint the Repository in one of your controller methods or using a helper function such as `app()` or  `resolve()` .

**Type-hinting**

```php
public function store(BooksRepository $repo)
{
    $repo->save(request()->validated());
}
```
**Helper function**

```php
public function store()
{
    resolve(BooksRepository::class)->save(request()->validated());
}
```

**Using `new` keyword**

```php
public function store()
{
  $repo = new BooksRepository(new Book());

  $repo->save(request()->validated());
}
```


## Available repository methods

`save(array $data, Model $model = null): ?Model`

The provided `data` will be used to create a new database record. If a model is given as a second argument, the model will be updated instead of creating a new one. This will also result in updating the corresponding database record.

If the method executed successfully the new or updated model will be returned. In the case of an unhandled Exception during the creation or modification of a Model all actions will be rolled back and `null` will be returned.

```php
// Create a DB record
$book = $bookRepo->save([
  'name' => 'Laravel 6',
  'author' => 'Taylor Otwell'
]);
```

```php
// Update a DB record
$book = Book::find(1);

$updatedBook = $bookRepo->save([
  'name' => 'Laravel 6.1',
  'author' => 'Taylor Otwell'
], $book);
```

`delete(Model $model): bool`

An attempt will be made to delete the provided `model` from the database, if the model was deleted successfully `true` will be returned. In the case of an unhandled Exception, all actions will be rolled back and `false` will be returned.

```php
// Deleting a DB record
$book = Book::find(1);

$bookRepo->delete($book);
```



## Example usage in Controller

```php
namespace App\Http\Controllers;

use App\Repositories\BooksRepository;

/**
 * Class BookController
 *
 * @package App\Http\Controllers
 */
class BookController extends Controller
{
    /**
     * @var BooksRepository
     */
    protected $repository;
    
    /**
     * BookController constructor.
     *
     * @param  BooksRepository  $repository
     */
    public function __construct(BooksRepository $repository)
    {
        parent::__construct();
        
        $this->repository = $repository;
    }
    
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $this->repository->save(request()->validated());
        
        ...

        return redirect()->route('book.index');
    }
    
    /**
     * @param  Book  $book
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Book $book): RedirectResponse
    {
        $this->repository->save(request()->validated());
        
        ...

        return redirect()->route('book.index');
    }

    
    /**
     * @param  Book  $book
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->repository->delete($book);

        ...
        
        return redirect()->route('book.index');
    }
}
```



## Handling relational data

The `save` and `delete` methods are just transaction wrapping functions for the `protected` methods `handleSave` and `handleDelete`. When there is the need to overwrite the default behavior for saving and deleting models the `handle*` methods should be overwritten. This can be very useful if you want to update/delete relational data within the same method.

For this example we have an `orders` table with many `order_lines`:

```php
namespace App\Repositories;

use RoyVoetman\Repositories\Repository;

/**
 * Class OrdersRepository
 *
 * @package App\Repositories
 */
class OrdersRepository extends Repository
{
    ...
    
    /**
     * The save method calls this method within a database transaction.
     * So when creating one of the order lines fails it won't result in
     * an order with an incorrect amount of lines, it will simply rollback 
     * all previous actions.
     */
    protected function handleSave(array $data, Model $order = null): Model
    {
      	// Create order with the 'lines' key from the $data array
        $order = parent::handleSave(Arr: :except($data, 'lines'), $order);
        
      	// Add order lines if 'lines' key is defined in $data array
        if (Arr::has($data, 'lines')) {
            $order->lines()->delete();
          
            foreach ($data['lines'] as $line) {
            	$order->lines()->create($line);
            }
        }

        return $order;
    }
    
    /**
     * The delete method calls this method within a database transaction.
     * So when deleting one of the order lines fails it won't result in
     * data corruption, it will simply rollback all previous actions.
     */
    protected function handleDelete(Model $order)
    {
      	// Delete all order lines
        $order->lines()->delete();
        
      	// Delete order itself
        parent::handleDelete($order);
    }
}
```



## Deleting  / Manipulating / Adding data before its saved

When you are working with passwords you want to hash them before you store them in the database. You can do this by overriding the `save` method.

 ```php
namespace App\Repositories;

/**
 * Class UsersRepository
 *
 * @package App\Repositories
 */
class UsersRepository extends Repository
{
    ...
      
    /**
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(array $data, Model $model = null): Model
    {      
      	if(Arr::has($data, 'password')) {
        		// Delete confirm password from data array
      		$data = Arr::forget($data, 'password_confirm');
      
      		// Manipulating password in data array
			$data['password'] = bcrypt($data['password']; 	
        }
                                   
       	// Adding language to data array
         $data['language'] = App::getLocale();
				
        return parent::save($data, $model);
    }

}
 ```

> If you only want to trigger specific logic when a model is created or updated you can check if the `$model` parameter equals `null`.

## Alert messages

When an error occurs when starting a transaction or during the executing of the `save` or `delete` method a message will be flashed to the session under the "warning" key. This automatically works together with the [Laravel Flash Alert](https://github.com/RoyVoetman/Laravel-Flash-Alerts) package.

To change these error messages or to add specific translations you can run the following command

```bash
php artisan vendor:publish --provider="RoyVoetman\Repositories\RepositoriesServiceProvider" 
```
This will place the overwritable translations under resources/lang/vendor/laravel-repository-pattern

### Custom error handling logic
When you want to handle errors differently you can overwrite the `*errorMsg` fields in your Repository. If they are equal to a string they will be flashed to the session. When they are a callable (e.g. a Closure) they will be evaluated when the error occurs and nothing will be automatically flashed to the session.

| Field                     | Default  |
| ------------------------- | -------- |
| transactionErrorMsg       | `__('alerts.could not start database transaction')` |
| saveErrorMsg              | `__('alerts.database error')` |
| deleteErrorMsg            | `__('alerts.could not delete')` |

```php
namespace App\Repositories;

use App\Models\Book;
use RoyVoetman\Repositories\Repository;

/**
 * Class BooksRepository
 *
 * @package App\Repositories
 */
class BooksRepository extends Repository
{
    /**
     * BooksRepository constructor.
     *
     * @param Book $model
     */
    public function __construct(Book $model)
    {
        parent::__construct($model);
	
	$this->saveErrorMsg = function () {
		// Your custom error handeling logic
	}
    }
}

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

