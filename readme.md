Laravel 5 JSON API
==================

Response format:

* [JSON API](http://jsonapi.org/format/)

Usage
-----

#### Use composer to require packages

```bash
composer require neilrussell6\laravel5-json-api dev-master
```

#### Update config/app.php

```php
    'providers' => [
        ...
        \Neilrussell6\Laravel5JsonApi\Providers\Laravel5JsonApiServiceProvider::class,
        ...
    'aliases' => [
        ...
        'JsonApiUtils' => \Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils::class
        ...
```

#### Update bootstrap/app.php

```php
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    // App\Exceptions\Handler::class
    \Neilrussell6\Laravel5JsonApi\Exceptions\Handler::class
);
```

#### Update app/Http/Kernel.php

```php
    protected $routeMiddleware = [
        ...
        'jsonapi' => \Neilrussell6\Laravel5JsonApi\Http\Middleware\JsonApi::class,
        ...
```

#### Update routes/api.php

```php
Route::group(['middleware' => ['jsonapi'], 'namespace' => 'Api'], function () {
    ...
```

#### Extend JsonApiController

eg. if your API will use **app/Http/Controllers/Controller.php** asa base controller, then update as follows:

```php
class Controller extends JsonApiController
    ...
    protected $model;
    ...
    public function __construct ($model)
    {
        $this->model = new $model();
        ...
```

Manual testing
--------------

> NOTE: if you are using MySQL < 5.7.7 then update the character set in **config/database.php** as follows:

```php
    ...
    'connections' => [
        ...
        'mysql' => [
            ...
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            ...
```

eg. in laravel/5.4.x:

1) Configure database in **laravel/5.4.x/.env**

2) Run migrations

```bash
php artisan migrate --path=packages/neilrussell6/laravel5-json-api/src-testing/database/migrations
```

or if using in a project where this package was required via composer:

```bash
php artisan migrate --path=vendor/neilrussell6/laravel5-json-api/src-testing/database/migrations
```

3) Seed database with sample data

```bash
art db:seed --class=Neilrussell6\\Laravel5JsonApi\\Seeds\\DatabaseSeeder
```

4) Serve project

```bash
php artisan serve
```

5) View in [Postman](https://chrome.google.com/webstore/detail/postman/fhbjgbiflinjbdggehcddcbncdddomop?hl=en)

eg. GET http://127.0.0.1:8000/api
eg. GET http://127.0.0.1:8000/api/users
eg. GET http://127.0.0.1:8000/api/users/1

Running automated tests
-----------------------

> TODO: create an artisan command to automate all this.

Assuming the following directory structure:

 * laravel
    * 5.4.x (contains a clean install of Laravel 5.4.x)
 * packages
    * neilrussell6
        * laravel5-json-api

1) Create a symlink from packages to  laravel/5.4.x/packages.
   Repeat for other versions and Lumen.

2) Copy env.testing.example to env.testing

3) Copy everything in src-testing (excluding database directory) into cleaning install (laravel/5.4.x)

4) Update each clean install's composer.json:

```json
"autoload-dev": {
    "psr-4": {
        ...
        "Neilrussell6\\Laravel5JsonApi\\": "packages/neilrussell6/laravel5-json-api/src/",
        "Neilrussell6\\Laravel5JsonApi\\Seeds\\": "packages/neilrussell6/laravel5-json-api/src-testing/database/seeds/"
    }
    ...
```

4) Create a testing DB in each clean install (in laravel/5.4.x) and make it executable:

```bash
touch database/laravel5_json_api_testing.sqlite
sudo chmod -R 777 database/laravel5_json_api_testing.sqlite
```

5) add the following to .env for each clean install (laravel/5.4.x/.env)

```
DB_TESTING_DATABASE=laravel5_json_api_testing.sqlite
```

6) Run migrations (in laravel/5.4.x)

```bash
php artisan migrate --path=packages/neilrussell6/laravel5-json-api/src-testing/database/migrations --database=sqlite_testing
```

or if using in a project where this package was required via composer:

```bash
php artisan migrate --path=vendor/neilrussell6/laravel5-json-api/src-testing/database/migrations --database=sqlite_testing
```

7) Update Codeception config before each separate version test

eg. for laravel/5.4.x, update to root property in the following files:

* packages/neilrussell6/laravel5-json-api/tests/api.suite.yml
* packages/neilrussell6/laravel5-json-api/tests/functional.suite.yml

as follows:

```yml
    root: ../../../laravel/5.4.x/
```

8) Serve project (for Acceptance tests)

eg. in laravel/5.4.x/

```bash
php artisan serve
```

9) Run tests (in packages/neilrussell6/laravel5-json-api)

```bash
codecept run
```

Customisation
-------------

> You can customize request/response handling by overriding Controller methods

2 helpful tools for customisation are:

1) The package's utility facade **Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils**

2) The package's Response macros, eg.

```php
Response::item
Response::collection
Response::pagination
```

see **src/Providers/Laravel5JsonApiServiceProvider.php** for implementation.

Road Map
--------

#### JSON API

* Add support to update or create relationships through the relationships object in a request like this:
  UPDATE /articles/1
  {
    "data": [
      "relationships": {
        "comments": [ ... ],
        "author": { "type": "users", "id": "123" },
      }
    }
  }  
* Add support for paginating related resources
* Add support for include queries
* Add support for sorting
* Add support for filtering

License
-------

[MIT](https://github.com/neilrussell6/markdown-it-code-embed/blob/master/LICENSE)

JWTAuth
=======

> This package provides support for usage with [JWTAuth](https://github.com/tymondesigns/jwt-auth)

Middleware
----------

> This package includes middleware that will transform JWTAuth error responses to conform with JSON API specs.

#### Update app/Http/Kernel.php

```php
    protected $routeMiddleware = [
        ...
        'jsonapi.jwt' => \Neilrussell6\Laravel5JsonApi\Http\Middleware\TransformJWTResponse::class,
        ...
```

#### Update routes/api.php

Add 'jsonapi/jwt' middleware to your routes eg.

```php
Route::group(['middleware' => ['jsonapi.jwt']], function () {
    ...
```

> NOTE: This middleware will also work with any package that returns single error strings. eg. `[ 'error' => 'some error' ]`

Configuration
-------------

#### config/jsonapi.php

You can configure how error messages are returned by adjusting the JWTAuth mappings in:

 * jwt.error_messages.error_status_code (*can be an integer, string or keyed array*)
 * jwt.error_messages.error_title (*can be a string or keyed array*)
 * jwt.error_messages.error_detail (*can be a string or keyed array*)

The keys of keyed arrays must correspond with JWTAuth error codes which are:

 * token_not_provided
 * token_expired
 * token_invalid
 * user_not_found

ACL
===

> This package provides support for usage with [Laratrust](https://github.com/santigarcor/laratrust).
> But the ACL handling is implemented in such a way that it should work with most ACL packages or native Laravel policies/gates, but some configuration may be required.  

Configuration
-------------

#### config/jsonapi.php

You can configure what authorisation checks are called, and how they are called by configuring the following:

 * **jwt.acl.check_access**
   Whether or not to apply any ACL checks (if false neither ownership nor permission checks will occur).
   
 * **jwt.acl.check_ownership**
   Whether or not to check if resource is owned by the user attempting to access/modify it.
   
 * **jwt.acl.check_permission** 
   Whether or not to check if the user attempting to access/modify the resource has permission to do so.
   This permission check will correspond to the route name (eg. users.show or tasks.relationships.project.show).
   So your permissions need to be named according to route names.

 * **jwt.acl.check_ownership_method**
   The User model method to call when checking ownership (default is 'owns'). 
   eg. will be called like this: `Auth::user()->owns($resource)`

 * **jwt.acl.check_permission_method**
   The User model method to call when checking for permission (default is 'can'). 
   eg. will be called like this: `Auth::user()->can('projects.store', $resource)`

You can also configure how ACL related error messages are returned by adjusting the following:

 * acl.error_messages.error_status_code (*can be an integer, string or keyed array*)
 * acl.error_messages.error_title (*can be a string or keyed array*)
 * acl.error_messages.error_detail (*can be a string or keyed array*)

The keys of keyed arrays must correspond with the following:

 * check_ownership_fail
 * check_permission_fail

This package also comes with a **JsonApiAclSeeder**, which should be used instead of **LaratrustSeeder.php**
By default **JsonApiAclSeeder** will look for role, permission structures and maps in **config/laratrust_seeder.php** but you can define the location of your ACL structure by setting **jsonapi.acl.seeder_config** in **config/jsonapi.php**, just make sure your ACL's data structure is the same as that of Laratrust.

```php
    'acl' => [
        ...
        'seeder_config' => 'laratrust_seeder'
```

When configuration your ACL structure make sure the permissions map follows laravel's route action naming convention eg.
```php
    'permissions_map' => [
        'i' => 'index',
        'c' => 'store',
        'r' => 'show',
        'u' => 'update',
        'd' => 'destroy',
    ]
```

Also make sure the module part of your role_structure is a valid laravel route name, eg.
```php
    'role_structure' => [
        'administrator' => [
            'projects' => 'i,c,r,u,d',
            'projects.owner' => 'r',
            'projects.relationship.owner' => 'r',
            'projects.tasks' => 'i',
```

For a list of all the routes in your project use the following command:

```bash
php artisan route:list
```

#### Role Hierarchy

This package includes optional support for role hierarchies, meaning that if set, a role can override the ownership check for roles with a lower hierarchy than it.
eg. if a role called **admin** has a hierarchy of 3 and a role called **subscriber** has a hierarchy of 1, then a user with the **admin** role can perform actions (for which it has permission) on records belonging to users with the **subscriber** role.

To use role hierarchy, update **config/jsonapi.php** as follows:

```php
    'acl' => [
        'use_role_hierarchy' => true,
```

Then when creating roles set the ``hierarchy`` & ``is_hierarchical`` values.

You can alternatively setup roles using the ``JsonApiAclSeeder`` seeder, which can be configured as follows:

First define the order of your hierarchy in **config/jsonapi_acl_seeder.php**, eg.

```php
    'role_hierarchy' => [ // higher overrides lower
        'super_administrator' => 4,
        'administrator' => 3,
        'editor' => 2,
        'moderator' => 2,
        'subscriber' => 1,
    ]
```

Then specify which roles will be hierarchical (by default roles will not be hierarchical)

```php
    'hierarchical_roles' => [
        'super_administrator',
        'administrator',
        'editor'
    ],
```
return [
    'role_structure' => [
        'hierarchical_roles' => [ 'administrator' ],

The above setup will mean that:
 - super administrator's will be able to perform any actions they have permissions for, on records owned by users with roles below them in the hierarchy (administrator, editor, moderator, subscriber).
 - the same applies for administrator & editor roles. 
 - however moderators will not be able to perform any actions they have permissions for, on records owned by users with roles below them in the hierarchy, because even though the moderator role is superior to the subscriber role, the moderator role is not hierarchical (as defined in the hierarchical_roles array in **config/jsonapi.php**.   

Then run the ``JsonApiAclSeeder``, eg. Add the following to the ``run`` method of your **database/seeds/DatabaseSeeder.php** file:

```php
$this->call(JsonApiAclSeeder::class);
```

This seeder will setup all your roles and permissions data, including role hierarchies according to the configurations detailed above, but will not create any users.

> Remember don't run both the LaratrustSeeder and the JsonApiAclSeeder.
