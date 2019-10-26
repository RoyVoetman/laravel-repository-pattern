# Laravel Extras

## Introduction
This Laravel package provide developers with useful class preset, artisan commands, and helpers that are not included in Laravel by default.

[![Latest Version](https://img.shields.io/packagist/v/royvoetman/laravel-extras.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-extras)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/royvoetman/laravel-extras.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-extras)

### Features

| Feature                                                     | Status                 |
| ----------------------------------------------------------- | ---------------------- |
| Repository pattern                                          | Shipped                |
| Enhanced Form Requests                                      | Shipped                |
| Preparing Request Data before it is passed to the Validator | Shipped                |
| View and Route prefixes in controllers                      | Still to be documented |
| Artisan commands to create repositories                     | To do                  |



## Installation

```bash
composer require royvoetman/laravel-extras
```

If you are on Laravel 5.4 or earlier, then register the service provider in app.php

```php
'providers' => [
    // ...
    RoyVoetman\Extras\ExtrasServiceProvider::class,
]
```

If you are on Laravel 5.5 or higher, composer will have registered the provider automatically for you.

## Repository pattern
The power of using this Repository pattern is that all database DML (`create`, `update`, `delete`) commands will be handled within transactions. So that multiple queries with relational dependence on each other do not result in inconsistent data. 

### Getting started

Create a class that extends the Repository class. You should typehint the corresponding Model class within the classes constructor and pass it to the parent constructor. The parent constructor accepts all classes extended from laravel’s `Illuminate\Database\Eloquent\Model` class.

```php
namespace App\Repositories;

use App\Models\Book;
use RoyVoetman\Extras\Repository\Repository;

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


### Instantiation

The easiest way to instantiate a Repository is by resolving it from Laravel’s IoC container. This is because our type-hinted model will then be automatically injected. This can be done in two ways, typehint the Repository in one of your controller method or using a helper function such as `app()` or  `resolve()` .

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



### Available repository methods

`save(array $data, Model $model = null): ?Model`

The provided `data` will be used to create a new database record. If a model is given as a second argument, the model will be updated instead of creating a new one. This will also result in updating the corresponding database record.

If the method executed successfully the new or updated model will be returned. In the case of an unhandled Exception during the creation or update of a Model all actions will be rolled back and `null` will be returned.

`delete(Model $model): bool`

An attempt will be made to delete the provided `model` from the database. If the model was deleted successfully `true` will be returned. In the case of an unhandled Exception all actions will be rolled back and `false` will be returned.



### Example

```php
namespace App\Http\Controllers;

use App\Repositories\BooksRepository;

/**
 * Class BookController
 *
 * @package App\Http\Controllers
 */
class BookController extends Controller implements ResponsePrefixes
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



### Handeling relational data

The `save` and `delete` methods are just transaction wrapping functions for the `protected` methods `handleSave` and `handleDelete`. When there is the need to overwrite the default behavior for saving and deleting models the `handle*` methods should be overwritten. This can be every useful if you want to update/delete relational data within the same method.

Lets take for example an `orders` table with many `order_lines`:

```php
namespace App\Repositories;

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



### Deleting  / Manipulating / Adding data before its saved

When you are working with passwords for example you want to hash them before you store them in the database. You can do this by overriding the `save` method.

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
      	// Delete confirm password from data array
      	$data = Arr::forget($data, 'password_confirm');
      
      	// Manipulating password in data array
      	$data['password'] = bcrypt($data['password'];
                                   
       	// Adding language to data array
         $data['language'] = App::getLocale();
				
        return parent::save($data, $model);
    }

}
 ```


## Enhanced Form Requests

### Auto discover Form Requests attributes

```php
use RoyVoetman\Extras\Http\Request;

class StoreBook extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255|min:1',
            'reference_number' => 'nullable|string|max:255|min:1'
        ];
    }
    
    /**
     * @return string
     */
    public function getLanguageKey(): string
    {
        return 'books.labels';
    }
}
```

`resources/lang/en/books.php`
```php
return [
    ...

    'labels' => [
        'name'             => 'Name',
        'reference_number' => 'Reference number'
    ]
```


### Preparing Request Data before it is passed to the Validator

If for example the submitted data is sanitized or encoded you can decoded it before it is passed to the validation rules.

```php
namespace App\Http\Requests;

use RoyVoetman\Extras\Http\Request

class StoreBook extends Request
{    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'my_data' => 'required|array'
        ];
    }
    
    public function prepareRequestData()
    {
        $data = $this->all();
      
      	$data['my_data'] = $this->decode($data['encoded_data']);
        
        $this->request->replace($data);
    }
  
    private decode()
    {
      	// Your decode logic
    }
}

```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.



## Contributing

Contributions are **welcome** and will be fully **credited**. We accept contributions via Pull Requests on [Github](https://github.com/RoyVoetman/Laravel-Extras).

### Pull Requests

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).
- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.
- **Create feature branches** - Don't ask us to pull from your master branch.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.



## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
