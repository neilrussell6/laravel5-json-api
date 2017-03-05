<?php

use Illuminate\Database\Seeder;

class JsonApiAclSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $acl_config = config('jsonapi.acl.acl_config');
        $seeder_config = config('jsonapi.acl.seeder_config');
        $role_structure = !is_null($seeder_config) ? config("{$seeder_config}.role_structure") : null;
        $user_permission_structure = !is_null($seeder_config) ? config("{$seeder_config}.permission_structure") : null;
        $permissions_map = !is_null($seeder_config) ? collect(config("{$seeder_config}.permissions_map")) : null;

        if (is_null($role_structure)) { $role_structure = config('jsonapi.acl.role_structure'); }
        if (is_null($user_permission_structure)) { $user_permission_structure = config('jsonapi.acl.permission_structure'); }
        if (is_null($permissions_map)) { $permissions_map = collect(config('jsonapi.acl.permissions_map')); }

        if (is_null($role_structure) || is_null($user_permission_structure) || is_null($permissions_map)) {
            return;
        }

        // get an array of roles that are hierarchical from config
        $hierarchical_roles = config("jsonapi_acl_seeder.hierarchical_roles");
        if (is_null($hierarchical_roles)) {
            $hierarchical_roles = [];
        }

        foreach ($role_structure as $key => $modules) {

            $role_hierarchy = config("jsonapi_acl_seeder.role_hierarchy.{$key}");
            if (is_null($role_hierarchy)) {
                $role_hierarchy = 0;
            }

            // Create a new role
            $role_model = !is_null(config("{$acl_config}.role")) ? config("{$acl_config}.role") : '\App\Role';
            $role = $role_model::create([
                'name' => $key,
                'display_name' => ucwords(str_replace("_", " ", $key)),
                'description' => ucwords(str_replace("_", " ", $key)),
                'hierarchy' => $role_hierarchy,
                'is_hierarchical' => in_array($key, $hierarchical_roles),
            ]);

            foreach ($modules as $module => $value) {
                $permissions = explode(',', $value);

                foreach ($permissions as $p => $perm) {
                    $permission_value = $permissions_map->get($perm);

                    $permission_model = !is_null(config("{$acl_config}.permission")) ? config("{$acl_config}.permission") : '\App\Permission';
                    $permission = $permission_model::firstOrCreate([
                        'name' => $module . '.' . $permission_value,
                        'display_name' => ucwords($permission_value) . ' ' . ucwords(str_replace('.', ' ', $module)),
                        'description' => ucwords($permission_value) . ' ' . ucwords(str_replace('.', ' ', $module)),
                    ]);

                    if (!$role->hasPermission($permission->name)) {
                        $role->attachPermission($permission);
                    }
                }
            }
        }
    }
}
