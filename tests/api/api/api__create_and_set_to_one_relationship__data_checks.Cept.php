<?php

use Illuminate\Support\Facades\Config;
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

$I->comment("given 1 users");
factory(User::class, 1)->create();
$I->assertSame(1, User::all()->count());

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];

// projects

$I->comment("given 1 project");
factory(Project::class, 1)->create();
$I->assertSame(1, Project::all()->count());

$project_ids = array_column(User::all()->toArray(), 'id');
$project_1_id = $project_ids[0];

///////////////////////////////////////////////////////
//
// Test
//
// * create resource and set 'to one' relationship
// * test data is created and relationships are set
//
///////////////////////////////////////////////////////

// disable ACL access check
Config::set('jsonapi.acl.check_access', false);

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

//// ====================================================
//// create project, set owner
//// TODO: test once implemented
//// ====================================================
//
//$I->comment("when we create a project and set the owner");
//
//$project = Fixtures::get('project');
//$project['data']['relationships'] = [
//    'owner' => [
//        'data' => [
//            'type' => 'users',
//            'id' => $user_1_id
//        ]
//    ]
//];
//$I->sendPOST('/api/projects', $project);
//dd($I->grabResponseAsJson());
//
//$I->expect("should create 1 new record");
//$I->assertSame(2, Project::all()->count());
//
//$I->expect("should set new record's owner to user 1");
//$new_project = Project::find(2);
//dd($new_project->owner);
//$I->assertSame($user_1_id, $new_project->owner->id);

//// ====================================================
//// create task, set owner & project
//// TODO: test once implemented
//// ====================================================
//
//$I->comment("when we create a task and set the owner and project");
//
//$task = Fixtures::get('task');
//$task['data']['relationships'] = [
//    'owner' => [
//        'data' => [
//            'type' => 'users',
//            'id' => $user_1_id
//        ]
//    ],
//    'project' => [
//        'data' => [
//            'type' => 'projects',
//            'id' => $project_1_id
//        ]
//    ]
//];
//$I->sendPOST('/api/tasks', $task);
//
//$I->expect("should create 1 new record");
//$I->assertSame(1, Task::all()->count());
//
//$new_task = Task::find(1);
//$I->expect("should set new record's owner to user 1");
//$I->assertSame($user_1_id, $new_task->owner->id);
//
//$I->expect("should set new record's project to project 1");
//$I->assertSame($project_1_id, $new_task->project->id);
