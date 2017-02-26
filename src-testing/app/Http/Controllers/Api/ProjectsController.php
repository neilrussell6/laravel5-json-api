<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

class ProjectsController extends Controller
{
    public $rules = [
        'name' => 'required',
        'status' => 'required'
    ];

    /**
     * ProjectsController constructor
     *
     * @param Project $model
     */
    public function __construct (Project $model)
    {
        parent::__construct($model);
    }

    /**
     * validates input, then updates the target related resource item.
     * returns either: validation error, update error or not content when successfully updated.
     *
     * @param Request $request
     * @param $id
     * @param bool $should_overwrite (allows us to extend this method and use for POST requests)
     * @return mixed
     */
    public function updateRelated (Request $request, $id, $should_overwrite = true)
    {
        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];

        if ($relationship_name !== 'tasks') {
            return parent::updateRelated($request, $id, $should_overwrite);
        }

        // custom handling for update tasks relationship requests

        $request_data = $request->all();
        $project = $this->model->findOrFail($id);
        $task_model = $project->tasks()->getRelated();

        // validate request data
        $request_data_validation = array_reduce($request_data['data'], function ($carry, $resource_object) use ($task_model) {
            $validation = $this->validateRequestResourceObject($resource_object, $task_model, null, false);
            return !empty($validation['errors']) ? array_merge_recursive($carry, $validation) : $carry;
        }, [ 'errors' => [] ]);

        // respond with error
        if (!empty($request_data_validation['errors'])) {
            $predominant_error_code = JsonApiUtils::getPredominantErrorStatusCode($request_data_validation['error_code'], 422);
            return Response::make([ 'errors' => $request_data_validation['errors'] ], $predominant_error_code);
        }

        // map through each of the project's existing tasks that are not included in request and dissociate with project
        $existing_task_ids = array_column($project->tasks->toArray(), 'id');
        $request_task_ids = array_column($request_data['data'], 'id');
        $task_ids_to_dissociate = array_diff($existing_task_ids, $request_task_ids);

        // TODO: replace with saveMany
        $results = array_map(function ($task_id) use ($project, $task_model) {
            $task = $task_model->find($task_id);
            $task->project()->dissociate($project);
            return $task->save();
        }, $task_ids_to_dissociate);

        if (in_array(false, $results)) {
            return Response::make([ 'errors' => [ "Could not update related resource" ] ], 500 );
        }

        // map through each task id and associate with project
        $results = array_map(function ($task_id) use ($project, $task_model) {
            $task = $task_model->find($task_id);
            $task->project()->associate($project);
            return $task->save();
        }, array_column($request_data['data'], 'id'));

        if (in_array(false, $results)) {
            return Response::make([ 'errors' => [ "Could not update related resource" ] ], 500 );
        }

        // return no content
        return Response::make([], 204);
    }
}
