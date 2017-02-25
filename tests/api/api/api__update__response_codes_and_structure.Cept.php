<?php

use Codeception\Util\Fixtures;
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

$I->comment("given 10 tasks");
factory(Task::class, 10)->create([ 'project_id' => 1,  'status' => Project::STATUS_INCOMPLETE ]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * update resource
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// update resource
// ====================================================

$I->comment("when we update a resource");

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];
$user = Fixtures::get('user');
$user['data']['id'] = $user_1_id;
$user['data']['attributes']['name'] = "BBB";

$project_ids = array_column(Project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];
$project = Fixtures::get('project');
$project['data']['id'] = $project_1_id;
$project['data']['attributes']['name'] = "BBB";

$task_ids = array_column(Task::all()->toArray(), 'id');
$task_1_id = $task_ids[0];
$task = Fixtures::get('task');
$task['data']['id'] = $task_1_id;
$task['data']['attributes']['name'] = "BBB";

$requests = [
    [ 'PATCH', "/api/users/{$user_1_id}", $user ],
    [ 'PATCH', "/api/projects/{$project_1_id}", $project ],
    [ 'PATCH', "/api/tasks/{$task_1_id}", $task ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) resource updated -> 200 OK
    //
    // Specs:
    // "A server MUST return a 200 OK status code if an
    // update is successful."
    //
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // 2) top-level links
    // ----------------------------------------------------

    $I->expect("top-level self link should include newly created id");
    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+$/');

    // ----------------------------------------------------
    // 2) id & type
    //
    // Specs:
    // "A resource object MUST contain ... id."
    // "A resource object MUST contain ... type."
    //
    // ----------------------------------------------------

    $I->expect("should return id for resource object as string");
    $I->seeResponseJsonPathType('$.data.id', 'string:!empty');

    $I->expect("should return type for resource object as string");
    $I->seeResponseJsonPathType('$.data.type', 'string:!empty');

    // ----------------------------------------------------
    // 3) attributes
    //
    // Specs:
    // "a resource object MAY contain ... attributes: an
    // attributes object representing some of the
    // resource’s data."
    //
    // ----------------------------------------------------

    $I->expect("should return an attributes object");
    $I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

    // ----------------------------------------------------
    // 4) attributes (id & type)
    //
    // Specs:
    // "a resource can not have ... an attribute or
    // relationship named type or id"
    //
    // ----------------------------------------------------

    $I->expect("attributes object should not include type or id");
    $I->seeNotResponseJsonPath('$.data.attributes.type');
    $I->seeNotResponseJsonPath('$.data.attributes.id');

    // ----------------------------------------------------
    // 5) attributes (same name)
    //
    // Specs:
    // "a resource can not have an attribute and
    // relationship with the same name."
    //
    // ----------------------------------------------------

    // TODO: test

    // ----------------------------------------------------
    // 6) attributes (foreign keys)
    //
    // Specs:
    // "Although has-one foreign keys (e.g. author_id) are
    // often stored internally alongside other information
    // to be represented in a resource object, these keys
    // SHOULD NOT appear as attributes."
    //
    // ----------------------------------------------------

    $I->expect("attributes object should not include any foreign keys");
    $attributes = $I->grabResponseJsonPath('$.data.attributes');
    $unique_attributes = array_reduce($attributes, function ($carry, $obj) {
        return array_unique(array_merge($carry, array_keys($obj)));
    }, []);
    $I->assertNotContainsRegex('/(.*?)\_id$/', $unique_attributes);

    // ----------------------------------------------------
    // 7) links
    //
    // A single item resource that is not a sub resource
    // request, should not contain a links object.
    //
    // ----------------------------------------------------

    $I->expect("should not return a links object");
    $I->seeNotResponseJsonPath('$.data.links');

    // ----------------------------------------------------
    // 8) meta
    //
    // Specs:
    // "a resource object MAY contain ...
    // meta: a meta object containing non-standard
    // meta-information about a resource that can not be
    // represented as an attribute or relationship."
    //
    // ----------------------------------------------------

    $I->expect("should not return meta for entity");
    $I->seeNotResponseJsonPath('$.data.meta');

    // ----------------------------------------------------
    // 9) relationships
    //
    // Specs:
    // "a resource object MAY contain ...
    // relationships: a relationships object describing
    // relationships between the resource and other JSON
    // API resources."
    //
    // ----------------------------------------------------

    $I->expect("should return a relationships object for entity");
    $I->seeResponseJsonPathType('$.data.relationships', 'array:!empty');

    // ... links

    $I->expect("should return links for each relationship");
    $I->seeResponseJsonPathType('$.data.relationships[*].links', 'array:!empty');

    $I->expect("should return self & related links");
    $I->seeResponseJsonPathRegex('$.data.relationships[*].links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/relationships\/\w+$/');
    $I->seeResponseJsonPathRegex('$.data.relationships[*].links.related', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');

});
