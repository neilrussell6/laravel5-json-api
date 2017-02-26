<?php namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Neilrussell6\Laravel5JsonApi\Http\Controllers\JsonApiController;

class Controller extends JsonApiController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const PAGINATION_LIMIT = 100;

    protected $model;

    public $messages = [
        'required' => 'The :attribute field is required.',
        'email' => 'A valid email is required for :attribute field.',
        'unique' => ':attribute field must be unique.',
        'min' => ':attribute field must be at least :min character in length.',
    ];

    /**
     * Controller constructor.
     * @param $model
     */
    public function __construct ($model)
    {
        $this->model = new $model();
    }
}
