<?php

use App\Models\Project;
use App\Models\Task;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// before
//
///////////////////////////////////////////////////////

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
// * delete resource
// * test data is deleted
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// delete project 1
// ====================================================

$project_ids = array_column(Project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];

// ----------------------------------------------------

$I->comment("when we delete a project");
$I->sendDELETE("/api/projects/{$project_1_id}");

$I->expect("should have 1 less record");
$I->assertSame(9, Project::all()->count());

$I->expect("project 1 should no longer exist");
$I->assertNotContains($project_1_id, array_column(Project::all()->toArray(), 'id'));

// ====================================================
// delete task 1
// ====================================================

$task_ids = array_column(Task::all()->toArray(), 'id');
$task_1_id = $task_ids[0];

// ----------------------------------------------------

$I->comment("when we delete a task");
$I->sendDELETE("/api/tasks/{$task_1_id}");

$I->expect("should have 1 less record");
$I->assertSame(9, Task::all()->count());

$I->expect("task 1 should no longer exist");
$I->assertNotContains($task_1_id, array_column(Task::all()->toArray(), 'id'));
