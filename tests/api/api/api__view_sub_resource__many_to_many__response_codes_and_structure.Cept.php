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

$I->comment("given 10 projects");
factory(Project::class, 10)->create();
$I->assertSame(10, Project::all()->count());

// ... shared with user 1
$I->comment("given user 1 is associated with the first 5 projects");
Project::paginate(5)->getCollection()->map(function ($project) {
    $project->users()->attach(1);
});

// ... has user 1 as editor
$I->comment("given user 1 is editor on task 1");
Project::find(1)->users()->sync([1 => ['is_editor' => true]], false); // the false stops sync from overriding existing values in the pivot table

// tasks

// ... belonging to project 1
$I->comment("given 10 tasks");
factory(Task::class, 10)->create(['project_id' => 1]);
$I->assertSame(10, Task::all()->count());

// ... shared with user 1
$I->comment("given user 1 is associated with the first 5 tasks");
Task::paginate(5)->getCollection()->map(function ($task) {
    $task->users()->attach(1);
});

// ... have users 2 & 3 as editor (will also share with users 2 & 3)
$I->comment("given user's 2 & 3 are editors on task 1");
Task::find(1)->editors()->sync([2 => ['is_editor' => true], 3 => ['is_editor' => true]], false); // the false stops sync from overriding existing values in the pivot table

///////////////////////////////////////////////////////
//
// Test
//
// * sub resource request (many to many)
//   (...resource/{id}/resource)
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$user_ids = array_column(User::all()->toArray(), 'id');
$project_ids = array_column(Project::all()->toArray(), 'id');
$task_ids = array_column(Task::all()->toArray(), 'id');

// ====================================================
// has results
// eg. users/1/projects
// ====================================================

// ----------------------------------------------------
//
// Specs:
// "Data, including resources and relationships, can
// be fetched by sending a GET request to an endpoint."
//
// ----------------------------------------------------

$I->comment("when we make a sub resource request to a resource with a many to many relationship with the primary resource");

$user_1_id = $user_ids[0];
$user_2_id = $user_ids[1];
$user_3_id = $user_ids[2];
$project_1_id = $project_ids[0];
$task_1_id = $task_ids[0];

$requests = [
    [ 'GET', "/api/users/{$user_1_id}/projects" ],
//    [ 'GET', "/api/users/{$user_1_id}/tasks" ],
//    [ 'GET', "/api/users/{$user_2_id}/tasks" ],
//    [ 'GET', "/api/users/{$user_3_id}/tasks" ],
//    [ 'GET', "/api/projects/{$project_1_id}/tasks" ],
//    [ 'GET', "/api/projects/{$project_1_id}/editors" ],
//    [ 'GET', "/api/tasks/{$task_1_id}/editors" ],
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

$I->comment("when we make a sub resource request to a resource with a many to many relationship with the primary resource, but there are no results");

$user_4_id = $user_ids[3];
$project_2_id = $project_ids[1];
$task_2_id = $task_ids[1];

//$requests = [
//    [ 'GET', "/api/users/{$user_2_id}/projects" ],
//    [ 'GET', "/api/users/{$user_4_id}/tasks" ],
//    [ 'GET', "/api/projects/{$project_2_id}/tasks" ],
//    [ 'GET', "/api/projects/{$project_2_id}/editors" ],
//    [ 'GET', "/api/tasks/{$task_2_id}/editors" ],
//];
//
//$I->sendMultiple($requests, function($request) use ($I) {
//
//    $I->comment("given we make a {$request[0]} request to {$request[1]}");
//
//    // ----------------------------------------------------
//    // 1) sub resource request -> 200 OK
//    // ----------------------------------------------------
//
//    $I->expect("should return 200 HTTP code");
//    $I->seeResponseCodeIs(HttpCode::OK);
//
//    // ----------------------------------------------------
//    // 2) top-level links
//    // ----------------------------------------------------
//
//    $I->expect("should return a top-level links object, including self link");
//    $I->seeResponseJsonPathType('$.links', 'array:!empty');
//    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');
//
//    // ----------------------------------------------------
//    // 3) data
//    //
//    // Specs:
//    // "Resource linkage MUST be represented as one of the
//    // following ... an empty array ([]) for empty to-many
//    // relationships."
//    //
//    // ----------------------------------------------------
//
//    $I->expect("should return empty array as data");
//    $I->seeResponseJsonPathType('$.data', 'array:empty');
//
//});
