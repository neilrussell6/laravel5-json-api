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

$I->comment("given 10 users");
factory(User::class, 10)->create();
$I->assertSame(10, User::all()->count());

$I->comment("given 10 projects");
factory(Project::class, 10)->create();
$I->assertSame(10, Project::all()->count());

$I->comment("given 10 tasks");
factory(Task::class, 10)->create(['project_id' => 1]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * update resource
// * test data is updated
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// update user 1
// ====================================================

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];
$user = Fixtures::get('user');
$user['data']['id'] = $user_1_id;
$user['data']['attributes']['name'] = "BBB";

// ----------------------------------------------------

$I->comment("when we update a user");
$I->sendPATCH("/api/users/{$user_1_id}", $user);

$I->expect("should change user 1 name");
$user_1 = User::find($user_1_id)->toArray();
$I->assertSame("BBB", $user_1['name']);

// ====================================================
// update project 1
// ====================================================

$project_ids = array_column(Project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];
$project = Fixtures::get('project');
$project['data']['id'] = $project_1_id;
$project['data']['attributes']['name'] = "BBB";

// ----------------------------------------------------

$I->comment("when we update a project");
$I->sendPATCH("/api/projects/{$project_1_id}", $project);

$I->expect("should change project 1 name");
$project_1 = Project::find($project_1_id)->toArray();
$I->assertSame("BBB", $project_1['name']);

// ====================================================
// update task 1
// ====================================================

$task_ids = array_column(Task::all()->toArray(), 'id');
$task_1_id = $task_ids[0];
$task = Fixtures::get('task');
$task['data']['id'] = $task_1_id;
$task['data']['attributes']['name'] = "BBB";

// ----------------------------------------------------

$I->comment("when we update a task");
$I->sendPATCH("/api/tasks/{$task_1_id}", $task);

$I->expect("should change task 1 name");
$task_1 = Task::find($task_1_id)->toArray();
$I->assertSame("BBB", $task_1['name']);
