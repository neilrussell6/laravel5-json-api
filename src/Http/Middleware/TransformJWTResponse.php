<?php namespace Neilrussell6\Laravel5JsonApi\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

/**
 * Class TransformJWTResponse
 * @package Neilrussell6\Laravel5JsonApi\Http\Middleware
 */
class TransformJWTResponse
{
    /**
     * Transform JWT error responses to conform with JSON API specs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle ($request, Closure $next)
    {
        $response = $next($request);

        // respond with already rendered exception page
        // if an exception was thrown
        $exception = $response->exception;
        if ($exception) {
            return $response;
        }

        $content = $response->getContent();

        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        if (!is_null($content) && array_key_exists('error', $content) && is_string($content['error'])) {

            $status_code = 401;
            $error = JsonApiUtils::makeErrorObjectWithConfig('jsonapi.jwt', $content['error'], $status_code, "Unauthorised");

            // set response content);
            $content = [ 'errors' => JsonApiUtils::makeErrorObjects([ $error ], $status_code) ];
            if (get_class($response) === JsonResponse::class) {
                $content = json_encode($content);
            }
            $response->setContent($content);

            // set response status code
            $response->setStatusCode($error['status']);
        }

        return $response;
    }
}
