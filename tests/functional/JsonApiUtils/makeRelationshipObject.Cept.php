<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeRelationshipObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a relationship object for JSON API formatted response");

//-----------------------------------------------------
// all required values
//-----------------------------------------------------

$I->comment("given all required values");

$sub_resource_name = "tasks";
$base_url = "http://aaa.bbb.ccc/ddd/1";

$result = JsonApiUtils::makeRelationshipObject($sub_resource_name, $base_url);

//-----------------------------------------------------

$I->expect("should return correctly formatted relationship object");
$I->seeJsonPathType($result, '$.links', 'array:!empty');
$I->seeJsonPathSame($result, '$.links.self', 'http://aaa.bbb.ccc/ddd/1/relationships/tasks');
$I->seeJsonPathSame($result, '$.links.related', 'http://aaa.bbb.ccc/ddd/1/tasks');
