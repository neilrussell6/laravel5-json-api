<?php namespace Neilrussell6\Laravel5JsonApi\Utils;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class JsonApiUtils
 * @package Neilrussell6\Laravel5JsonApi\Utils
 *
 * A utility class that generates various parts of JSON API response
 */
class JsonApiUtils
{
    /**
     * returns the status code that appears most often in the given array of error objects
     *
     * @param $errors (array of JSON API error objects)
     * @param int $status_code
     * @return array
     */
    public function getPredominantErrorStatusCode ($errors, $status_code = 422)
    {
        $statuses = array_count_values(array_column($errors, 'status'));
        $max_count = max($statuses);
        $value_counts = array_count_values($statuses);

        if ($value_counts[ $max_count ] > 1) {
            return $status_code;
        }

        return array_search($max_count, $statuses);
    }

    /**
     * creates an array of error objects error object for JSON API formatted response
     * http://jsonapi.org/format/#error-objects
     *
     * @param $error_messages
     * @param $http_code
     * @return array
     */
    public function makeErrorObjects (array $error_messages, $http_code = 422)
    {
        return array_map(function($message) use ($http_code) {

            // members with default fallback values
            $result['status'] = array_key_exists('status', $message) ? $message['status'] : $http_code;

            // members only included if value provided
            if (array_key_exists('id', $message)) { $result['id'] = $message['id']; }
            if (array_key_exists('about', $message)) { $result['about'] = $message['about']; }
            if (array_key_exists('code', $message)) { $result['code'] = $message['code']; }
            if (array_key_exists('detail', $message)) { $result['detail'] = $message['detail']; }
            if (array_key_exists('links', $message)) { $result['links'] = $message['links']; }
            if (array_key_exists('meta', $message)) { $result['meta'] = $message['meta']; }
            if (array_key_exists('pointer', $message)) { $result['pointer'] = $message['pointer']; }
            if (array_key_exists('parameter', $message)) { $result['parameter'] = $message['parameter']; }
            if (array_key_exists('source', $message)) { $result['source'] = $message['source']; }
            if (array_key_exists('title', $message)) { $result['title'] = $message['title']; }

            return $result;
        }, $error_messages);
    }

    /**
     * creates an array of error objects from attribute validation errors for JSON API formatted response
     * http://jsonapi.org/format/#error-objects
     *
     * @param $attribute_validation_error_messages
     * @param $http_code
     * @return array
     */
    public function makeErrorObjectsFromAttributeValidationErrors (array $attribute_validation_error_messages, $http_code = 422)
    {
        $error_messages = array_map(function($field) use ($attribute_validation_error_messages, $http_code) {

            // use 409 for unique error, otherwise use provided default
            $status = array_reduce($attribute_validation_error_messages[ $field ], function ($carry, $message) {
                return preg_match('/unique/', $message) ? 409 : $carry;
            }, $http_code);

            return [
                'status'    => $status,
                'detail'    => $attribute_validation_error_messages[ $field ][0],
                'source'    => [
                    'pointer' => "/data/attributes/{$field}"
                ],
                'title'     => "Invalid Attribute"
            ];
        }, array_keys($attribute_validation_error_messages));

        return $this->makeErrorObjects($error_messages, $http_code);
    }

    /**
     * Makes a JSON API error object using the error_messages array at the given config location.
     *
     * @param $config_location (eg. jsonapi.jwt or jsonapi.acl)
     * @param $error_message_key
     * @param int $default_status_code
     * @param string $default_title
     * @param string $default_detail
     * @return array
     */
    public function makeErrorObjectWithConfig($config_location, $error_message_key, $default_status_code = 400, $default_title = "Bad Request", $default_detail = "An error occurred") {

        // status code

        $config_value = config("{$config_location}.error_messages.status_code");
        $status_code = is_string($config_value) ? $config_value : config("{$config_location}.error_messages.status_code.{$error_message_key}");

        // title

        $config_value = config("{$config_location}.error_messages.title");
        $title = is_string($config_value) ? $config_value : config("{$config_location}.error_messages.title.{$error_message_key}");

        // detail

        $config_value = config("{$config_location}.error_messages.detail");
        $detail = is_string($config_value) ? $config_value : config("{$config_location}.error_messages.detail.{$error_message_key}");

        // defaults

        if (is_null($status_code)) { $status_code = $default_status_code; }
        if (is_null($title)) { $title = $default_title; }
        if (is_null($detail)) { $detail = $default_detail; }

        return [
            'status' => $status_code,
            'title' => $title,
            'detail' => $detail,
        ];
    }

    /**
     * creates a relationship object for JSON API formatted response
     *
     * @param $sub_resource_name
     * @param $base_url
     * @return array
     */
    public function makeRelationshipObject ($sub_resource_name, $base_url) {
        return [
            'links' => [
                'self' => "{$base_url}/relationships/{$sub_resource_name}",
                'related' => "{$base_url}/{$sub_resource_name}",
            ]
        ];
    }

    /**
     * creates a resource object for JSON API formatted response
     * http://jsonapi.org/format/#document-resource-objects
     *
     * @param array $data
     * @param $model
     * @param $base_url
     * @param $links (links object)
     * @param bool $include_relationships
     * @param bool $is_minimal (restricts the results to only type & id)
     * @return array
     */
    public function makeResourceObject ($data, $model, $base_url, $links, $include_relationships = true, $is_minimal = false)
    {
        $collection = new Collection($data);

        // don't include type, id or foreign keys in attributes
        $filtered_collection = $collection->filter(function($item, $key) {
            return !in_array($key, ['id', 'type', 'pivot']) && preg_match('/(.*?)\_id$/', $key) !== 1;
        });

        // type & id
        $result = [
            'id'    => strval($data['id']),
            'type'  => $model->type,
        ];

        // attributes & links
        if (!$is_minimal) {
            $result = array_merge($result, [
                'attributes'    => $filtered_collection->toArray(),
            ]);

            if (!is_null($links)) {
                $result['links'] = $links;
            }
        }

        // relationships
        if ($include_relationships && property_exists($model, 'default_includes') && !empty($model->default_includes)) {

            // build relationships objects
            $relationships = array_reduce($model->default_includes, function ($carry, $default_include) use ($base_url) {
                return array_merge($carry, [ $default_include => $this->makeRelationshipObject($default_include, $base_url) ]);
            }, []);

            $result = array_merge($result, [ 'relationships' => $relationships ]);
        }

        return $result;
    }

    /**
     * creates resource object links object for JSON API formatted response
     * http://jsonapi.org/format/#document-top-level
     *
     * @param $request_base_url
     * @return mixed
     */
    public function makeResourceObjectLinksObject ($request_base_url, $resource_id)
    {
        $result = [];

        // relationships resource
        if (preg_match('/\/\w+\/\d+\/relationships\/\w+$/', $request_base_url)) {
            // doesn't happen because relationships requests return
            // resource identifier objects which do not include a
            // links object
        }
        // sub resource
        else if (preg_match('/\/\w+\/\d+\/\w+$/', $request_base_url)) {
            $base_url = preg_replace('/\/\w+\/\d+(\/\w+)$/', '$1', $request_base_url);
            $result['self'] = "{$base_url}/{$resource_id}";
        }
        // specific primary resource
        else if (preg_match('/\/\w+\/\d+$/', $request_base_url)) {
            // doesn't happen because the resource object will not
            // contain a link object when the top-level data member
            // is not an array
        }
        // primary resource collection response
        else if (preg_match('/\/\w+$/', $request_base_url)) {
            $result['self'] = "{$request_base_url}/{$resource_id}";
        }
        else {
            return null;
        }

        return $result;
    }

    /**
     * creates the top level object for JSON API formatted response
     * http://jsonapi.org/format/#document-top-level
     *
     * @param array $response
     * @return mixed
     */
    public function makeResponseObject (array $response)
    {
        $default_content = [
            'jsonapi' => [
                'version' => '1.0'
            ]
        ];

        // validate response

        // both data & errors
        //
        // "The members data and errors MUST NOT coexist in the
        // same document."
        if (array_key_exists('data', $response) && array_key_exists('errors', $response)) {
            return false;
        }

        // no data, errors or meta properties
        //
        // "A document MUST contain at least one of the
        // following top-level members:
        //  - data: the document’s “primary data”
        //  - errors: an array of error objects
        //  - meta: a meta object that contains non-standard
        //    meta-information."
        if (!array_key_exists('data', $response) && !array_key_exists('errors', $response) && !array_key_exists('meta', $response)) {
            return false;
        }

        // response is valid so merge and return
        return array_merge_recursive($default_content, $response);
    }

    /**
     * creates top-level links object for JSON API formatted response
     * http://jsonapi.org/format/#document-top-level
     *
     * @param $request_base_url
     * @param null $resource_id
     * @return mixed
     */
    public function makeTopLevelLinksObject ($request_base_url, $resource_id = null)
    {
        $result = [];

        // relationships resource
        if (preg_match('/\/\w+\/\d+\/relationships\/\w+$/', $request_base_url)) {
            $result['self'] = $request_base_url;
            $result['related'] = str_replace('relationships/', '', $request_base_url);
        }
        // sub resource
        else if (preg_match('/\/\w+\/\d+\/\w+$/', $request_base_url)) {
            $result['self'] = $request_base_url;
        }
        // specific primary resource
        else if (preg_match('/\/\w+\/\d+$/', $request_base_url)) {
            $result['self'] = $request_base_url;
        }
        // any other response
        else {
            if (is_null($resource_id)) {
                $result['self'] = $request_base_url;
            } else {
                $result['self'] = "{$request_base_url}/{$resource_id}";
            }
        }

        return $result;
    }

    /**
     * creates a pagination links object for JSON API formatted response
     * http://jsonapi.org/format/#fetching-pagination
     *
     * @param LengthAwarePaginator $paginator
     * @param $full_base_url
     * @param $base_url
     * @param $query_params
     * @return array
     */
    public function makeTopLevelPaginationLinksObject (LengthAwarePaginator $paginator, $full_base_url, $base_url, $query_params)
    {
        $result = $this->makeTopLevelLinksObject($full_base_url);

        $indices = [
            'current'   => $paginator->currentPage(),
            'first'     => 1,
            'last'      => $paginator->lastPage(),
            'next'      => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'prev'      => !$paginator->onFirstPage() ? $paginator->currentPage() - 1 : null,
        ];

        $page_query = array_key_exists('page', $query_params) && !is_null($query_params['page']) ? $query_params['page'] : [];

        $query_params = [
            'current'   => !is_null($indices['current']) ? http_build_query(['page' => array_replace_recursive($page_query, ['offset' => $indices['current']])]) : null,
            'first'     => !is_null($indices['first']) ? http_build_query(['page' => array_replace_recursive($page_query, ['offset' => $indices['first']])]) : null,
            'last'      => !is_null($indices['last']) ? http_build_query(['page' => array_replace_recursive($page_query, ['offset' => $indices['last']])]) : null,
            'next'      => !is_null($indices['next']) ? http_build_query(['page' => array_replace_recursive($page_query, ['offset' => $indices['next']])]) : null,
            'prev'      => !is_null($indices['prev']) ? http_build_query(['page' => array_replace_recursive($page_query, ['offset' => $indices['prev']])]) : null,
        ];

        return array_merge_recursive($result, [
            'first' => !is_null($indices['first']) ? "{$base_url}?{$query_params['first']}" : null,
            'last'  => !is_null($indices['last']) ? "{$base_url}?{$query_params['last']}" : null,
            'next'  => !is_null($indices['next']) ? "{$base_url}?{$query_params['next']}" : null,
            'prev'  => !is_null($indices['prev']) ? "{$base_url}?{$query_params['prev']}" : null,
        ]);
    }

    /**
     * creates a pagination meta object for JSON API formatted response
     * http://jsonapi.org/format/#fetching-pagination
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function makeTopLevelPaginationMetaObject (LengthAwarePaginator $paginator)
    {
        return [
            'pagination' => [
                'count'         => $paginator->count(),
                'limit'         => $paginator->perPage(),
                'offset'        => $paginator->currentPage(),
                'total_items'   => $paginator->total(),
                'total_pages'   => $paginator->lastPage(),
            ]
        ];
    }

    /**
     * validate request and return an array of errors
     * TODO: add functional/unit test
     *
     * @param Request $request
     * @return array
     */
    public function validateJsonApiRequest(Request $request)
    {
        $regex_json_api_media_type_without_params = '/application\/vnd\.api\+json(\,.*)?$/';

        // Missing request Content-Type header
        if (!$request->hasHeader('Content-Type')) {

            return JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request missing Content-Type header",
                'detail' => "Clients MUST send all JSON API data in request documents with the header Content-Type: application/vnd.api+json without any media type parameters."
            ]], 400);
        }

        // Invalid request Content-Type header
        if ($request->header('Content-Type') !== 'application/vnd.api+json') {

            return JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request Content-Type header",
                'detail' => "Clients MUST send all JSON API data in request documents with the header Content-Type: application/vnd.api+json without any media type parameters."
            ]], 415);
        }

        // Invalid request Accept header
        if ($request->hasHeader('Accept') && !preg_match($regex_json_api_media_type_without_params, $request->header('Accept'))) {

            return JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request Accept header",
                'detail' => "Clients that include the JSON API media type in their Accept header MUST specify the media type there at least once without any media type parameters."
            ]], 406);
        }

        // if request method requires request data
        if (in_array($request->method(), ['POST', 'PATCH'])) {

            $request_data = $request->all();
            $error_code = 422;

            // validate request data : data
            if (!array_key_exists('data', $request_data)) {

                return JsonApiUtils::makeErrorObjects([[
                    'title' => "Invalid request",
                    'detail' => "The request MUST include a single resource object as primary data."
                ]], $error_code);
            }

            // single resource object
            else if (count($request_data['data']) > 0 && is_string(array_keys($request_data['data'])[0])) {
                return $this->validateRequestResourceObject($request_data['data'], $error_code, $request->method());
            }

            // indexed array of resource objects
            else {
                return array_reduce($request_data['data'], function ($carry, $resource_object) use ($error_code, $request) {
                    return array_merge_recursive($carry, $this->validateRequestResourceObject($resource_object, $error_code, $request->method()));
                }, []);
            }
        }

        return [];
    }

    /**
     * validate request resource object
     * TODO: add functional/unit test
     *
     * @param $resource_object
     * @param $error_code
     * @param $request_method
     * @return array
     */
    public function validateRequestResourceObject ($resource_object, $error_code, $request_method) {

        $errors = [];

        // validate data.type
        if (!array_key_exists('type', $resource_object)) {

            $errors = JsonApiUtils::makeErrorObjects([[
                'title' => "Invalid request",
                'detail' => "The request resource object MUST contain at least a type member."
            ]], $error_code);
        }

        // if request method requires request data
        else if (in_array($request_method, ['PATCH'])) {

            // validate data.id
            if (!array_key_exists('id', $resource_object)) {

                $errors = JsonApiUtils::makeErrorObjects([[
                    'title' => "Invalid request",
                    'detail' => "The request resource object for a PATCH request MUST contain an id member."
                ]], $error_code);
            }
        }

        return $errors;
    }
}
