<?php namespace Neilrussell6\Laravel5JsonApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class JsonApiAclUtils Facade
 * @package Neilrussell6\Laravel5JsonApi\Utils
 */
class JsonApiAclUtils extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'jsonapiaclutils';
    }
}
