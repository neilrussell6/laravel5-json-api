<?php namespace Neilrussell6\Laravel5JsonApi\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Handler
 * @package Neilrussell6\Laravel5JsonApi\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report (Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render ($request, Exception $exception)
    {
        $errors = [];
        $error_code = null;

        switch (get_class($exception)) {

            // Endpoint not found
            case NotFoundHttpException::class:
                $error_code = $exception->getStatusCode();
                $errors = JsonApiUtils::makeErrorObjects([[
                    'title' => "Endpoint not found",
                    'detail' => "The requested endpoint is unknown, it may be misspelled."
                ]], $error_code);
                break;

            // Method not allowed
            case MethodNotAllowedHttpException::class:
                $error_code = $exception->getStatusCode();
                $errors = JsonApiUtils::makeErrorObjects([[
                    'title' => "Method not allowed",
                    'detail' => "The requested method is not allowed by this endpoint."
                ]], $error_code);
                break;

            // Method not allowed
            case ModelNotFoundException::class:
                $error_code = 404;
                $errors = JsonApiUtils::makeErrorObjects([[
                    'title' => "Resource does not exist",
                    'detail' => "The target resource does not exist."
                ]], $error_code);
                break;
        }

        // respond with error
        if (!empty($errors)) {
            $links = [ 'self' => $request->fullUrl() ];
            $content = JsonApiUtils::makeResponseObject([ 'errors' => $errors ], $links);
            return response($content, $error_code);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated ($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
