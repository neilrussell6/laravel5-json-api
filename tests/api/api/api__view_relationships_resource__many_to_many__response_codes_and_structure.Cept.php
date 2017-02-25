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

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];
$user_2_id = $user_ids[1];
$user_3_id = $user_ids[2];
$user_4_id = $user_ids[3];

// projects

$I->comment("given 10 projects");
factory(Project::class, 10)->create();
$I->assertSame(10, Project::all()->count());

$project_ids = array_column(User::all()->toArray(), 'id');
$project_1_id = $project_ids[0];
$project_2_id = $project_ids[1];
$project_3_id = $project_ids[2];

// ... shared with user 1
$I->comment("given user 1 is associated with the first 5 projects");
Project::paginate(5)->getCollection()->map(function ($project) use($user_1_id) {
    $project->users()->attach($user_1_id);
});

// ... has user 1 as editor
$I->comment("given user 1 is editor on project 1");
Project::find($project_1_id)->users()->sync([ $user_1_id => [ 'is_editor' => true ] ], false); // the false stops sync from overriding existing values in the pivot table

// tasks

// ... belonging to project 1
$I->comment("given 10 tasks");
factory(Task::class, 10)->create([ 'project_id' => $project_1_id ]);
$I->assertSame(10, Task::all()->count());

$task_ids = array_column(User::all()->toArray(), 'id');
$task_1_id = $task_ids[0];
$task_2_id = $task_ids[1];
$task_3_id = $task_ids[2];

// ... shared with user 1
$I->comment("given user 1 is associated with the first 5 tasks");
Task::paginate(5)->getCollection()->map(function ($task) use ($user_1_id) {
    $task->users()->attach($user_1_id);
});

// ... have users 2 & 3 as editor (will also share with users 2 & 3)
$I->comment("given user's 2 & 3 are editors on task 1");
Task::find($task_1_id)->editors()->sync([ $user_2_id => [ 'is_editor' => true ], $user_3_id => [ 'is_editor' => true ] ], false); // the false stops sync from overriding existing values in the pivot table

///////////////////////////////////////////////////////
//
// Test
//
// * relationships request (many to many)
//   (...resource/{id}/relationships/resource)
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// has results
// eg. users/1/relationships/projects
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

$I->comment("when we make a relationships request to a resource with a many to many relationship with the primary resource");

$requests = [
    [ 'GET', "/api/users/{$user_1_id}/relationships/projects" ],
    [ 'GET', "/api/users/{$user_1_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$project_1_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$project_1_id}/relationships/editors" ],
    [ 'GET', "/api/projects/{$project_1_id}/relationships/users" ],
    [ 'GET', "/api/tasks/{$task_1_id}/relationships/editors" ],
    [ 'GET', "/api/tasks/{$task_1_id}/relationships/users" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) relationships request -> 200 OK
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

    $I->expect("should return data as an array of resource identifier objects");
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

    $I->expect("should return type value for each resource identifier object");
    $I->seeResponseJsonPathType('$.data[*].type', 'string:!empty');

    $I->expect("should return ids for each resource identifier object as strings");
    $I->seeResponseJsonPathType('$.data[*].id', 'string:!empty');

    // ----------------------------------------------------
    // ... attributes & links
    //
    // My assumptions:
    // A “resource identifier object” must not contain
    // attributes or links.
    // 
    // ----------------------------------------------------

    $I->expect("should not return attributes, links or relationships for any resource identifier objects");
    $I->seeNotResponseJsonPath('$.data[*].attributes');
    $I->seeNotResponseJsonPath('$.data[*].links');
    $I->seeNotResponseJsonPath('$.data[*].relationships');

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

$I->comment("when we make a relationships request to a resource with a many to many relationship with the primary resource, and there are no results");

$requests = [
    [ 'GET', "/api/users/{$user_2_id}/relationships/projects" ],
    [ 'GET', "/api/users/{$user_4_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$project_2_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$project_2_id}/relationships/editors" ],
    [ 'GET', "/api/tasks/{$task_2_id}/relationships/editors" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) relationships request -> 200 OK
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
    // "A server MUST respond to a successful request to
    // fetch a resource collection with an array of
    // resource objects or an empty array ([]) as the
    // response document’s primary data."
    //
    // ----------------------------------------------------

    $I->expect("should return empty array as data");
    $I->seeResponseJsonPathType('$.data', 'array:empty');

});
