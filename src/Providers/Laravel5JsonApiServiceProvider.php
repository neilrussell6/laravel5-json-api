<?php namespace Neilrussell6\Laravel5JsonApi\Providers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

/**
 * Class Laravel5JsonApiServiceProvider
 * @package Neilrussell6\Laravel5JsonApi\Providers
 */
class Laravel5JsonApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param ResponseFactory $factory
     */
    public function boot (ResponseFactory $factory)
    {
        $this->publishes([
            __DIR__ . '/../config/jsonapi.php' => config_path('jsonapi.php')
        ], 'config');
        $this->publishes([
            __DIR__ . '/../config/jsonapi_acl_seeder.php' => config_path('jsonapi_acl_seeder.php')
        ], 'config');
        $this->publishes([
            __DIR__ . '/../database/migrations/2017_03_02_085402_json_api_acl_update_roles_table.php' => database_path('migrations/2017_03_02_085402_json_api_acl_update_roles_table.php')
        ], 'migrations');
        $this->publishes([
            __DIR__ . '/../database/seeds/JsonApiAclSeeder.php' => database_path('seeds/JsonApiAclSeeder.php')
        ], 'seeds');

        $factory->macro('collection', function (Request $request, Collection $collection, $model, $status = 200, $include_resource_object_links = true, $is_minimal = false) use ($factory) {

            $include_relationships = false;
            $base_url = $request->url();

            $result = [
                'links' => JsonApiUtils::makeTopLevelLinksObject($base_url),
                'data' => $collection->map(function($item) use ($model, $base_url, $include_relationships, $is_minimal, $include_resource_object_links) {

                    $links = $include_resource_object_links ? JsonApiUtils::makeResourceObjectLinksObject($base_url, $item['id']) : null;
                    return JsonApiUtils::makeResourceObject($item->toArray(), $model, $base_url, $links, $include_relationships, $is_minimal);
                })
            ];

            return $factory->make($result, $status);
        });

        $factory->macro('item', function (Request $request, $data, $model, $status = 200, $include_resource_object_links = false, $is_minimal = false) use ($factory) {

            $base_url = $request->url();
            $top_level_links = JsonApiUtils::makeTopLevelLinksObject($base_url, $data['id']);
            $resource_object_links = $include_resource_object_links ? JsonApiUtils::makeResourceObjectLinksObject($base_url, $data['id']) : null;
            $include_relationships = preg_match('/\/\w+\/\d+\/\w+$/', $base_url) === 0 && preg_match('/\/\w+\/\d+\/relationships\/\w+$/', $base_url) === 0 && preg_match('/access_tokens/', $base_url) === 0; // don't include relationships for sub resource, relationships or access_tokens request

            $result = [
                'links' => $top_level_links,
                'data' => is_null($data) ? $data : JsonApiUtils::makeResourceObject($data, $model, $top_level_links['self'], $resource_object_links, $include_relationships, $is_minimal)
            ];

            return $factory->make($result, $status);
        });

        $factory->macro('pagination', function (Request $request, LengthAwarePaginator $paginator, $model, $status = 200) use ($factory) {

            $full_base_url = $request->fullUrl();
            $base_url = $request->url();
            $query_params = $request->query();
            $result = [
                'links' => JsonApiUtils::makeTopLevelPaginationLinksObject($paginator, $full_base_url, $base_url, $query_params),
                'meta' => JsonApiUtils::makeTopLevelPaginationMetaObject($paginator),
                'data' => $paginator->getCollection()->map(function($item) use ($model, $base_url) {
                    $include_relationships = false;
                    $links = JsonApiUtils::makeResourceObjectLinksObject($base_url, $item['id']);
                    return JsonApiUtils::makeResourceObject($item, $model, $base_url, $links, $include_relationships);
                })
            ];

            return $factory->make($result, $status);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('jsonapiutils', function()
        {
            return new \Neilrussell6\Laravel5JsonApi\Utils\JsonApiUtils;
        });

        App::bind('jsonapiaclutils', function()
        {
            return new \Neilrussell6\Laravel5JsonApi\Utils\JsonApiAclUtils;
        });
    }
}
