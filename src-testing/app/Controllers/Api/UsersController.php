<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UsersController extends Controller
{
    /**
     * UsersController constructor
     *
     * @param User $model
     */
    public function __construct (User $model)
    {
        parent::__construct($model);
    }
}
