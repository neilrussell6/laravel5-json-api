<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// Test
//
// * create resource
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// create resource
// ====================================================

$I->comment("when we create a resource");

$requests = [
    [ 'POST', '/api/users', Fixtures::get('user') ],
    [ 'POST', '/api/projects', Fixtures::get('project') ],
    [ 'POST', '/api/tasks', Fixtures::get('task') ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // 1) create resource -> 201 Created
    //
    // Specs:
    // "the server MUST return either a 201 Created status
    // code and response document (as described above) or
    // a 204 No Content status code with no response
    // document."
    //
    // ----------------------------------------------------

    $I->expect("should return 201 HTTP code");
    $I->seeResponseCodeIs(HttpCode::CREATED);

    // ----------------------------------------------------
    // 2) top-level links
    // ----------------------------------------------------

    $I->expect("top-level self link should include newly created id");
    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+$/');

    // ----------------------------------------------------
    // 3) id & type
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
    // 4) attributes
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
    // 5) attributes (id & type)
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
    $attributes = $I->grabResponseJsonPath('$.data.attributes');
    $unique_attributes = array_reduce($attributes, function ($carry, $obj) {
        return array_unique(array_merge($carry, array_keys($obj)));
    }, []);
    $I->assertNotContainsRegex('/(.*?)\_id$/', $unique_attributes);

    // ----------------------------------------------------
    // 8) links
    //
    // A single item resource that is not a sub resource
    // request, should not contain a links object.
    //
    // ----------------------------------------------------

    $I->expect("should not return a links object");
    $I->seeNotResponseJsonPath('$.data.links');

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
    $I->seeNotResponseJsonPath('$.data.meta');

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

    $I->expect("should return a relationships object for entity");
    $I->seeResponseJsonPathType('$.data.relationships', 'array:!empty');

    // ... links

    $I->expect("should return links for each relationship");
    $I->seeResponseJsonPathType('$.data.relationships[*].links', 'array:!empty');

    $I->expect("should return self & related links");
    $I->seeResponseJsonPathRegex('$.data.relationships[*].links.self', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/relationships\/\w+$/');
    $I->seeResponseJsonPathRegex('$.data.relationships[*].links.related', '/^http\:\/\/[^\/]+\/api\/\w+\/\d+\/\w+$/');

});
