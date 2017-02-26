<?php namespace Neilrussell6\Laravel5JsonApi\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
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

            $error_object = null;
            $status_code = 401;

            switch ($content['error']) {
                case 'token_not_provided':
                    $error = [
                        'status' => !is_null(config('jsonapi.jwt.error_status_code.token_not_provided')) ? config('jsonapi.jwt.error_status_code.token_not_provided') : $status_code,
                        'title' => !is_null(config('jsonapi.jwt.error_title.token_not_provided')) ? config('jsonapi.jwt.error_title.token_not_provided') : "Unauthorised",
                        'detail' => !is_null(config('jsonapi.jwt.detail.token_not_provided')) ? config('jsonapi.jwt.error_detail.token_not_provided') : "Access token not provided",
                    ];
                    break;

                case 'token_expired':
                    $error = [
                        'status' => !is_null(config('jsonapi.jwt.error_status_code.token_expired')) ? config('jsonapi.jwt.error_status_code.token_expired') : $status_code,
                        'title' => !is_null(config('jsonapi.jwt.error_title.token_expired')) ? config('jsonapi.jwt.error_title.token_expired') : "Unauthorised",
                        'detail' => !is_null(config('jsonapi.jwt.detail.token_expired')) ? config('jsonapi.jwt.error_detail.token_expired') : "Access token is expired",
                    ];
                    break;

                case 'token_invalid':
                    $error = [
                        'status' => !is_null(config('jsonapi.jwt.error_status_code.token_invalid')) ? config('jsonapi.jwt.error_status_code.token_invalid') : $status_code,
                        'title' => !is_null(config('jsonapi.jwt.error_title.token_invalid')) ? config('jsonapi.jwt.error_title.token_invalid') : "Unauthorised",
                        'detail' => !is_null(config('jsonapi.jwt.detail.token_invalid')) ? config('jsonapi.jwt.error_detail.token_invalid') : "Access token is invalid",
                    ];
                    break;

                case 'user_not_found':
                    $error = [
                        'status' => !is_null(config('jsonapi.jwt.error_status_code.user_not_found')) ? config('jsonapi.jwt.error_status_code.user_not_found') : $status_code,
                        'title' => !is_null(config('jsonapi.jwt.error_title.user_not_found')) ? config('jsonapi.jwt.error_title.user_not_found') : "Unauthorised",
                        'detail' => !is_null(config('jsonapi.jwt.error_detail.user_not_found')) ? config('jsonapi.jwt.error_detail.user_not_found') : "No user for given access token",
                    ];
                    break;

            }

            // set response content
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
