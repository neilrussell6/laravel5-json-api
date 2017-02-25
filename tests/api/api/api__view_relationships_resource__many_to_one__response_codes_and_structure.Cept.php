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
$I->comment("given 5 projects owned by user 2");
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
// * relationships request (many to one)
//   (...resource/{id}/relationships/resource)
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$project_ids = array_column(Project::all()->toArray(), 'id');
$task_ids = array_column(Task::all()->toArray(), 'id');

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
// "A server MUST support fetching relationship data
// for every relationship URL provided as a self link
// as part of a relationship’s links object."
//
// ----------------------------------------------------

$I->comment("when we make a relationships resource request to a resource with a many to one relationship with the primary resource");

$project_1_id = $project_ids[0];
$task_1_id = $task_ids[0];

$requests = [
    [ 'GET', "/api/tasks/{$task_1_id}/relationships/project" ],
    [ 'GET', "/api/tasks/{$task_1_id}/relationships/owner" ],
    [ 'GET', "/api/projects/{$project_1_id}/relationships/owner" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) sub resource request -> 200 OK
    //
    // Specs:
    // "A server MUST respond to a successful request to
    // fetch a relationship with a 200 OK response."
    //
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // 2) top-level links
    //
    // Specs:
    // "The top-level links object MAY contain self and
    // related links, as described above for relationship
    // objects."
    //
    // ----------------------------------------------------

    $I->expect("should return a top-level links object");
    $I->seeResponseJsonPathType('$.links', 'array:!empty');

    // ----------------------------------------------------
    // self link
    //
    // Specs:
    // "self: a link for the relationship itself
    // (a “relationship link”). This link allows the client
    // to directly manipulate the relationship. For
    // example, removing an author through an article’s
    // relationship URL would disconnect the person from
    // the article without deleting the people resource
    // itself. When fetched successfully, this link returns
    // the linkage for the related resources as its primary
    // data. (See Fetching Relationships.)"
    //
    // ----------------------------------------------------

    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/relationships\/\w+$/');

    // ----------------------------------------------------
    // related link
    //
    // Specs:
    // ??
    //
    // ----------------------------------------------------

    $I->seeResponseJsonPathRegex('$.links.related', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');

    // ----------------------------------------------------
    // 3) data
    //
    // Specs:
    // "A “relationship object” MUST contain at least one
    // of the following ... data: resource linkage"
    //
    // "... related resources can be requested from a
    // relationship endpoint ... /1/relationships/ ...
    // the primary data would be a collection of resource
    // identifier objects that represent linkage ..."
    //
    // ----------------------------------------------------

    $I->expect("should return data as a single resource identifier object");
    $I->seeResponseJsonPathType('$.data', 'array:!empty');

    // ----------------------------------------------------
    // ... id & type
    //
    // Specs:
    // "A “resource identifier object” is an object that
    // identifies an individual resource.
    // A “resource identifier object” MUST contain type and
    // id members."
    //
    // "A “resource identifier object” MAY also include a
    // meta member, whose value is a meta object that
    // contains non-standard meta-information."
    //
    // My assumptions:
    // A “resource identifier object” must not contain
    // attributes or links.
    //
    // ----------------------------------------------------

    $I->expect("should return type & id values for resource identifier object as strings");
    $I->seeResponseJsonPathType('$.data.type', 'string:!empty');
    $I->seeResponseJsonPathType('$.data.id', 'string:!empty');

    // ----------------------------------------------------
    // ... attributes & links
    //
    // My assumptions:
    // A “resource identifier object” must not contain
    // attributes or links.
    //
    // ----------------------------------------------------

    $I->expect("should not return attributes, links or relationships for resource identifier object");
    $I->seeNotResponseJsonPath('$.data.attributes');
    $I->seeNotResponseJsonPath('$.data.links');
    $I->seeNotResponseJsonPath('$.data.relationships');

});

// ====================================================
// has no results
// eg. users/2/relationships/projects
// ====================================================

// ----------------------------------------------------
//
// Specs:
// "A server MUST respond to a successful request to
// fetch a resource collection with an array of
// resource objects or an empty array ([]) as the
// response document’s primary data."
//
// ----------------------------------------------------

$I->comment("when we make a sub resource request to a resource with a many to one relationship with the primary resource, but there are no results");

$task_6_id = $task_ids[5];
$project_6_id = $project_ids[5];

$requests = [
    [ 'GET', "/api/tasks/{$task_6_id}/relationships/project" ],
    [ 'GET', "/api/tasks/{$task_6_id}/relationships/owner" ],
    [ 'GET', "/api/projects/{$project_6_id}/relationships/owner" ],
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

    $I->expect("should return a top-level links object, including self & related links");
    $I->seeResponseJsonPathType('$.links', 'array:!empty');
    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/relationships\/\w+$/');
    $I->seeResponseJsonPathRegex('$.links.related', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');

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
