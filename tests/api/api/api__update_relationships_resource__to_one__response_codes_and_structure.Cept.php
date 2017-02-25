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

// users

$I->comment("given 10 users");
factory(User::class, 10)->create();
$I->assertSame(10, User::all()->count());

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];
$user_2_id = $user_ids[1];
$user_3_id = $user_ids[2];

// projects

// ... no owner
$I->comment("given 1 project, with no owner");
$project_1_id = factory(Project::class, 1)->create()->toArray()[0]['id'];

// ... owned by user 2
$I->comment("given 1 project owned by user 2");
$project_2_id = factory(Project::class, 1)->create(['user_id' => $user_2_id])->toArray()[0]['id'];

// tasks

// ... belonging to project 1
// ... no owner
$I->comment("given 1 task of project 1, and no owner");
$task_1_id = factory(Task::class, 1)->create(['project_id' => $project_1_id, 'user_id' => $user_2_id])->toArray()[0]['id'];

// ... no project
// ... owned by user 2
$I->comment("and 1 task with no project, owned by user 2");
$task_2_id = factory(Task::class, 1)->create(['user_id' => $user_2_id])->toArray()[0]['id'];

///////////////////////////////////////////////////////
//
// Test
//
// * update resource 'to one' relationship
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ----------------------------------------------------
//
// Specs:
// "A server MUST return a 204 No Content status code
// if an update is successful and the representation
// of the resource in the request matches the result."
//
// ----------------------------------------------------

// ====================================================
// update resource relationships
// ====================================================

$I->comment("when we update a resource's specific 'to many' relationships");

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];
$user = Fixtures::get('user');
$user['data']['id'] = $user_1_id;
$user['data']['attributes']['name'] = "BBB";

$project_ids = array_column(Project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];
$project = Fixtures::get('project');
$project['data']['id'] = $project_1_id;
$project['data']['attributes']['name'] = "BBB";

$task_ids = array_column(Task::all()->toArray(), 'id');
$task_1_id = $task_ids[0];
$task = Fixtures::get('task');
$task['data']['id'] = $task_1_id;
$task['data']['attributes']['name'] = "BBB";

$new_owner = [ 'data' => [ 'type' => 'users', 'id' => $user_3_id ] ];
$new_project = [ 'data' => [ 'type' => 'projects', 'id' => $project_2_id ] ];

$requests = [
    [ 'PATCH', "/api/projects/{$project_1_id}/relationships/owner", $new_owner ],
    [ 'PATCH', "/api/projects/{$project_2_id}/relationships/owner", $new_owner ],
    [ 'PATCH', "/api/tasks/{$task_1_id}/relationships/owner", $new_owner ],
    [ 'PATCH', "/api/tasks/{$task_2_id}/relationships/owner", $new_owner ],
    [ 'PATCH', "/api/tasks/{$task_1_id}/relationships/project", $new_project ],
    [ 'PATCH', "/api/tasks/{$task_2_id}/relationships/project", $new_project ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) resource relationship updated -> 204 NO CONTENT
    //
    // Specs:
    // "A server MUST return a 204 No Content status code
    // if an update is successful and the representation
    // of the resource in the request matches the result."
    //
    // ----------------------------------------------------

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    // ----------------------------------------------------
    // 2) no content
    // ----------------------------------------------------

    $I->expect("should not return content");
    $I->seeResponseEquals(null);

});
