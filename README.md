# Eloquent Filter
An Eloquent way to filter Eloquent Models

## Introduction
Lets say we want to return a list of users filtered by multiple parameters. When we navigate to:

`/users?name=er&last_name=&company_id=2&roles[]=1&roles[]=4&roles[]=7&industry=5`

`$request->all()` will return:
```php
[
	'name' 		 => 'er',
    'last_name'  => ''
    'company_id' => '2',
    'roles'      => ['1','4','7'],
    'industry'   => '5'
]
```
To filter by all those parameters we would need to do something like:
```php
<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\User;

class UserController extends Controller
{

    public function index(Request $request)
    {
    	$query = User::where('company_id', $request->input('company_id'));

        if ($request->input('last_name') !== '')
        {
            $query->where('last_name', 'LIKE', '%' . $request->input('last_name') . '%');
        }

        if ($request->input('name') !== '')
        {
            $query->where(function ($q) use ($request)
            {
                return $q->where('first_name', 'LIKE', $request->input('name') . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $request->input('name') . '%');
            });
        }

        $query->whereHas('roles', function ($q) use ($request)
        {
            return $q->whereIn('id', $request->input('roles'));
        })
            ->whereHas('clients', function ($q) use ($request)
            {
                return $q->whereHas('industry_id', $request->input('industry'));
            });

        return $query->get();
    }

}
```
To filter that same input With Eloquent Filters:

```php
<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\User;

class UserController extends Controller
{

	public function index(Request $request)
    {
    	return User::filter($request->all())->get();
    }

}
```

## Configuration
### Install Through Composer
```
composer require tucker-eric/eloquentfilter
```
After installing the Eloquent Filter library, register the `EloquentFilter\ServiceProvider::class` in your `config/app.php` configuration file:
```php
'providers' => [
    // Other service providers...

    EloquentFilter\ServiceProvider::class,
],
```
Copy the package config to your local config with the publish command:
```bash
php artisan vendor:publish --provider="EloquentFilter\ServiceProvider"
```
In the `app/eloquentfilter.php` config file.  Set the namespace your model filters will reside in:
```php
'namespace' => "App\\ModelFilters\\",
```

## Usage

### Generating The Filter
You can create a model filter with the following artisan command:
```bash
php artisan model:filter User
```
Where `User` is the Eloquent Model you are creating the filter for.  This will create `app/ModelFilters/UserFilter.php`

### Defining The Filter Logic
Define the filter logic based on the camel cased input key passed to the `filter()` method.

- Empty strings are ignored
- `_id` is dropped from the end of the input to define the method so filtering `user_id` would use the `user()` method
- Input without a corresponding filter method are ignored
- The value of the key is injected into the method
- All values are accessible through the `$this->input()` method or a single value by key `$this->input($key)` 
- All Eloquent Builder methods are accessible in `this` context in the model filter class.

### Applying The Filter To A Model

Implement the `EloquentFilter\Filterable` trait on any Eloquent model:
```php
<?php namespace App;

use EloqentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Filterable;
    
    //User Class
}
```
This gives you access to the `filter()` method that accepts an array of input:
```php
class UserController extends Controller
{
	public function index(Request $request)
    {
        return User::filter($request->all())->get();
    }
}
```

#### Filtering By Relationships
In order to filter by a relationship (whether the relation is joined in the query or not) add the relation in the `$relations` array with the name of the relation as referred to on the model as the key and the column names that will be received as input to filter.

The related model **MUST** have a ModelFilter associated with it.  We instantiate the related model's filter and use the column values from the `$relations` array to call the associated methods.

This is helpful when querying multiple columns on a relation's table.  For a single column using a `$this->whereHas()` method in the model filter works just fine

##### Example:

If I have a `User` that `hasMany` `App\Client::class` my model would look like:
```php
class User extends Model
{
    use Filterable;
    
    public function clients()
    {
    	return $this->hasMany(Client::class);
    }
}
```
Let's also say each `App\Client` has belongs to `App\Industry::class`:
```php
class User extends Model
{
    use Filterable;
    
    public function industry()
    {
    	return $this->belongsTo(Industry::class);
    }
}
```
We want to query our User's and filter them based on the industry of their client:

Input used to filter:
```php
$input = [
	industry => 5
];
```
`UserFilter` with the relation defined so it's able to be queried.  
```php
class UserFilter extends ModelFilter
{
	public $relations = [
        'clients' => ['industry'],
    ];
}
```
`ClientFilter` with the `industry` method that's used to filter:
```php
class UserFilter extends ModelFilter
{
	public $relations = [];
    
    public function industry($id)
    {
    	return $this->where('industry_id', $id);
	}
}
```

If the following array is passed to the `filter()` method:
```php
[
	'name' 		 => 'er',
    'last_name'  => ''
    'company_id' => 2,
    'roles'      => [1,4,7],
    'industry'   => 5
]
```
In `app/ModelFilters/UserFilter.php`:
```php
<?php namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class UserFilter extends ModelFilter
{
	public $relations = [
        'clients' => ['industry'],
    ];
    
	public function name($name)
    {
    	return $this->where(function($q)
        {
        	return $q->where('first_name', 'LIKE', $name . '%')->orWhere('last_name', 'LIKE', '%' . $name.'%');
        });    	
    }
    
    public function lastName($lastName)
    {
    	return $this->where('last_name', 'LIKE', '%' . $lastName);
    }
    
    public function company($id)
    {
    	return $this->where('company_id',$id);
    }
    
    public function roles($ids)
    {
    	return $this->whereHas('roles', function($query) use ($ids)
        {
        	return $query->whereIn('id', $ids);
        });
    }
}
```
# Contributing
Any contributions welcome!
