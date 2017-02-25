<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeResourceObjectLinkObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a resource object links object for JSON API response");

//-----------------------------------------------------
// primary resource request (index or create)
//-----------------------------------------------------

$I->comment("given the base url of a primary resource request");

$request_base_url = "http://aaa.bbb.ccc/api/users";
$resource_id = 123;

$result = JsonApiUtils::makeResourceObjectLinksObject($request_base_url, $resource_id);

//-----------------------------------------------------

$I->expect("should only return a self link");
$I->seeJsonPath($result, '$.self');
$I->seeNotJSONPath($result, '$.related');

$I->expect("self link should be the resource's id appended to the base url");
$I->seeJsonPathSame($result, '$.self', 'http://aaa.bbb.ccc/api/users/123');

//-----------------------------------------------------
// specific primary resource request (view or update)
//-----------------------------------------------------

// eg. http://aaa.bbb.ccc/api/users/1

// doesn't happen because the resource object will not
// contain a link object when the top-level data member
// is not an array

//-----------------------------------------------------
// relationships request
//-----------------------------------------------------

// eg. http://aaa.bbb.ccc/api/users/1/relationships/projects

// doesn't happen because relationships requests return
// resource identifier objects which do not include a
// links object

//-----------------------------------------------------
// sub resource request
//-----------------------------------------------------

$I->comment("given the base url of a sub resource request");

$request_base_url = "http://aaa.bbb.ccc/api/users/1/projects";
$resource_id = 123;

$result = JsonApiUtils::makeResourceObjectLinksObject($request_base_url, $resource_id);

//-----------------------------------------------------

$I->expect("should only return a self link");
$I->seeJsonPath($result, '$.self');
$I->seeNotJSONPath($result, '$.related');

$I->expect("self link should not include primary resource's endpoint");
$I->seeJsonPathSame($result, '$.self', 'http://aaa.bbb.ccc/api/projects/123');
