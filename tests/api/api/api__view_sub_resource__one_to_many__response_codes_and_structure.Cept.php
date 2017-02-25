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

$I->comment("given 10 users");
factory(User::class, 10)->create();
$I->assertSame(10, User::all()->count());

$I->comment("given 10 projects");
factory(Project::class, 10)->create();
$I->assertSame(10, Project::all()->count());

$I->comment("given 5 tasks for project 1");
factory(Task::class, 5)->create(['project_id' => 1]);
$I->comment("and 5 tasks for project 2");
factory(Task::class, 5)->create(['project_id' => 2]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * sub resource request (one to many)
//   (...resource/{id}/resource)
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// has results
// eg. projects/1/tasks
// ====================================================

// ----------------------------------------------------
//
// Specs:
// "Data, including resources and relationships, can
// be fetched by sending a GET request to an endpoint."
//
// ----------------------------------------------------

$I->comment("when we make a sub resource request to a resource with a one to many relationship with the primary resource");

$project_ids = array_column(project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];
$project_2_id = $project_ids[1];

$requests = [
    [ 'GET', "/api/projects/{$project_1_id}/tasks" ],
    [ 'GET', "/api/projects/{$project_2_id}/tasks" ],
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

    $I->expect("should return data as an array of resource objects");
    $I->seeResponseJsonPathType('$.data', 'array:!empty');

    // ----------------------------------------------------
    // ... resource objects
    // ----------------------------------------------------

    $I->expect("should include a resource object for each entity, including type, id, attributes & links");
    $I->seeResponseJsonPathType('$.data[*].type', 'string:!empty');
    $I->seeResponseJsonPathType('$.data[*].id', 'string:!empty');
    $I->seeResponseJsonPathType('$.data[*].attributes', 'array:!empty');
    $I->seeResponseJsonPathType('$.data[*].links', 'array:!empty');

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
    $I->seeResponseJsonPathRegex('$.data[*].links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+$/');

});

// ====================================================
// has no results
// eg. users/2/projects
// ====================================================

$I->comment("when we make a sub resource request to a resource with a one to many relationship with the primary resource, but there are no results");

$project_ids = array_column(project::all()->toArray(), 'id');
$project_3_id = $project_ids[2];

$requests = [
    [ 'GET', "/api/projects/{$project_3_id}/tasks" ],
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
    // ----------------------------------------------------

    $I->expect("should return a top-level links object, including self link");
    $I->seeResponseJsonPathType('$.links', 'array:!empty');
    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');

    // ----------------------------------------------------
    // 3) data
    //
    // Specs:
    // "Resource linkage MUST be represented as one of the
    // following ... an empty array ([]) for empty to-many
    // relationships."
    //
    // ----------------------------------------------------

    $I->expect("should return empty array as data");
    $I->seeResponseJsonPathType('$.data', 'array:empty');

});
