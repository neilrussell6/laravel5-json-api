<?php namespace Neilrussell6\Laravel5JsonApi\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

/**
 * Class TransformACLResponse
 * @package Neilrussell6\Laravel5JsonApi\Http\Middleware
 */
class TransformACLResponse
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

        // if content contains error array, but is an invalid JSON API response object
        if (!is_null($content) && array_key_exists('error', $content)) {

            $error_object = null;
            $status_code = 403;

            switch ($content['error']['code']) {
                case 'INSUFFICIENT_PERMISSIONS':
                    $error = [
                        'status' => !is_null(config('jsonapi.acl.error_status_code.insufficient_permissions')) ? config('jsonapi.acl.error_status_code.insufficient_permissions') : $content['error']['status_code'],
                        'title' => !is_null(config('jsonapi.acl.error_title.insufficient_permissions')) ? config('jsonapi.acl.error_title.insufficient_permissions') : "Insufficient Permissions",
                        'detail' => !is_null(config('jsonapi.acl.detail.insufficient_permissions')) ? config('jsonapi.acl.error_detail.insufficient_permissions') : $content['error']['description'],
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
