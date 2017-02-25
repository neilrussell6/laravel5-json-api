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
$user_4_id = $user_ids[3];

// projects

$I->comment("given 3 projects");
factory(Project::class, 3)->create();
$I->assertSame(3, Project::all()->count());

$project_ids = array_column(User::all()->toArray(), 'id');
$project_1_id = $project_ids[0];
$project_2_id = $project_ids[1];
$project_3_id = $project_ids[2];

// ... project 1 is shared with user 1 & 4, and user 4 is flagged as editor
$I->comment("given user 1 is associated with project 1");
Project::find($project_1_id)->users()->attach($user_1_id);

// ... project 1 has user 4 as editor
$I->comment("given user 4 is editor on project 1");
Project::find($project_1_id)->editors()->sync([ $user_4_id => [ 'is_editor' => true ] ], false); // the false stops sync from overriding existing values in the pivot table

// ... project 2 is not shared with any users and has no editor

// ... project 3 is shared with users 2 & 3, and both are editors
$I->comment("given users 2 & 3 are associated with project 3, and both are editors");
Project::find($project_3_id)->users()->attach($user_2_id);
Project::find($project_3_id)->users()->attach([ $user_3_id => [ 'is_editor' => true ] ]);

// tasks

// ... task 1 belongs to project 1
$I->comment("given task 1 belongs to project 1");
factory(Task::class, 1)->create([ 'project_id' => $project_1_id ]);

// ... task 2 belongs to no project
$I->comment("given task 2 belongs to no project");
factory(Task::class, 1)->create();

// ... task 3,4,5 belong to project 2
$I->comment("given tasks 3,4,5 belong to project 2");
factory(Task::class, 3)->create([ 'project_id' => $project_2_id ]);

$I->assertSame(5, Task::all()->count());

$task_ids = array_column(User::all()->toArray(), 'id');
$task_1_id = $task_ids[0];
$task_2_id = $task_ids[1];
$task_3_id = $task_ids[2];
$task_4_id = $task_ids[3];
$task_5_id = $task_ids[4];

// ... task 1 is shared with user 1
$I->comment("given user 1 is associated with and is the editor of task 1");
Task::find($task_1_id)->users()->attach($user_1_id);
Task::paginate(5)->getCollection()->map(function ($task) use ($user_1_id) {
    $task->users()->attach($user_1_id);
});

// ... task 1 has user 1 as editor
Task::find($task_1_id)->editors()->sync([ $user_1_id => [ 'is_editor' => true ], $user_3_id => [ 'is_editor' => true ] ], false); // the false stops sync from overriding existing values in the pivot table

///////////////////////////////////////////////////////
//
// Test
//
// * update resource 'to many' relationship
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

$I->comment("when we update a resource's specific 'to many' relationship");

$new_users = [
    'data' => [
        [ 'type' => 'users', 'id' => $user_2_id ],
        [ 'type' => 'users', 'id' => $user_3_id ],
    ]
];
$new_editors = [
    'data' => [
        [ 'type' => 'users', 'id' => $user_1_id, 'attributes' => [ 'is_editor' => true ] ],
        [ 'type' => 'users', 'id' => $user_2_id, 'attributes' => [ 'is_editor' => true ] ],
    ]
];
$clear_users = [
    'data' => []
];
$new_tasks = [
    'data' => [
        [ 'type' => 'tasks', 'id' => $task_2_id ],
        [ 'type' => 'tasks', 'id' => $task_3_id ],
    ]
];

$requests = [
    [ 'PATCH', "/api/projects/{$project_1_id}/relationships/users", $new_users ],
    [ 'PATCH', "/api/projects/{$project_2_id}/relationships/users", $new_editors ],
    [ 'PATCH', "/api/projects/{$project_3_id}/relationships/users", $clear_users ],
    [ 'PATCH', "/api/projects/{$project_1_id}/relationships/tasks", $new_tasks ],
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
