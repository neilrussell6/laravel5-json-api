<?php

use Codeception\Util\HttpCode;
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
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// delete resource
// ====================================================

$I->comment("when we delete a resource");

$project_ids = array_column(Project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];

$task_ids = array_column(Task::all()->toArray(), 'id');
$task_1_id = $task_ids[0];

$requests = [
    [ 'DELETE', "/api/projects/{$project_1_id}" ],
    [ 'DELETE', "/api/tasks/{$task_1_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) delete resource -> 204 NO CONTENT
    //
    // Specs:
    // "A server MUST return a 204 No Content status code
    // if a deletion request is successful and no content
    // is returned."
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
