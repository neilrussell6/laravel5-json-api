<?php

use Codeception\Util\Fixtures;
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
// * test data is updated
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ----------------------------------------------------
//
// Specs:
// "A server MUST respond to PATCH, POST, and DELETE requests to a URL from a to-many relationship link as described below."
//
// ----------------------------------------------------

// ====================================================
// update project 1's users with users 2 & 3
// ====================================================

$new_users = [
    'data' => [
        [ 'type' => 'users', 'id' => $user_2_id ],
        [ 'type' => 'users', 'id' => $user_3_id ],
    ]
];

// ----------------------------------------------------

$I->comment("when we update a project 1's users with user 2 & 3");
$I->sendPATCH("/api/projects/{$project_1_id}/relationships/users", $new_users);

$I->expect("should overwrite existing users (users 1 & 4), resulting in only users 2 & 3");
$project_1 = Project::find($project_1_id);
$project_1_user_ids = array_column($project_1->users->toArray(), 'id');
$I->assertContains($user_2_id, $project_1_user_ids);
$I->assertContains($user_3_id, $project_1_user_ids);

$I->expect("should also overwrite existing editors (user 4), resulting in no editors (because editors are just shared users with a flag)");
$I->assertEmpty($project_1->editors->toArray());

// ====================================================
// update project 3's editors with users 1 & 2
// ====================================================

// we will update editors through the users
// relationship, to ensure we are explicit about what
// we are doing which is:
//  - replacing all of the project's user relationships
//  - and not just updating those users that are
//    flagged with is_editor
// so no PATCH requests to editors.
//
// If we wanted to support PATCH requests to editors
// then we would have to have to return a response
// detailing any side effects (eg. user relationships
// affected by the editors update) as per:
// Specs:
// "If a server accepts an update but also changes
// the targeted relationship(s) in other ways than
// those specified by the request, it MUST return a
// 200 OK response. The response document MUST
// include a representation of the updated
// relationship(s)."

$new_editors = [
    'data' => [
        [ 'type' => 'users', 'id' => $user_1_id, 'attributes' => [ 'is_editor' => true ] ],
        [ 'type' => 'users', 'id' => $user_2_id, 'attributes' => [ 'is_editor' => true ] ],
    ]
];

// ----------------------------------------------------

$I->comment("when we update a project 3's editors with user 1 & 2");
$I->sendPATCH("/api/projects/{$project_3_id}/relationships/users", $new_editors);

$project_3 = Project::find($project_3_id);

$I->expect("should overwrite existing editors (user 3), resulting in only users 1 & 2");
$project_3_editor_ids = array_column($project_3->editors->toArray(), 'id');
$I->assertContains($user_1_id, $project_3_editor_ids);
$I->assertContains($user_2_id, $project_3_editor_ids);
$I->assertNotContains($user_3_id, $project_3_editor_ids);

$I->expect("should overwrite existing users (user 3), resulting in users 1 & 2");
$project_3_user_ids = array_column($project_3->users->toArray(), 'id');
$I->assertContains($user_1_id, $project_3_user_ids);
$I->assertContains($user_2_id, $project_3_user_ids);
$I->assertNotContains($user_3_id, $project_3_user_ids);

// ====================================================
// clear project 3's users
// ====================================================

$new_users = [
    'data' => []
];

// ----------------------------------------------------

$I->comment("when we clear a project 3's users");
$I->sendPATCH("/api/projects/{$project_3_id}/relationships/users", $new_users);

$I->expect("should overwrite existing users (users 2 & 3), resulting in no users");
$project_3 = Project::find($project_3_id);
$I->assertEmpty($project_3->users->toArray());

// ====================================================
// update project 1's tasks with tasks 2 & 3
// ====================================================

// an update like this is handled by a custom request handler (see ProjectsController::updateRelated)

$new_tasks = [
    'data' => [
        [ 'type' => 'tasks', 'id' => $task_2_id ],
        [ 'type' => 'tasks', 'id' => $task_3_id ],
    ]
];

// ----------------------------------------------------

$I->comment("when we update a project 1's tasks with tasks 2 & 3");
$I->sendPATCH("/api/projects/{$project_1_id}/relationships/tasks", $new_tasks);

$I->expect("should overwrite existing tasks (task 1), resulting in only tasks 2 & 3");
$project_1 = Project::find($project_1_id);
$project_1_task_ids = array_column($project_1->tasks->toArray(), 'id');
$I->assertContains($task_2_id, $project_1_task_ids);
$I->assertContains($task_3_id, $project_1_task_ids);
$I->assertNotContains($task_1_id, $project_1_task_ids);
