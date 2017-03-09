<?php namespace Neilrussell6\Laravel5JsonApi\Http\Controllers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiAclUtils;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;
use ReflectionClass;

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
        //------------------------------------------------
        // args
        //------------------------------------------------

        // get 'page' url args
        $page_args = $request->query('page');

        //------------------------------------------------
        // ACL : if user does not have permission
        //------------------------------------------------

        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::permissionCheck($request->route()->getName(), Auth::user());
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        //------------------------------------------------
        // paginate
        //------------------------------------------------

        // paginate request
        $pagination_options = $this->makePaginationOptions($page_args);
        $pagination_query = $this->model->query();

        //------------------------------------------------
        // paginate : ownership / role hierarchy
        //------------------------------------------------

        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false && !is_null(config('jsonapi.acl.check_ownership')) && config('jsonapi.acl.check_ownership') !== false) {

            // get resource owner highest role hierarchy value
            $resource_owner_hierarchy = JsonApiAclUtils::getHighestRoleHierarchy(Auth::user()->roles);

            // get resource owner query part
            $resource_table = $this->model->getTable();
            $resource_owner_key = property_exists($this->model, 'owner_key') ? $this->model->owner_key : "user_id";
            $resource_owner_query_part = "{$resource_table}.{$resource_owner_key}"; // eg. 'projects.user_id'

            // if not set to use role hierarchy, then just check ownership
            if (is_null(config('jsonapi.acl.use_role_hierarchy')) || config('jsonapi.acl.use_role_hierarchy') === false) {

                $pagination_query->where($resource_owner_query_part, '=', Auth::user()->id);
            }

            // else check ownership & role hierarchy
            else {

                $pagination_query
                    ->select("{$resource_table}.*")//, 'resource_user_highest_role.hierarchy')
                    ->join(DB::raw("(SELECT users.id AS user_id, MAX(COALESCE(roles.hierarchy, 0)) AS hierarchy FROM users LEFT JOIN role_user ON role_user.user_id = users.id LEFT JOIN roles ON roles.id = role_user.role_id GROUP BY users.id) AS resource_user_highest_role"), 'resource_user_highest_role.user_id', '=', $resource_owner_query_part)
                    ->where('resource_user_highest_role.hierarchy', '<', $resource_owner_hierarchy)
                    ->orWhere($resource_owner_query_part, '=', Auth::user()->id);
                //dd($pagination_query->toSql());
            }
        }

        //------------------------------------------------
        // paginate ...
        //------------------------------------------------

        // get paginated results
        $paginator = $pagination_query->paginate($pagination_options['limit'], ['*'], "page[offset]", $pagination_options['offset']);

        //------------------------------------------------
        // response
        //------------------------------------------------

        // if no pagination arguments are provided,
        // and the result count falls within the PAGINATION_LIMIT
        // then return as normal collection
        if (is_null($page_args) && !$paginator->hasMorePages()) {
            return Response::collection($request, $paginator->getCollection(), $this->model, 200);
        }

        // otherwise return paginated Response
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
        //------------------------------------------------
        // args
        //------------------------------------------------

        // get 'page' url args
        $page_args = $request->query('page');

        // is minimal ? (return resource identifier object ie. only type and id)
        $action         = $request->route()->getAction();
        $is_minimal     = array_key_exists('is_minimal', $action) && $action['is_minimal'];

        //------------------------------------------------
        // primary resource
        //------------------------------------------------

        // get primary resource
        $primary_resource = $this->model->findOrFail($id);

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $primary_resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        //------------------------------------------------
        // related resource
        //------------------------------------------------

        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];

        $relationship       = $primary_resource->{$relationship_name}(); // eg. hasMany etc
        $related_model      = $relationship->getRelated(); // eg. Project etc
        $include_resource_object_links = true;

        //------------------------------------------------
        // paginate
        //------------------------------------------------

        // paginate relationship request
        $pagination_options = $this->makePaginationOptions($page_args);
        $pagination_query = $relationship;

        //------------------------------------------------
        // paginate : ownership / role hierarchy
        //------------------------------------------------

        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false && !is_null(config('jsonapi.acl.check_ownership')) && config('jsonapi.acl.check_ownership') !== false) {

            // get resource owner highest role hierarchy value
            $resource_owner_hierarchy = JsonApiAclUtils::getHighestRoleHierarchy(Auth::user()->roles);

            // get resource owner query part
            $resource_table = $related_model->getTable();
            $resource_owner_key = property_exists($related_model, 'owner_key') ? $related_model->owner_key : "user_id";
            $resource_owner_query_part = "{$resource_table}.{$resource_owner_key}"; // eg. 'projects.user_id'

            // if not set to use role hierarchy, then just check ownership
            if (is_null(config('jsonapi.acl.use_role_hierarchy')) || config('jsonapi.acl.use_role_hierarchy') === false) {

                $pagination_query->where($resource_owner_query_part, '=', Auth::user()->id);
            }

            // else check ownership & role hierarchy
            else {

                // update pagination query to consider role hierarchies
                $pagination_query
                    ->select("{$resource_table}.*")
                    ->join(DB::raw("(SELECT users.id AS user_id, MAX(COALESCE(roles.hierarchy, 0)) AS hierarchy FROM users LEFT JOIN role_user ON role_user.user_id = users.id LEFT JOIN roles ON roles.id = role_user.role_id GROUP BY users.id) AS resource_user_highest_role"), 'resource_user_highest_role.user_id', '=', $resource_owner_query_part)
                    ->whereRaw("(resource_user_highest_role.hierarchy < ? OR {$resource_owner_query_part} = ?)")
                    ->addBinding($resource_owner_hierarchy, 'where')
                    ->addBinding(Auth::user()->id, 'where');
                //dd($pagination_query->toSql());
            }
        }

        //------------------------------------------------
        // paginate ...
        //------------------------------------------------

        // get paginated results
        $paginator = $pagination_query->paginate($pagination_options['limit'], ['*'], "page[offset]", $pagination_options['offset']);

        //------------------------------------------------
        // response
        //-----------------------------------------------4-

        // if no pagination arguments are provided,
        // and the result count falls within the PAGINATION_LIMIT
        // then return as normal collection
        if (is_null($page_args) && !$paginator->hasMorePages()) {
            return Response::collection($request, $paginator->getCollection(), $related_model, 200, $include_resource_object_links, $is_minimal);
        }

        // otherwise return paginated Response
        return Response::pagination($request, $paginator, $related_model, 200, $include_resource_object_links, $is_minimal);
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
        // get target resource
        $resource = $this->model->findOrFail($id);

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

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

        // fetch primary resource
        $primary_resource   = $this->model->findOrFail($id);

        // fetch related resource
        $related_resource   = $primary_resource->{$relationship_name};
        $related_data       = !is_null($related_resource) ? $related_resource->toArray() : null;
        $relationship       = $primary_resource->{$relationship_name}(); // eg. hasMany etc
        $related_model      = $relationship->getRelated(); // eg. Project etc
        $include_resource_object_links = true;

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $related_resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

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
            return Response::make([ 'errors' => $request_data_validation['errors'] ], $request_data_validation['error_code']);
        }

        // create & find resource
        $result = $this->model->create($request_data['data']['attributes']);
        $resource = $this->model->findOrFail($result->id);

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        // update relationships
        // TODO: implement as one query so that if the relationship update fails the whole request fails
        // issue: at this point the primary resource is already created, so if this part fails we have partially completed a request

        if (array_key_exists('relationships', $request_data['data'])) {
            $relationships = new Collection($request_data['data']['relationships']);
            $relationships->each(function($relationship, $key) use ($request, $resource) {
                $this->updateRelatedHelper($request, $relationship['data'], $key, $resource, true);
            });
        }

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

        // fetch resource
        $resource = $this->model->findOrFail($id);

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        // update resource
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
        // fetch primary resource
        $primary_resource = $this->model->findOrFail($id);

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $primary_resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        $request_data = $request->all();

        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];

        // update related
        $update_relationship_result = $this->updateRelatedHelper($request, $request_data['data'], $relationship_name, $primary_resource, $should_overwrite);
        return Response::make($update_relationship_result['response'], $update_relationship_result['status_code']);
    }

    /**
     * Helper that handles the updating of a related entity
     *
     * @param $request
     * @param $relationship_data
     * @param $relationship_name
     * @param $primary_resource
     * @param $should_overwrite
     * @return array
     */
    protected function updateRelatedHelper ($request, $relationship_data, $relationship_name, $primary_resource, $should_overwrite)
    {
        $request_data = $request->all();

        //------------------------------------------------
        // related resource
        //------------------------------------------------

        $relationship      = $primary_resource->{$relationship_name}(); // eg. hasMany etc
        $related_model     = $relationship->getRelated(); // eg. Project etc

        //------------------------------------------------
        // validate request data
        //------------------------------------------------

        // ... single related resource object
        if (count($relationship_data) > 0 && is_string(array_keys($relationship_data)[0])) {
            $request_data_validation = $this->validateRequestResourceObject($relationship_data, $related_model, null, false);
        }

        // ... indexed array of related resource objects
        else {
            $request_data_validation = array_reduce($request_data['data'], function ($carry, $resource_object) use ($related_model) {
                $validation = $this->validateRequestResourceObject($resource_object, $related_model, null, false);
                return !empty($validation['errors']) ? array_merge_recursive($carry, $validation) : $carry;
            }, [ 'errors' => [] ]);
        }

        // respond with error
        if (!empty($request_data_validation['errors'])) {
            $predominant_error_code = JsonApiUtils::getPredominantErrorStatusCode($request_data_validation['error_code'], 422);
            return [
                'response' => [ 'errors' => $request_data_validation['errors'] ],
                'status_code' => $predominant_error_code
            ];
        }

        //------------------------------------------------
        // update relationship
        //------------------------------------------------

        // ... single related resource object
        if (count($relationship_data) > 0 && is_string(array_keys($relationship_data)[0])) {

            // fetch related resource (this is the resource we are changing to, not from)
            // the from resource is irrelevant, we are interested only in two thigns:
            // 1) can we update this relationship of the primary resource (eg. can we update the project this task belongs to?)
            // 2) do we have permission to update that relationship to this related resource (eg. do we own the project we are changing this task's project to?)
            $related_resource = $related_model->find($relationship_data['id']);

            // ACL
            if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {

                // map route name to related permission required (eg. setting Project Owner during projects.store requires a projects.relationships.owner.update permission)
                $related_permission_required = JsonApiAclUtils::getRelatedPermission($request->route()->getName(), $relationship_name);

                $errors = JsonApiAclUtils::accessCheck($related_permission_required, Auth::user(), $related_resource);

                if (!empty($errors)) {
                    $predominant_error_code = JsonApiUtils::getPredominantErrorStatusCode($errors, 422);
                    return [
                        'response' => [ 'errors' => $errors ],
                        'status_code' => $predominant_error_code
                    ];
                }
            }

            // update related resource
            $primary_resource->{$relationship_name}()->associate($related_resource);
        }

        // ... indexed array of related resource objects
        else {

            $related_data = array_reduce($request_data['data'], function ($carry, $resource_object) {
                $carry[ $resource_object['id'] ] = array_key_exists('attributes', $resource_object) ? $resource_object['attributes'] : [];
                return $carry;
            }, []);

            $primary_resource->{$relationship_name}()->sync($related_data, $should_overwrite);
        }

        if (!$primary_resource->save()) {
            return [
                'response' => [ 'errors' => [ "Could not update related resource" ] ],
                'status_code' => 500
            ];
        }

        //------------------------------------------------
        // response
        //------------------------------------------------

        return [
            'response' => [],
            'status_code' => 204
        ];
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
        // get target resource
        $resource = $this->model->findOrFail($id);

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        if (!$this->model->destroy($id)) {
            return Response::make([ 'errors' => [ "Could not delete resource" ] ], 500 );
        }

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
        $request_data = $request->all();

        // fetch primary resource
        $primary_resource = $this->model->findOrFail($id);

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), Auth::user(), $primary_resource);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        //------------------------------------------------
        // related resource
        //------------------------------------------------

        // get target relationship name (eg. owner, author)
        $relationship_name = array_values(array_slice($request->segments(), -1))[0];
        $relationship       = $primary_resource->{$relationship_name}(); // eg. hasMany etc

        //------------------------------------------------
        // dissociate relationship
        //------------------------------------------------

        // ... single resource object
        if (in_array(get_class($relationship), [ BelongsTo::class, HasOne::class ])) {

            $primary_resource->{$relationship_name}()->dissociate();
        }

        // ... indexed array of resource objects (including HasOneOrMany)
        else {
            $related_ids = array_column($request_data['data'], 'id');
            $primary_resource->{$relationship_name}()->detach($related_ids);
        }

        if (!$primary_resource->save()) {
            return [
                'response' => [ 'errors' => [ "Could not update related resource" ] ],
                'status_code' => 500
            ];
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
        else if (array_key_exists('id', $resource_object) && !is_null($id) && intval($resource_object['id']) !== intval($id)) {

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
