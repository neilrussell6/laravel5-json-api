<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Codeception\Util\HttpCode;

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
factory(Task::class, 10)->create(['project_id' => 1]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * index resource
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// index resource
// ====================================================

$I->comment("when we index a resource");

$requests = [
    [ 'GET', '/api/users' ],
    [ 'GET', '/api/projects' ],
    [ 'GET', '/api/tasks' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) index resource -> 200 OK
    //
    // Specs:
    // "A server MUST respond to a successful request to
    // fetch ... resource collection with a 200 OK
    // response."
    //
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // 2) top-level links
    // ----------------------------------------------------

    $I->expect("should include top-level self link");
    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+$/');

    // ----------------------------------------------------
    // 3) id & type
    //
    // Specs:
    // "A resource object MUST contain ... id."
    // "A resource object MUST contain ... type."
    //
    // ----------------------------------------------------

    $I->expect("should return type value for each resource object");
    $I->seeResponseJsonPathType('$.data[*].type', 'string:!empty');

    $I->expect("should return ids for each resource object as strings");
    $I->seeResponseJsonPathType('$.data[*].id', 'string:!empty');

    // ----------------------------------------------------
    // 4) attributes
    //
    // Specs:
    // "a resource object MAY contain ... attributes: an
    // attributes object representing some of the
    // resource’s data."
    //
    // ----------------------------------------------------

    $I->expect("should return an attributes object for each entity, containing it's visible properties");
    $I->seeResponseJsonPathType('$.data[*].attributes', 'array:!empty');

    // ----------------------------------------------------
    // 5) attributes (id & type)
    //
    // Specs:
    // "a resource can not have ... an attribute or
    // relationship named type or id"
    //
    // ----------------------------------------------------

    $I->expect("attributes object should not include type or id");
    $I->seeNotResponseJsonPath('$.data[*].attributes.type');
    $I->seeNotResponseJsonPath('$.data[*].attributes.id');

    // ----------------------------------------------------
    // 6) attributes (same name)
    //
    // Specs:
    // "a resource can not have an attribute and
    // relationship with the same name."
    //
    // ----------------------------------------------------

    // TODO: test

    // ----------------------------------------------------
    // 7) attributes (foreign keys)
    //
    // Specs:
    // "Although has-one foreign keys (e.g. author_id) are
    // often stored internally alongside other information
    // to be represented in a resource object, these keys
    // SHOULD NOT appear as attributes."
    //
    // ----------------------------------------------------

    $I->expect("attributes object should not include any foreign keys");
    $attributes = $I->grabResponseJsonPath('$.data[*].attributes');
    $unique_attributes = array_reduce($attributes, function ($carry, $obj) {
        return array_unique(array_merge($carry, array_keys($obj)));
    }, []);
    $I->assertNotContainsRegex('/(.*?)\_id$/', $unique_attributes);

    // ----------------------------------------------------
    // 8) links
    //
    // Specs:
    // "a resource object MAY contain ...
    // links: a links object containing links related to
    // the resource."
    //
    // ----------------------------------------------------

    $I->expect("should return a links object for each entity containing only a self property");
    $I->seeResponseJsonPathType('$.data[*].links', 'array:!empty');
    $I->seeResponseJsonPathRegex('$.data[*].links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+$/');

    // ----------------------------------------------------
    // 9) meta
    //
    // Specs:
    // "a resource object MAY contain ...
    // meta: a meta object containing non-standard
    // meta-information about a resource that can not be
    // represented as an attribute or relationship."
    //
    // ----------------------------------------------------

    $I->expect("should not return meta for any entities");
    $I->seeNotResponseJsonPath('$.data[*].meta');

    // ----------------------------------------------------
    // 10) relationships
    //
    // Specs:
    // "a resource object MAY contain ...
    // relationships: a relationships object describing
    // relationships between the resource and other JSON
    // API resources."
    //
    // ----------------------------------------------------

    $I->expect("should not return relationships for any entities in an index request");
    $I->seeNotResponseJsonPath('$.data[*].relationships');

});
