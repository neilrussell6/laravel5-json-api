<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;
use Codeception\Util\HttpCode;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeErrorObjects
//
///////////////////////////////////////////////////////

$I->wantTo("make an array error objects for JSON API response");

//-----------------------------------------------------
// 2 empty messages & no status arg
//-----------------------------------------------------

$I->comment("given 2 empty messages & no status arg");
$error_messages = [[], []];
$result = JsonApiUtils::makeErrorObjects($error_messages);

//-----------------------------------------------------

$I->expect("should return an array of error objects");
$I->seeJsonPathType($result, '$.', 'array:!empty');

//-----------------------------------------------------

$I->expect("should return 422 status member for each error object if non is provided in each message");
$I->seeJsonPathSame($result, '$.[*].status', HttpCode::UNPROCESSABLE_ENTITY);

$I->expect("should not add optional members if no values are provided");
$I->seeNotJSONPath($result, '$.[*].id');
$I->seeNotJSONPath($result, '$.[*].links');
$I->seeNotJSONPath($result, '$.[*].about');
$I->seeNotJSONPath($result, '$.[*].code');
$I->seeNotJSONPath($result, '$.[*].title');
$I->seeNotJSONPath($result, '$.[*].detail');
$I->seeNotJSONPath($result, '$.[*].source');
$I->seeNotJSONPath($result, '$.[*].pointer');
$I->seeNotJSONPath($result, '$.[*].parameter');

//-----------------------------------------------------
// 2 empty messages & status arg
//-----------------------------------------------------

$I->comment("2 empty messages & status arg");
$error_messages = [[], []];
$result = JsonApiUtils::makeErrorObjects($error_messages, HttpCode::UNAUTHORIZED);

//-----------------------------------------------------

$I->expect("should return status argument for each error object if non is provided in each message");
$I->seeJsonPathSame($result, '$.[*].status', HttpCode::UNAUTHORIZED);

//-----------------------------------------------------
// 2 messages, 1 with status, 1 without & status arg
//-----------------------------------------------------

$I->comment("2 messages with statuses & status arg");
$error_messages = [
    [
        'status' => HttpCode::BAD_GATEWAY,
        'id' => "AAA1",
        'about' => "BBB1",
        'code' => "CCC1",
        'detail' => "DDD1",
        'links' => "EEE1",
        'meta' => "FFF1",
        'pointer' => "GGG1",
        'parameter' => "HHH1",
        'source' => "III1"
    ],
    [
        'id' => "AAA2",
        'code' => "CCC2",
        'links' => "EEE2",
        'pointer' => "GGG2",
        'source' => "III2"
    ]
];

$result = JsonApiUtils::makeErrorObjects($error_messages, HttpCode::CONFLICT);

//-----------------------------------------------------

$I->expect("should add optional members if values are provided");

$I->seeJsonPathSame($result, '$.[0].id', "AAA1");
$I->seeJsonPathSame($result, '$.[0].about', "BBB1");
$I->seeJsonPathSame($result, '$.[0].code', "CCC1");
$I->seeJsonPathSame($result, '$.[0].detail', "DDD1");
$I->seeJsonPathSame($result, '$.[0].links', "EEE1");
$I->seeJsonPathSame($result, '$.[0].meta', "FFF1");
$I->seeJsonPathSame($result, '$.[0].pointer', "GGG1");
$I->seeJsonPathSame($result, '$.[0].parameter', "HHH1");
$I->seeJsonPathSame($result, '$.[0].source', "III1");

$I->seeJsonPathSame($result, '$.[1].id', "AAA2");
$I->seeNotJSONPath($result, '$.[1].about');
$I->seeJsonPathSame($result, '$.[1].code', "CCC2");
$I->seeNotJSONPath($result, '$.[1].detail');
$I->seeJsonPathSame($result, '$.[1].links', "EEE2");
$I->seeNotJSONPath($result, '$.[1].meta');
$I->seeJsonPathSame($result, '$.[1].pointer', "GGG2");
$I->seeNotJSONPath($result, '$.[1].parameter');
$I->seeJsonPathSame($result, '$.[1].source', "III2");

//-----------------------------------------------------

$I->expect("1st message should use individually provided status");
$I->seeJsonPathSame($result, '$.[0].status', HttpCode::BAD_GATEWAY);

//-----------------------------------------------------

$I->expect("2nd message should use status argument");
$I->seeJsonPathSame($result, '$.[1].status', HttpCode::CONFLICT);
