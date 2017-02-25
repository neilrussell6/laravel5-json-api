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
// * test data is updated
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ----------------------------------------------------
// 
// Specs:
// "A server MUST respond to PATCH requests to a URL
// from a to-one relationship link ...
// The PATCH request MUST include a top-level member
// named data containing one of:
// - a resource identifier object corresponding to the
//   new related resource.
// - null, to remove the relationship.
//
// ----------------------------------------------------

// ====================================================
// update project 1's owner
// ====================================================

$new_owner = ['data' => ['type' => 'users', 'id' => $user_3_id ] ];

// ----------------------------------------------------

$I->comment("when we update a project's owner");
$I->sendPATCH("/api/projects/{$project_1_id}/relationships/owner", $new_owner);

$I->expect("should change project 1's owner to user 3");
$project_1 = Project::find($project_1_id);
$project_1_owner_id = $project_1->owner->id;
$I->assertSame($user_3_id, $project_1_owner_id);

// ====================================================
// update project 2's owner
// ====================================================

$new_owner = [ 'data' => [ 'type' => 'users', 'id' => $user_3_id ] ];

// ----------------------------------------------------

$I->comment("when we update a project's owner");
$I->sendPATCH("/api/projects/{$project_2_id}/relationships/owner", $new_owner);

$I->expect("should change project 1's owner to user 3");
$project_2 = Project::find($project_2_id);
$project_2_owner_id = $project_2->owner->id;
$I->assertSame($user_3_id, $project_2_owner_id);

// ====================================================
// update task 1's owner
// ====================================================

$new_owner = [ 'data' => [ 'type' => 'users', 'id' => $user_3_id ] ];

// ----------------------------------------------------

$I->comment("when we update a task's owner");
$I->sendPATCH("/api/tasks/{$task_1_id}/relationships/owner", $new_owner);

$I->expect("should change project 1's owner to user 3");
$task_1 = Task::find($task_1_id);
$task_1_owner_id = $task_1->owner->id;
$I->assertSame($user_3_id, $task_1_owner_id);

// ====================================================
// update task 2's owner
// ====================================================

$new_owner = [ 'data' => [ 'type' => 'users', 'id' => $user_3_id ] ];

// ----------------------------------------------------

$I->comment("when we update a project's owner");
$I->sendPATCH("/api/tasks/{$task_2_id}/relationships/owner", $new_owner);

$I->expect("should change project 1's owner to user 3");
$task_2 = Task::find($task_2_id);
$task_2_owner_id = $task_2->owner->id;
$I->assertSame($user_3_id, $task_2_owner_id);

// ====================================================
// update task 1's project
// ====================================================

$new_project = [ 'data' => [ 'type' => 'projects', 'id' => $project_2_id ] ];

// ----------------------------------------------------

$I->comment("when we update a task's project");
$I->sendPATCH("/api/tasks/{$task_1_id}/relationships/project", $new_project);

$I->expect("should change task 1's project to project 2");
$task_1 = Task::find($task_1_id);
$task_1_project_id = $task_1->project->id;
$I->assertSame($project_2_id, $task_1_project_id);

// ====================================================
// update task 2's project
// ====================================================

$new_project = [ 'data' => [ 'type' => 'projects', 'id' => $project_2_id ] ];

// ----------------------------------------------------

$I->comment("when we update a task's project");
$I->sendPATCH("/api/tasks/{$task_2_id}/relationships/project", $new_project);

$I->expect("should change task 1's project to project 2");
$task_2 = Task::find($task_2_id);
$task_2_project_id = $task_2->project->id;
$I->assertSame($project_2_id, $task_2_project_id);
