<?php namespace Neilrussell6\Laravel5JsonApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

/**
 * Class ValidateJsonApiRequest
 * @package Neilrussell6\Laravel5JsonApi\Http\Middleware
 */
class ValidateJsonApiRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle (Request $request, Closure $next)
    {
        $errors = [];
        $error_code = null;

        $regex_json_api_media_type_without_params = '/application\/vnd\.api\+json(\,.*)?$/';

        // Missing request Content-Type header
        if (!$request->hasHeader('Content-Type')) {

            $error_code = 400;
            $errors = JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request missing Content-Type header",
                'detail' => "Clients MUST send all JSON API data in request documents with the header Content-Type: application/vnd.api+json without any media type parameters."
            ]], $error_code);
        }

        // Invalid request Content-Type header
        else if ($request->header('Content-Type') !== 'application/vnd.api+json') {

            $error_code = 415;
            $errors = JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request Content-Type header",
                'detail' => "Clients MUST send all JSON API data in request documents with the header Content-Type: application/vnd.api+json without any media type parameters."
            ]], $error_code);
        }

        // Invalid request Accept header
        else if ($request->hasHeader('Accept') && !preg_match($regex_json_api_media_type_without_params, $request->header('Accept'))) {

            $error_code = 406;
            $errors = JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request Accept header",
                'detail' => "Clients that include the JSON API media type in their Accept header MUST specify the media type there at least once without any media type parameters."
            ]], $error_code);
        }

        // if request method requires request data
        else if (in_array($request->method(), ['POST', 'PATCH'])) {

            $request_data = $request->all();
            $error_code = 422;

            // validate request data : data
            if (!array_key_exists('data', $request_data)) {

                $errors = JsonApiUtils::makeErrorObjects([[
                    'title' => "Invalid request",
                    'detail' => "The request MUST include a single resource object as primary data."
                ]], $error_code);
            }

            // single resource object
            else if (count($request_data['data']) > 0 && is_string(array_keys($request_data['data'])[0])) {
                $errors = $this->validateRequestResourceObject($request_data['data'], $error_code, $request->method());
            }

            // indexed array of resource objects
            else {
                $errors = array_reduce($request_data['data'], function ($carry, $resource_object) use ($error_code, $request) {
                    return array_merge_recursive($carry, $this->validateRequestResourceObject($resource_object, $error_code, $request->method()));
                }, []);
            }
        }

        // respond with errors
        if (!empty($errors)) {
            return response([ 'errors' => $errors ], $error_code);
        }

        // ...
        return $next($request);
    }

    private function validateRequestResourceObject ($resource_object, $error_code, $request_method) {

        $errors = [];

        // validate data.type
        if (!array_key_exists('type', $resource_object)) {

            $errors = JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request",
                'detail' => "The request resource object MUST contain at least a type member."
            ]], $error_code);
        }

        // if request method requires request data
        else if (in_array($request_method, ['PATCH'])) {

            // validate data.id
            if (!array_key_exists('id', $resource_object)) {

                $errors = JsonApiUtils::makeErrorObjects([[
                    'title' => "Invalid request",
                    'detail' => "The request resource object for a PATCH request MUST contain an id member."
                ]], $error_code);
            }
        }

        return $errors;
    }
}
