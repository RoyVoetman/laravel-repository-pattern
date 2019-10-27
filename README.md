# Laravel Extras

## Introduction
This Laravel package provide developers with useful class preset, artisan commands, and helpers that are not included in Laravel by default.

[![Latest Version](https://img.shields.io/packagist/v/royvoetman/laravel-extras.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-extras)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/royvoetman/laravel-extras.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-extras)

| Feature                                                      | Status  |
| ------------------------------------------------------------ | ------- |
| [Repository pattern](https://github.com/RoyVoetman/Laravel-Extras#repository-pattern) | Shipped |
| [Enhanced Form Requests](https://github.com/RoyVoetman/Laravel-Extras#enhanced-form-requests) | Shipped |
| [View and Route prefixes in Controllers](https://github.com/RoyVoetman/Laravel-Extras#view-and-route-prefixes-in-controllers) | Shipped |
| Artisan commands to create repositories                      | To do   |



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
The power of using this Repository pattern is that all database DML (`create`, `update`, `delete`) commands will be handled within transactions. This will prevent that multiple queries with relational dependence on each other do not result in inconsistent data. 

### Getting started

Create a class that extends the `RoyVoetman\Extras\Repository\Repository` class. The corresponding model should be type-hinted within the class constructor and passed to the parent constructor. The parent constructor accepts all classes that extend from the `Illuminate\Database\Eloquent\Model` class.

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



### Available repository methods

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



### Example usage in Controller

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



### Handling relational data

The `save` and `delete` methods are just transaction wrapping functions for the `protected` methods `handleSave` and `handleDelete`. When there is the need to overwrite the default behavior for saving and deleting models the `handle*` methods should be overwritten. This can be very useful if you want to update/delete relational data within the same method.

For this example we have an `orders` table with many `order_lines`:

```php
namespace App\Repositories;

use RoyVoetman\Extras\Repository\Repository;

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



## Enhanced Form Requests

### Getting started

Create a class that extends the `RoyVoetman\Extras\Http\Request` class. This class has two new methods compared to the `Illuminate\Foundation\Http\FormRequest` class. There is a `getLanguageKey` method which has to return the language path of where all the translations of the rule attributes are stored. Second, there is a `prepareRequestData` method which is called before the data is passed through the validator.

### Auto discover Form Requests attributes

When the `getLanguageKey` method is implemented there is no need to define an `attributes` method. All attribute translations will automatically be resolved from the given language path. 

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

When for example submitted data is sanitised or encoded you can decode it before it is passed to the validator.

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
          	// Validate decoded data
            'decode_data' => 'required|array',
          	// You still have access to the encoded data as well
          	'encoded_data' => 'required'
        ];
    }
    
    public function prepareRequestData()
    {
        $data = $this->all();
      
      	$data['decode_data'] = $this->decode($data['encoded_data']);
        
        $this->request->replace($data);
    }
  
    private decode()
    {
      	// Your decode logic
    }
}

```



## View and Route prefixes in Controllers

In a resource controller, it is a common pattern to have all the associated views in the same folder, and the same goes for the location of your routes. With this feature, you can define a prefix which is automatically appended to your view-name or route-name. 

### View prefixes

When you want to use View prefixes your controller will have to implement the `RoyVoetman\Extras\Contracts\ViewPrefix` interface. This interface requires you to add a `viewPrefix` method that returns a `string`.

Second your Controller must include the `RoyVoetman\Extras\Http\Traits\CreatesViews` trait. This trait includes the `view(string $view)` method to the controller which handles the prefixing for us. The best practice is to include this trait in your `BaseController` . This method checks if the `CreatesViews` interface is implemented, if this is not the case this method will behave the same as the `view()` global helper function.

```php
namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use RoyVoetman\Extras\Http\Traits\CreatesViews;

/**
 * Class BookController
 *
 * @package App\Http\Controllers
 */
class BookController extends Controller implements CreatesViews
{
    /**
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create(): Renderable
    {
      	// Return view: `authorized.books.create`
        return $this->view('create');
    }
  
    /**
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Book $book): Renderable
    {
      	// You can have chain methods like `with()` just like 
      	// you normally would when using `return view()`
        return $this->view('edit')->with('book', $book);
    }
  
    /**
     * @return string
     */
    public function viewPrefix(): string
    {
        return 'authorized.books';
    }
}
```

### Route prefixes

Route prefixing works the same as View Prefixing except for the following: 

The Controller must implement the `RoyVoetman\Extras\Contracts\RoutePrefix` interface and must include the `RoyVoetman\Extras\Http\Traits\ForwardsRequests` trait. 

Instead of the `viewPrefix` method, you have to include a `routePrefix` method. And instead of the `view(string $view)` method you have to use the `redirect(string $route)` method. When the `RoutePrefix` method is not implemented this method will behave the same as calling `redirect()->route($route)`. 

> Route prefixes only work if you are using [named routes](https://laravel.com/docs/5.8/routing#named-routes). 

```php
namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use RoyVoetman\Extras\Http\Traits\RoutePrefix;

/**
 * Class BookController
 *
 * @package App\Http\Controllers
 */
class BookController extends Controller implements RoutePrefix
{
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(): RedirectResponse
    {
				...        

        // Redirect to: `books.index`
        return $this->redirect('index');
    }
  
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Book $book): RedirectResponse
    {
        ...
          
      	// You can have chain methods like `with()` just like 
      	// you normally would when using `return redirect()`
        return $this->redirect('index')->with('status', 'Book updated');
    }
  
    /**
     * @return string
     */
    public function routePrefix(): string
    {
        return 'books';
    }
}
```

### View and Route prefixes

There is a convenient shortcut when you want to implement the `ViewPrefix` and the `RoutePrefix` interface. You can include the `RoyVoetman\Extras\Contracts\ResponsePrefixes` interface which just extends method interfaces.

```php
/**
 * Interface ResponsePrefixes
 *
 * @package App\Interfaces
 */
interface ResponsePrefixes extends RoutePrefix, ViewPrefix
{
    //
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