<?php namespace Neilrussell6\Laravel5JsonApi\Exceptions;

class JsonApiResponseException extends \Exception
{
    /**
     * @param string $message
     * @param int $status_code
     */
    public function __construct($message = "An error occurred", $status_code = 500)
    {
        parent::__construct($message, $status_code);
    }
}
