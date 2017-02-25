<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeResponseObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a JSON API formatted response object");

$I->comment("given a response");

$repsonse = [
    'aaa' => 123
];

$result = JsonApiUtils::makeResponseObject($repsonse);

//-----------------------------------------------------

$I->expect("should add top level jsonapi object with version to provided response");
$I->seeJsonPathType($result, '$.jsonapi', 'array:!empty');
$I->seeJsonPathSame($result, '$.jsonapi.version', '1.0');
$I->seeJsonPathSame($result, '$.aaa', $repsonse['aaa']);
