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

$I->comment("given no users");
$I->assertSame(0, User::all()->count());

$I->comment("given no projects");
$I->assertSame(0, Project::all()->count());

$I->comment("given no tasks");
$I->assertSame(0, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * create resource
// * test data is created
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// create user
// ====================================================

$I->comment("when we create a user");
$I->sendPOST('/api/users', Fixtures::get('user'));

$I->expect("should create 1 new record");
$I->assertSame(1, User::all()->count());

// ====================================================
// create project
// ====================================================

$I->comment("when we create a project");
$I->sendPOST('/api/projects', Fixtures::get('project'));

$I->expect("should create 1 new record");
$I->assertSame(1, Project::all()->count());

// ====================================================
// create task
// ====================================================

$project_1_id = Project::all()->toArray()[0]['id'];
$task = Fixtures::get('task');
$task['data']['attributes']['project_id'] = $project_1_id;

// ----------------------------------------------------

$I->comment("when we create a project");
$I->sendPOST('/api/tasks', $task);

$I->expect("should create 1 new record");
$I->assertSame(1, Task::all()->count());
