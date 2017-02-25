<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;

class TasksController extends Controller
{
    public $rules = [
        'name' => 'required',
        'status' => 'required',
        'type' => 'required',
        'priority' => 'required'
    ];

    /**
     * TasksController constructor
     *
     * @param Task $model
     */
    public function __construct (Task $model)
    {
        parent::__construct($model);
    }
}
