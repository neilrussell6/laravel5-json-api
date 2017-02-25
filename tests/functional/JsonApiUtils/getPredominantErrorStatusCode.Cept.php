<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::getPredominantErrorStatusCode
//
///////////////////////////////////////////////////////

$I->wantTo("make an error object for JSON API response");

//-----------------------------------------------------
// 2 409 errors & 1 422 error
//-----------------------------------------------------

$I->comment("given 2 409 errors & 1 422 error");
$error_messages = [
    [ 'status' => 409 ],
    [ 'status' => 409 ],
    [ 'status' => 422 ],
];
$result = JsonApiUtils::getPredominantErrorStatusCode($error_messages);

//-----------------------------------------------------

$I->expect("should return the majority status");
$I->assertSame($result, 409);

//-----------------------------------------------------
// 1 409 error & 2 422 errors
//-----------------------------------------------------

$I->comment("given 1 409 error & 2 422 errors");
$error_messages = [
    [ 'status' => 422 ],
    [ 'status' => 409 ],
    [ 'status' => 422 ],
];
$result = JsonApiUtils::getPredominantErrorStatusCode($error_messages);

//-----------------------------------------------------

$I->expect("should return the majority status");
$I->assertSame($result, 422);

//-----------------------------------------------------
// 2 409 errors & 2 422 errors
//-----------------------------------------------------

$I->comment("given 2 409 errors & 2 422 errors");
$error_messages = [
    [ 'status' => 422 ],
    [ 'status' => 409 ],
    [ 'status' => 422 ],
    [ 'status' => 409 ],
];
$result = JsonApiUtils::getPredominantErrorStatusCode($error_messages, 123);

//-----------------------------------------------------

$I->expect("should return the provided default status");
$I->assertSame($result, 123);
