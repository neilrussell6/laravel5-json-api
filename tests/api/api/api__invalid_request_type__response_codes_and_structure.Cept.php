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
// * invalid request type
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
// 409 CONFLICT
// ====================================================

// ----------------------------------------------------
// 1) Unknown type
//
// Specs:
// "A server MUST return 409 Conflict when processing
// a PATCH request in which the resource object’s type
// and id do not match the server’s endpoint."
//
// "A server MUST return 409 Conflict when processing a
// POST request in which the resource object’s type is
// not among the type(s) that constitute the collection
// represented by the endpoint."
//
// ----------------------------------------------------

$I->comment("when we make a request that requires data (store, update) and provide an unknown type");

$user_wrong_type = Fixtures::get('user');
$project_wrong_type = Fixtures::get('project');
$task_wrong_type = Fixtures::get('task');

$user_wrong_type['data']['type'] = 'not_users';
$project_wrong_type['data']['type'] = 'not_projects';
$task_wrong_type['data']['type'] = 'not_tasks';

$requests =  [
    [ 'POST', '/api/users', $user_wrong_type ],
    [ 'POST', '/api/projects', $project_wrong_type ],
    [ 'POST', '/api/tasks', $task_wrong_type ],
    [ 'PATCH', "/api/users/{$user_1_id}", array_merge_recursive($user_wrong_type, [ 'data' => [ 'id' => $user_1_id ] ]) ],
    [ 'PATCH', "/api/projects/{$project_1_id}", array_merge_recursive($project_wrong_type, [ 'data' => [ 'id' => $project_1_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$task_1_id}", array_merge_recursive($task_wrong_type, [ 'data' => [ 'id' => $task_1_id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 409 HTTP code");
    $I->seeResponseCodeIs(HttpCode::CONFLICT);

    // ----------------------------------------------------

    $I->expect("should return an array of errors");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

    // ----------------------------------------------------

    $I->expect("should return a single error object in errors array");
    $errors = $I->grabResponseJsonPath('$.errors[*]');
    $I->assertSame(count($errors), 1);

    // ----------------------------------------------------

    $I->expect("error object should contain a status, title and detail member");
    $I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::CONFLICT);
    $I->seeResponseJsonPathType('$.errors[0].title', 'string:!empty');
    $I->seeResponseJsonPathType('$.errors[0].detail', 'string:!empty');

});
