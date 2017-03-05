<?php namespace Neilrussell6\Laravel5JsonApi\Utils;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class JsonApiAclUtils
 * @package Neilrussell6\Laravel5JsonApi\Utils
 *
 * A utility class for Laravel5JsonApi ACL
 */
class JsonApiAclUtils
{

    /**
     * @param $request_route_name
     * @param Model $user
     * @param Model $resource
     * @return array
     */
    public function accessCheck($request_route_name, Model $user, Model $resource)
    {
        // if user does not have permission);
        $errors = JsonApiAclUtils::permissionCheck($request_route_name, $user);
        if (!empty($errors)) {
            return $errors;
        }

        // if user is not the resource owner
        $errors = JsonApiAclUtils::ownershipCheck($user, $resource);
        if (!empty($errors)) {
            return $errors;
        }

        return [];
    }
    /**
     * @param $user
     * @param $owner
     * @return bool
     */
    public function doesUserRoleOverrideOwnerRole($user, $owner)
    {
        // if not set to use role hierarchy, then fail
        if (is_null(config('jsonapi.acl.use_role_hierarchy')) || config('jsonapi.acl.use_role_hierarchy') === false) {
            return false;
        }

        // get highest role for user & owner
        $highest_user_role_hierarchy = $this->getHighestRoleHierarchy($user->roles);
        $highest_owner_role_hierarchy = $this->getHighestRoleHierarchy($owner->roles);

        return $highest_user_role_hierarchy > $highest_owner_role_hierarchy;
    }

    /**
     * build the related permission required
     * eg. setting a Project's Owner during 'projects.store' request requires a 'projects.relationships.owner.update' permission (all other verbs remain the same)
     *
     * @param $route_name
     * @param $relationship_name
     * @return string
     * @throws \Exception
     */
    public function getRelatedPermission($route_name, $relationship_name) {

        if (preg_match('/relationships/', $route_name)) {
            return $route_name;
        }

        $verbs = [ 'index', 'view', 'store', 'update', 'delete' ];
        preg_match('/'.implode('|', $verbs).'/', $route_name, $verb_search_matches);

        // invalid route name
        if (empty($verb_search_matches)) {
            throw new \Exception("Invalid route name give to JsonApiAclUtils::getRelatedPermission");
        }

        // get verb and route prefix
        $verb = $verb_search_matches[0];
        $route_prefix = str_replace(".{$verb}", '', $route_name);

        // map store to update
        $mapped_verb = in_array($verb, ['store']) ? 'update' : $verb;

        return "{$route_prefix}.relationships.{$relationship_name}.{$mapped_verb}";
    }

    /**
     * @param $request_route_name
     * @param Model $user
     * @return array
     */
    public function permissionCheck($request_route_name, Model $user)
    {
        if (is_null(config('jsonapi.acl.check_permission')) || config('jsonapi.acl.check_permission') === false) {
            return [];
        }

        // TODO: get can method from config

        if ($user->can($request_route_name)) {
            return [];
        }

        $JsonApiUtils = new JsonApiUtils();

        // user does not have permission

        return [
            $JsonApiUtils->makeErrorObjectWithConfig('jsonapi.acl', 'check_permission_fail', 403, "Forbidden")
        ];
    }

    /**
     * @param Model $user
     * @param Model $resource
     * @return array
     */
    public function ownershipCheck(Model $user, Model $resource)
    {
        if (is_null(config('jsonapi.acl.check_ownership')) || config('jsonapi.acl.check_ownership') === false) {
            return [];
        }

        // TODO: get owns method from config

        if ($user->owns($resource)) {
            return [];
        }

        $JsonApiUtils = new JsonApiUtils();

        // user is not the resource owner

        // get resource owner
        $user_reflection = new \ReflectionClass($user);
        $resource_reflection = new \ReflectionClass($resource);
        $user_class = $user_reflection->getShortName();
        $resource_class = $resource_reflection->getShortName();
        // ... if resource is a user class then it is it's own owner
        // ... otherwise get it's owner method, if available
        $resource_owner = $user_class === $resource_class ? $resource : (!is_null($resource->owner) ? $resource->owner : null);

        // user role is higher (in hierarchy) than owner role
        if (!is_null($resource_owner) && JsonApiAclUtils::doesUserRoleOverrideOwnerRole($user, $resource_owner)) {
            return [];
        }

        // no hierarchy check
        // or user failed role hierarchy check

        return [
            $JsonApiUtils->makeErrorObjectWithConfig('jsonapi.acl', 'check_ownership_fail', 403, "Forbidden")
        ];
    }

    /**
     * return the hierarchy value of the highest role (that is hierarchical) in the hierarchy
     * my sincerest apologies to anyone who had to read that :)
     *
     * @param $roles
     * @return mixed
     */
    public function getHighestRoleHierarchy ($roles)
    {
        return array_reduce($roles->toArray(), function ($result, $role) {
            return $role['is_hierarchical'] && (intval($role['hierarchy']) > $result) ? intval($role['hierarchy']) : $result;
        }, 0);
    }
}
