<?php

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

// projects

// ... owned by user 3
$I->comment("given 5 projects owned by user 3");
factory(Project::class, 5)->create(['user_id' => 2]);

// ... no owner
$I->comment("and 5 projects with no owner");
factory(Project::class, 5)->create();

$I->assertSame(10, Project::all()->count());

// tasks

// ... belonging to project 1
// ... owned by user 2
$I->comment("given 5 tasks for project 1, and owned by user 2");
factory(Task::class, 5)->create(['project_id' => 1, 'user_id' => 2]);

// ... no project
// ... no owner
$I->comment("and 5 tasks with no project or owner");
factory(Task::class, 5)->create();

$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * sub resource request (many to one)
//   (...resource/{id}/resource)
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$project_ids = array_column(project::all()->toArray(), 'id');
$task_ids = array_column(task::all()->toArray(), 'id');

// ====================================================
// has results
// eg. tasks/1/project
// ====================================================

// ----------------------------------------------------
//
// Specs:
// "Data, including resources and relationships, can
// be fetched by sending a GET request to an endpoint."
//
// ----------------------------------------------------

$I->comment("when we make a sub resource request to a resource with a many to one relationship with the primary resource");

$project_1_id = $project_ids[0];
$task_1_id = $task_ids[0];

$requests = [
    [ 'GET', "/api/tasks/{$task_1_id}/project" ],
    [ 'GET', "/api/tasks/{$task_1_id}/owner" ],
    [ 'GET', "/api/projects/{$project_1_id}/owner" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) sub resource request -> 200 OK
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // 2) top-level links
    //
    // My assumptions:
    // A sub resource request should return a top-level
    // links object containing a self link, and perhaps
    // a related link (not sure about the related link).
    //
    // ----------------------------------------------------

    $I->expect("should return a top-level links object");
    $I->seeResponseJsonPathType('$.links', 'array:!empty');

    // ... self link

    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');

    // ... related link

    // TODO: should a sub resource request contain a related link and what should it be?

    // ----------------------------------------------------
    // 3) data
    //
    // My assumptions:
    // A sub resource request that returns multiple
    // entities, should include a full resource object for
    // each entity.
    //
    // ----------------------------------------------------

    $I->expect("should return data as a single resource objects");
    $I->seeResponseJsonPathType('$.data', 'array:!empty'); // TODO: how to test this?

    // ----------------------------------------------------
    // ... resource object
    // ----------------------------------------------------

    $I->expect("resource object should should include type, id, attributes & links");
    $I->seeResponseJsonPathType('$.data.type', 'string:!empty');
    $I->seeResponseJsonPathType('$.data.id', 'string:!empty');
    $I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');
    $I->seeResponseJsonPathType('$.data.links', 'array:!empty');

    // ----------------------------------------------------
    // ... resource objects : links
    //
    // My assumptions:
    // The self links for each resource object for a sub
    // resource request, should not be sub resource links,
    // but instead should be primary resource links.
    // eg. ../projects/1 instead of ../users/123/projects/1
    //
    // ----------------------------------------------------

    $I->expect("should return links object, including a self link");
    $I->seeResponseJsonPathRegex('$.data.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+$/');

    // TODO: should to one sub resources return relationships object?

});

// ====================================================
// has no results
// eg. users/2/projects
// ====================================================

$I->comment("when we make a sub resource request to a resource with a many to one relationship with the primary resource, but there are no results");

$task_6_id = $task_ids[5];
$project_6_id = $project_ids[5];

$requests = [
    [ 'GET', "/api/tasks/{$task_6_id}/project" ],
    [ 'GET', "/api/tasks/{$task_6_id}/owner" ],
    [ 'GET', "/api/projects/{$project_6_id}/owner" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

//    var_dump($I->grabResponseAsJson());die();

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) sub resource request -> 200 OK
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // 2) top-level links
    // ----------------------------------------------------

    $I->expect("should return a top-level links object, including self link");
    $I->seeResponseJsonPathType('$.links', 'array:!empty');
    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');

    // ----------------------------------------------------
    // 3) data
    //
    // Specs:
    // "Resource linkage MUST be represented as one of the
    // following ... null for empty to-one relationships."
    //
    // ----------------------------------------------------

    $I->expect("should return null for data");
    $I->seeResponseJsonPathNull('$.data');

});
