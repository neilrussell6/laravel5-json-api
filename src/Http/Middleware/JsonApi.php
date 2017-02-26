<?php namespace Neilrussell6\Laravel5JsonApi\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
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
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws JsonApiResponseException
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
        $content = $response->getContent();

        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        if (!is_null($content)) {
            $content = JsonApiUtils::makeResponseObject($content);

            // is response is invalid, then throw exception
            if (!$content) {
                throw new JsonApiResponseException("Response is not valid according to JSON API specs", 500);
            }

            if (get_class($response) === JsonResponse::class) {
                $content = json_encode($content);
            }

            $response->setContent($content);
        }

        // Add JSON API response headers
        $response->header('Content-Type', 'application/vnd.api+json');

        return $response;
    }
}
