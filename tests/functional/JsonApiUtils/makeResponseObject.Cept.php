<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeResponseObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a JSON API formatted response object");

//-----------------------------------------------------
// data array
//-----------------------------------------------------

$I->comment("given a response containing a data array (even if empty)");

$repsonse = [
    'data' => []
];

$result = JsonApiUtils::makeResponseObject($repsonse);

//-----------------------------------------------------

$I->expect("should add top level jsonapi object with version to provided response");
$I->seeJsonPathType($result, '$.jsonapi', 'array:!empty');
$I->seeJsonPathSame($result, '$.jsonapi.version', '1.0');
$I->seeJsonPathType($result, '$.data', 'array:empty');

//-----------------------------------------------------
// errors array
//
// Specs:
// "Error objects MUST be returned as an array keyed by
// errors in the top level of a JSON API document."
//
//-----------------------------------------------------

$I->comment("given a response containing an errors array (even if empty)");

$repsonse = [
    'errors' => []
];

$result = JsonApiUtils::makeResponseObject($repsonse);

//-----------------------------------------------------

$I->expect("should add top level jsonapi object with version to provided response");
$I->seeJsonPathType($result, '$.jsonapi', 'array:!empty');
$I->seeJsonPathSame($result, '$.jsonapi.version', '1.0');
$I->seeJsonPathType($result, '$.errors', 'array:empty');

//-----------------------------------------------------
// meta object
//-----------------------------------------------------

$I->comment("given a response containing a meta object (even if empty)");

$repsonse = [
    'meta' => []
];

$result = JsonApiUtils::makeResponseObject($repsonse);

//-----------------------------------------------------

$I->expect("should add top level jsonapi object with version to provided response");
$I->seeJsonPathType($result, '$.jsonapi', 'array:!empty');
$I->seeJsonPathSame($result, '$.jsonapi.version', '1.0');
$I->seeJsonPathType($result, '$.meta', 'array:empty');

//-----------------------------------------------------
// no data, errors or meta properties
//
// Specs:
// "A document MUST contain at least one of the
// following top-level members:
//  - data: the document’s “primary data”
//  - errors: an array of error objects
//  - meta: a meta object that contains non-standard
//    meta-information."
//-----------------------------------------------------

$I->comment("given a response containing no data, errors or meta properties");

$repsonse = [
    'aaa' => 'asdasdsa'
];

$I->expect("should return false");
$I->assertFalse(JsonApiUtils::makeResponseObject($repsonse));

//-----------------------------------------------------
// both data & errors
//
// Specs:
// "The members data and errors MUST NOT coexist in the
// same document."
//-----------------------------------------------------

$I->comment("given a response containing no data, errors or meta properties");

$repsonse = [
    'data' => [],
    'errors' => [],
];

$I->expect("should return false");
$I->assertFalse(JsonApiUtils::makeResponseObject($repsonse));

