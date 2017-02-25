Laravel 5 JSON API
==================

Response format:

* [JSON API](http://jsonapi.org/format/)

## Usage

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
    protected $middleware = [
        ...
        \Neilrussell6\Laravel5JsonApi\Http\Middleware\BuildJsonApiResponse::class,
        \Neilrussell6\Laravel5JsonApi\Http\Middleware\ValidateJsonApiRequest::class,
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

## Manual testing

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
art db:seed --class=Neilrussell6\\Laravel5JsonApi\\Testing\\database\\seeds\\DatabaseSeeder
```

4) Serve project

```bash
php artisan serve
```

5) View in [Postman](https://chrome.google.com/webstore/detail/postman/fhbjgbiflinjbdggehcddcbncdddomop?hl=en)

eg. GET http://127.0.0.1:8000/api
eg. GET http://127.0.0.1:8000/api/users
eg. GET http://127.0.0.1:8000/api/users/1

## Running automated tests
      
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
        "Neilrussell6\\Laravel5JsonApi\\Testing\\": "packages/neilrussell6/laravel5-json-api/src-testing/"
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

## Customisation

You can customize request/response handling by overriding Controller methods, see

And 2 helpful tools to use when doing this are:

1) The package's utility facade **Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils**

2) The package's Response macros, eg.

```php
Response::item
Response::collection
Response::pagination
```

see **src/Providers/Laravel5JsonApiServiceProvider.php** for implementation.
