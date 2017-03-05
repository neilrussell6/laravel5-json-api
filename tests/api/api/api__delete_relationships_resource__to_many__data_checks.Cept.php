<?php

use Illuminate\Support\Facades\Config;
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
Project::find($project_3_id)->users()->attach([ $user_2_id => [ 'is_editor' => true ] ]);
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
// * delete resource 'to many' relationship
// * test data is updated
//
///////////////////////////////////////////////////////

// disable ACL access check
Config::set('jsonapi.acl.check_access', false);

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ----------------------------------------------------
//
// Specs:
// "If the client makes a DELETE request to a URL from
// a relationship link the server MUST delete the
// specified members from the relationship or return a
// 403 Forbidden response. If all of the specified
// resources are able to be removed from, or are
// already missing from, the relationship then the
// server MUST return a successful response."
//
// ----------------------------------------------------

// ====================================================
// delete user 1, 2 & 3 relationships for project 1
// ====================================================

$delete_users = [
    'data' => [
        [ 'type' => 'users', 'id' => $user_1_id ],
        [ 'type' => 'users', 'id' => $user_2_id ],
        [ 'type' => 'users', 'id' => $user_3_id ],
    ]
];

// ----------------------------------------------------

$I->comment("when we delete user 1, 2 & 3 relationships for project 1");
$I->sendDELETE("/api/projects/{$project_1_id}/relationships/users", $delete_users);

$I->expect("should not delete existing users (users 1 & 4), unless included in request (user 1), resulting in only user 4");
$project_1 = Project::find($project_1_id);
$project_1_user_ids = array_column($project_1->users->toArray(), 'id');
$I->assertNotContains($user_1_id, $project_1_user_ids);
$I->assertNotContains($user_2_id, $project_1_user_ids);
$I->assertNotContains($user_3_id, $project_1_user_ids);
$I->assertContains($user_4_id, $project_1_user_ids);

// ====================================================
// delete editor 1 & 2 relationships for project 1
// ====================================================

// TODO: test

// ====================================================
// delete task 2 & 3 relationships for project 2
// ====================================================

// TODO: test
