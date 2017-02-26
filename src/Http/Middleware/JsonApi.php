<?php namespace Neilrussell6\Laravel5JsonApi\Http\Middleware;

use Closure;
use Neilrussell6\Laravel5JsonApi\Exceptions\JsonApiResponseException;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

/**
 * Class JsonApi
 * @package Neilrussell6\Laravel5JsonApi\Http\Middleware
 */
class JsonApi
{
    /**
     * Validate JSON API request and build JSON API Response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle ($request, Closure $next)
    {
        // validate request
        $errors = JsonApiUtils::validateJsonApiRequest($request);

        // respond with errors
        if (!empty($errors)) {
            $error_code = JsonApiUtils::getPredominantErrorStatusCode($errors);
            return response([ 'errors' => $errors ], $error_code);
        }

        // next middleware
        $response = $next($request);

        // if an exception was thrown
        // ... then respond with already rendered exception page
        $exception = $response->exception;
        if ($exception) {
            return $response;
        }

        // make JSON API response object and update response content
        $original_content = $response->getOriginalContent();
        if (!is_null($original_content)) {
            $content = JsonApiUtils::makeResponseObject($original_content);

            // is response is invalid, then throw exception
            if (!$content) {
                throw new JsonApiResponseException("Response is not valid according to JSON API specs", 500);
            }

            $response->setContent($content);
        }

        // Add JSON API response headers
        $response->header('Content-Type', 'application/vnd.api+json');

        return $response;
    }
}
