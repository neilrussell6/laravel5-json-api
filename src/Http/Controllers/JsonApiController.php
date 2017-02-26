<?php namespace Neilrussell6\Laravel5JsonApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

/**
 * Class JsonApiController
 * @package Neilrussell6\Laravel5JsonApi\Http\Controllers
 */
class JsonApiController extends Controller
{
    const PAGINATION_LIMIT = 100;

    protected $model;

    /**
     * Controller constructor.
     * @param $model
     */
    public function __construct ($model)
    {
        $this->model = new $model();
    }

    // ----------------------------------------------------
    // CRUD
    // ----------------------------------------------------

    /**
     * return a paginated collection of resource items
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function index (Request $request)
    {
        $page_args = $request->query('page');

        // paginate request
        $pagination_options = $this->makePaginationOptions($page_args);
//        $paginator = $this->model->paginate($pagination_options['limit'], ['*'], "page['offset']", $pagination_options['offset']);
        $paginator = $this->model->paginate($pagination_options['limit'], ['*'], "page[offset]", $pagination_options['offset']);

        // if no pagination arguments are provided,
        // and the result count falls within the PAGINATION_LIMIT
        // then return as normal collection
        if (is_null($page_args) && !$paginator->hasMorePages()) {
            return Response::collection($request, $paginator->getCollection(), $this->model, 200);
        }

        // otherwise return paginated response
        return Response::pagination($request, $paginator, $this->model, 200);
    }

    /**
     * return a paginated collection of related resource items
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function indexRelated (Request $request, $id)
    {
        // is minimal ? (return resource identifier object ie. only type and id)
        $action         = $request->route()->getAction();
        $is_minimal     = array_key_exists('is_minimal', $action) && $action['is_minimal'];

        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];

        // fetch primary & related resources
        $primary_entity     = $this->model->findOrFail($id);
        $related_collection = $primary_entity->{$relationship_name};
        $related_model      = $primary_entity->{$relationship_name}()->getRelated();
        $include_resource_object_links = true;

        return Response::collection($request, $related_collection, $related_model, 200, $include_resource_object_links, $is_minimal);
    }

    /**
     * return single resource item
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function show (Request $request, $id)
    {
        $resource = $this->model->findOrFail($id);
        return Response::item($request, $resource, $this->model, 200);
    }

    /**
     * return single related resource item
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function showRelated (Request $request, $id)
    {
        // is minimal ? (return resource identifier object ie. only type and id)
        $action         = $request->route()->getAction();
        $is_minimal     = array_key_exists('is_minimal', $action) && $action['is_minimal'];

        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];

        // fetch primary & related resources
        $primary_entity     = $this->model->findOrFail($id);
        $related_entity     = $primary_entity->{$relationship_name};
        $related_data       = !is_null($related_entity) ? $related_entity->toArray() : null;
        $related_model      = $primary_entity->{$relationship_name}()->getRelated();
        $include_resource_object_links = true;

        return Response::item($request, $related_data, $related_model, 200, $include_resource_object_links, $is_minimal);
    }

    /**
     * validates input, then creates a new resource item.
     * returns either: validation error, creation error or successfully created new resource item.
     *
     * @param Request $request
     * @return mixed
     */
    public function store (Request $request)
    {
        $request_data = $request->all();
        $request_data_validation = $this->validateRequestResourceObject($request_data['data'], $this->model);

        // respond with errors
        if (!empty($request_data_validation['errors'])) {
            return response([ 'errors' => $request_data_validation['errors'] ], $request_data_validation['error_code']);
        }

        // create & find resource
        $result = $this->model->create($request_data['data']['attributes']);
        $resource = $this->model->findOrFail($result->id);

        // return newly created resource
        return Response::item($request, $resource->toArray(), $this->model, 201);
    }

    /**
     * validates input, then updates the target related resource item/s.
     * returns either: validation error, update error or successfully updated resource item.
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function storeRelated (Request $request, $id)
    {
        return $this->updateRelated($request, $id, $should_overwrite = false);
    }

    /**
     * validates input, then updates the target resource item.
     * returns either: validation error, update error or no content when successfully created.
     *
     * @param Request $request
     * @return mixed
     */
    public function update (Request $request, $id)
    {
        $request_data = $request->all();

        // only validate attributes provided (ignore missing)
        $validation_rules = array_keys($request_data['data']['attributes']);
        $request_data_validation = $this->validateRequestResourceObject($request_data['data'], $this->model, $id, true, $validation_rules);

        // respond with error
        if (!empty($request_data_validation['errors'])) {
            return Response::make([ 'errors' => $request_data_validation['errors'] ], $request_data_validation['error_code']);
        }

        // find & update resource
        $resource = $this->model->findOrFail($id);
        $resource->fill($request_data['data']['attributes']);
        $resource->save();

        // return updated resource
        return Response::item($request, $resource->toArray(), $this->model, 200);
    }

    /**
     * validates input, then updates the target related resource item/s.
     * returns either: validation error, update error or no content when successfully updated.
     *
     * @param Request $request
     * @param $id
     * @param bool $should_overwrite (allows us to extend this method and use for POST requests)
     * @return mixed
     */
    public function updateRelated (Request $request, $id, $should_overwrite = true)
    {
        // TODO: add more checks, eg. don't assume relationship model methods exist etc
        $request_data = $request->all();

        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];

        // fetch primary & related resources
        $primary_entity    = $this->model->findOrFail($id);
        $related           = $primary_entity->{$relationship_name}();
        $related_model     = $related->getRelated();

        // validate request data
        // ... single resource object
        if (count($request_data['data']) > 0 && is_string(array_keys($request_data['data'])[0])) {
            $request_data_validation = $this->validateRequestResourceObject($request_data['data'], $related_model, null, false);
        }

        // ... indexed array of resource objects
        else {

            $request_data_validation = array_reduce($request_data['data'], function ($carry, $resource_object) use ($related_model) {
                $validation = $this->validateRequestResourceObject($resource_object, $related_model, null, false);
                return !empty($validation['errors']) ? array_merge_recursive($carry, $validation) : $carry;
            }, [ 'errors' => [] ]);
        }

        // respond with error
        if (!empty($request_data_validation['errors'])) {
            $predominant_error_code = JsonApiUtils::getPredominantErrorStatusCode($request_data_validation['error_code'], 422);
            return Response::make([ 'errors' => $request_data_validation['errors'] ], $predominant_error_code);
        }

        // update relationship
        // ... single resource object
        else if (count($request_data['data']) > 0 && is_string(array_keys($request_data['data'])[0])) {
            $related_entity = $related_model->find($request_data['data']['id']);
            $primary_entity->{$relationship_name}()->associate($related_entity);
        }

        // ... indexed array of resource objects
        else {

            $related_data = array_reduce($request_data['data'], function ($carry, $resource_object) {
                $carry[ $resource_object['id'] ] = array_key_exists('attributes', $resource_object) ? $resource_object['attributes'] : [];
                return $carry;
            }, []);

            $primary_entity->{$relationship_name}()->sync($related_data, $should_overwrite);
        }

        if (!$primary_entity->save()) {
            return Response::make([ 'errors' => [ "Could not update related resource" ] ], 500 );
        }

        // return no content
        return Response::make([], 204);
    }

    /**
     * deletes the target resource item.
     * returns either: deletion error or no content.
     *
     * @param Request $request
     * @return mixed
     */
    public function destroy (Request $request, $id)
    {
        $this->model->destroy($id);

        // return no content
        return Response::make([], 204);
    }

    /**
     * validates input, then deletes the target related resource item/s.
     * returns either: validation error, update error or no content when successfully updated.
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function destroyRelated (Request $request, $id)
    {
        // TODO: add more checks, eg. don't assume relationship model methods exist etc
        $request_data = $request->all();

        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];

        // fetch primary & related resources
        $primary_entity    = $this->model->findOrFail($id);
        $related           = $primary_entity->{$relationship_name}();
        $related_model     = $related->getRelated();

        // validate request data
        // ... single resource object
        if (count($request_data['data']) > 0 && is_string(array_keys($request_data['data'])[0])) {
            $request_data_validation = $this->validateRequestResourceObject($request_data['data'], $related_model, null, false);
        }

        // ... indexed array of resource objects
        else {

            $request_data_validation = array_reduce($request_data['data'], function ($carry, $resource_object) use ($related_model) {
                $validation = $this->validateRequestResourceObject($resource_object, $related_model, null, false);
                return !empty($validation['errors']) ? array_merge_recursive($carry, $validation) : $carry;
            }, [ 'errors' => [] ]);
        }

        // respond with error
        if (!empty($request_data_validation['errors'])) {
            $predominant_error_code = JsonApiUtils::getPredominantErrorStatusCode($request_data_validation['error_code'], 422);
            return Response::make([ 'errors' => $request_data_validation['errors'] ], $predominant_error_code);
        }

        // update relationship
        // ... indexed array of resource objects
        $related_ids = array_column($request_data['data'], 'id');
        $primary_entity->{$relationship_name}()->detach($related_ids);

        if (!$primary_entity->save()) {
            return Response::make([ 'errors' => [ "Could not update related resource" ] ], 500 );
        }

        // return no content
        return Response::make([], 204);
    }

    // ----------------------------------------------------
    // helpers
    // ----------------------------------------------------

    /**
     * validate request data
     * returns and array of JSON API error objects
     *
     * @param $resource_object
     * @param $model
     * @param null $id
     * @param bool $validate_attributes
     * @param null $validation_rules
     * @return array
     */
    protected function validateRequestResourceObject ($resource_object, $model, $id = null, $validate_attributes = true, $validation_rules = null)
    {
        $result = [
            'errors' => [],
            'error_code' => null
        ];

        // validate request data : data.type
        if ($model['type'] !== $resource_object['type']) {

            $result['error_code'] = 409;
            $result['errors'] = JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request invalid type",
                'detail' => "The resource object’s type is not among the type(s) that constitute the collection represented by the endpoint."
            ]], $result['error_code']);
        }

        // validate request data : data.id
        else if (array_key_exists('id', $resource_object) && !is_null($id) && $resource_object['id'] !== intval($id)) {

            $result['error_code'] = 409;
            $result['errors'] = JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request invalid ID",
                'detail' => "The resource object’s id does not match the server’s endpoint."
            ]], $result['error_code']);
        }

        else if ($validate_attributes) {

            // validate attributes
            $attributes = array_key_exists('attributes', $resource_object) ? $resource_object['attributes'] : [];
            $rules = $model->rules;

            // filter rules if validation rules provided
            if (!is_null($validation_rules)) {
                $rule_collection = new Collection($model->rules);
                $rules = $rule_collection->filter(function ($value, $key) use ($validation_rules) {
                    return in_array($key, $validation_rules);
                })->toArray();
            }

            $validator = Validator::make($attributes, $rules, $model->messages);

            if ($validator->fails()) {
                $result['errors'] = JsonApiUtils::makeErrorObjectsFromAttributeValidationErrors($validator->errors()->getMessages(), 422);
                $result['error_code'] = JsonApiUtils::getPredominantErrorStatusCode($result['errors'], 422);
            }
        }

        return $result;
    }

    /**
     * make pagination options
     *
     * @param $page_args
     * @return array
     */
    protected function makePaginationOptions ($page_args)
    {
        $result = [
            'limit' => self::PAGINATION_LIMIT,
            'offset' => 1,
        ];

        if (is_null($page_args)) {
            return $result;
        }

        if (array_key_exists('limit', $page_args) && (int) $page_args['limit'] < self::PAGINATION_LIMIT) {
            $result['limit'] = (int) $page_args['limit'];
        }

        if (array_key_exists('offset', $page_args)) {
            $result['offset'] = (int) $page_args['offset'];
        }

        return $result;
    }
}
