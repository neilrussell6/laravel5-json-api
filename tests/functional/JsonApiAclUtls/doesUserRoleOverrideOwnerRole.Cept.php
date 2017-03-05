<?php

use \Mockery as m;
use Illuminate\Support\Facades\Config;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiAclUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiAclUtils::doesUserRoleOverrideOwnerRole
//
///////////////////////////////////////////////////////

// higher overrides lower
Config::set('jsonapi.acl.use_role_hierarchy', true);

// ----------------------------------------------------

$user = m::mock('User');
$user->roles = m::mock('Collection');
$user->roles->shouldReceive('toArray')->andReturn([
    [
        'name' => 'admin',
        'hierarchy' => 3,
        'is_hierarchical' => true
    ],
]);

$owner = m::mock('User');
$owner->roles = m::mock('Collection');
$owner->roles->shouldReceive('toArray')->andReturn([
    [
        'name' => 'editor',
        'hierarchy' => 2,
        'is_hierarchical' => true
    ],
    [
        'name' => 'reader',
        'hierarchy' => 1,
        'is_hierarchical' => true
    ],
]);

$result = JsonApiAclUtils::doesUserRoleOverrideOwnerRole($user, $owner);

$I->assertTrue($result);

// ----------------------------------------------------

$user = m::mock('User');
$user->roles = m::mock('Collection');
$user->roles->shouldReceive('toArray')->andReturn([
    [
        'name' => 'reader',
        'hierarchy' => 1,
        'is_hierarchical' => true
    ],
]);

$owner = m::mock('User');
$owner->roles = m::mock('Collection');
$owner->roles->shouldReceive('toArray')->andReturn([
    [
        'name' => 'editor',
        'hierarchy' => 2,
        'is_hierarchical' => true
    ],
    [
        'name' => 'writer',
        'hierarchy' => 2,
        'is_hierarchical' => true
    ],
]);

$result = JsonApiAclUtils::doesUserRoleOverrideOwnerRole($user, $owner);

$I->assertFalse($result);
