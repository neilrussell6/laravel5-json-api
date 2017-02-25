<?php namespace Neilrussell6\Laravel5JsonApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class JsonApiUtils Facade
 * @package Neilrussell6\Laravel5JsonApi\Utils
 */
class JsonApiUtils extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'jsonapiutils';
    }
}
