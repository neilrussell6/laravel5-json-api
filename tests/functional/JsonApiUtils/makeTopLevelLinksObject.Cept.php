<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeTopLevelLinksObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a resource object links object for JSON API response");

//-----------------------------------------------------
// relationships request
//-----------------------------------------------------

$I->comment("given the base url of a relationships request");

$request_base_url = "http://aaa.bbb.ccc/api/users/123/relationships/projects";
$result = JsonApiUtils::makeTopLevelLinksObject($request_base_url);

//-----------------------------------------------------

$I->expect("should return self & related links");
$I->seeJsonPath($result, '$.related');
$I->seeJsonPath($result, '$.self');

$I->expect("related link should be the provided request_base_url without 'relationships/'");
$I->seeJsonPathSame($result, '$.related', 'http://aaa.bbb.ccc/api/users/123/projects');

//-----------------------------------------------------
// specific primary resource request (view or update)
//-----------------------------------------------------

$I->comment("given the base url of a specific primary resource request (view or update)");

$request_base_url = "http://aaa.bbb.ccc/api/users/33";
$result = JsonApiUtils::makeTopLevelLinksObject($request_base_url);

//-----------------------------------------------------

$I->expect("should only return a self link");
$I->seeJsonPath($result, '$.self');
$I->seeNotJSONPath($result, '$.related');

$I->expect("self link should be the provided request_base_url");
$I->seeJsonPathSame($result, '$.self', 'http://aaa.bbb.ccc/api/users/33');

//-----------------------------------------------------
// any other response
//  - sub resource request
//  - primary resource request (index or create)
//-----------------------------------------------------

// with resource id

$I->comment("given the base url of a primary resource request or sub resource request, and a resource id");

$request_base_url = "http://aaa.bbb.ccc/api/users";
$resource_id = 123;
$result = JsonApiUtils::makeTopLevelLinksObject($request_base_url, $resource_id);

//-----------------------------------------------------

$I->expect("should only return a self link");
$I->seeJsonPath($result, '$.self');
$I->seeNotJSONPath($result, '$.related');

$I->expect("self link should be resource id appended to provided request_base_url");
$I->seeJsonPathSame($result, '$.self', 'http://aaa.bbb.ccc/api/users/123');

// without resource id

$I->comment("given the base url of a primary resource request or sub resource request, but no resource id");

$request_base_url = "http://aaa.bbb.ccc/api/users";
$result = JsonApiUtils::makeTopLevelLinksObject($request_base_url);

//-----------------------------------------------------

$I->expect("should only return a self link");
$I->seeJsonPath($result, '$.self');
$I->seeNotJSONPath($result, '$.related');

$I->expect("self link should be provided request_base_url");
$I->seeJsonPathSame($result, '$.self', 'http://aaa.bbb.ccc/api/users');
