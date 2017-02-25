<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// before
//
///////////////////////////////////////////////////////

$I->comment("given 10 users");
factory(User::class, 10)->create();
$I->assertSame(10, User::all()->count());

$I->comment("given 10 projects");
factory(Project::class, 10)->create();
$I->assertSame(10, Project::all()->count());

$I->comment("given 10 tasks");
factory(Task::class, 10)->create([ 'project_id' => 1,  'status' => Project::STATUS_INCOMPLETE ]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * invalid request data
// * response codes and structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];

$project_ids = array_column(Project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];

$task_ids = array_column(Task::all()->toArray(), 'id');
$task_1_id = $task_ids[0];

// ====================================================
// 422 UNPROCESSABLE_ENTITY
// ====================================================

$requests = [];

// ----------------------------------------------------
// 1) No data
//
// Specs:
// "The request MUST include a single resource object
// as primary data."
//
// ----------------------------------------------------

$I->comment("when we make a request that requires data (store, update) but we don't provide it");

$requests = array_merge($requests, [
    [ 'POST', '/api/users', [] ],
    [ 'POST', '/api/projects', [] ],
    [ 'POST', '/api/tasks', [] ],
    [ 'PATCH', "/api/users/{$user_1_id}", [] ],
    [ 'PATCH', "/api/projects/{$project_1_id}", [] ],
    [ 'PATCH', "/api/tasks/{$task_1_id}", [] ],
]);

// ----------------------------------------------------
// 2) No type
//
// Specs:
// "The resource object MUST contain at least a type
// member."
//
// ----------------------------------------------------

$I->comment("when we make a request that requires data (store, update) but we don't provide a type");

$user_without_type = Fixtures::get('user');
$project_without_type = Fixtures::get('project');
$task_without_type = Fixtures::get('task');

unset($user_without_type['data']['type']);
unset($project_without_type['data']['type']);
unset($task_without_type['data']['type']);

$requests = array_merge($requests, [
    [ 'POST', '/api/users', $user_without_type ],
    [ 'POST', '/api/projects', $project_without_type ],
    [ 'POST', '/api/tasks', $task_without_type ],
    [ 'PATCH', "/api/users/{$user_1_id}", array_merge_recursive($user_without_type, [ 'data' => [ 'id' => $user_1_id ] ]) ],
    [ 'PATCH', "/api/projects/{$project_1_id}", array_merge_recursive($project_without_type, [ 'data' => [ 'id' => $project_1_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$task_1_id}", array_merge_recursive($task_without_type, [ 'data' => [ 'id' => $task_1_id ] ]) ],
]);

// ----------------------------------------------------
// 3) No id
//
// Specs:
// "The PATCH request MUST include a single resource
// object as primary data. The resource object MUST
// contain type and id members."
//
// ----------------------------------------------------

$I->comment("when we make a request that requires data (store, update) but we don't provide a type");

$user_without_id = Fixtures::get('user');
$project_without_id = Fixtures::get('project');
$task_without_id = Fixtures::get('task');

$requests = array_merge($requests, [
    [ 'PATCH', "/api/users/{$user_1_id}", $user_without_id ],
    [ 'PATCH', "/api/projects/{$project_1_id}", $project_without_id ],
    [ 'PATCH', "/api/tasks/{$task_1_id}", $task_without_id ],
]);

// ----------------------------------------------------
// test all requests
// ----------------------------------------------------

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 422 HTTP code");
    $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);

    // ----------------------------------------------------

    $I->expect("should return an array of errors");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

    // ----------------------------------------------------

    $I->expect("should return a single error object in errors array");
    $errors = $I->grabResponseJsonPath('$.errors[*]');
    $I->assertSame(count($errors), 1);

    // ----------------------------------------------------

    $I->expect("error object should contain a status, title and detail member");
    $I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::UNPROCESSABLE_ENTITY);
    $I->seeResponseJsonPathType('$.errors[0].title', 'string:!empty');
    $I->seeResponseJsonPathType('$.errors[0].detail', 'string:!empty');

});
