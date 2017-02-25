<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// Test
//
// * unknown endpoint
// * test response error objects
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// Unknown endpoint
// ====================================================

$I->comment("when we make a request that results in an 404 error (unknown endpoint)");

$unknown = Fixtures::get('unknown');
$unknown_with_id = Fixtures::get('unknown');

$unknown_with_id['data']['id'] = 1;

$requests = [
    [ 'GET', '/api/unknown' ],
    [ 'POST', '/api/unknown', $unknown ],
    [ 'PATCH', '/api/unknown/1', $unknown_with_id ],
    [ 'DELETE', '/api/unknown/1' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 404 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

    // ----------------------------------------------------

    $I->expect("should not return a links object");
    $I->seeNotResponseJsonPath('$.links');
    
    // ----------------------------------------------------

    $I->expect("should return an array of errors");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

    // ----------------------------------------------------

    $I->expect("should return a single error object in errors array");
    $errors = $I->grabResponseJsonPath('$.errors[*]');
    $I->assertSame(count($errors), 1);

    // ----------------------------------------------------

    $I->expect("error object should contain a status, title and detail member");
    $I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::NOT_FOUND);
    $I->seeResponseJsonPathType('$.errors[0].title', 'string:!empty');
    $I->seeResponseJsonPathType('$.errors[0].detail', 'string:!empty');

});
