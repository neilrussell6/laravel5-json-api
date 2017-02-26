<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * UsersController constructor
     *
     * @param Api $model
     */
    public function __construct (Api $model)
    {
        parent::__construct($model);
    }

    /**
     * return a list of available endpoints
     *
     * @param Request $request
     * @return mixed
     */
    public function index (Request $request)
    {
        return response([
            'data' => []
        ], 200);
    }
}
