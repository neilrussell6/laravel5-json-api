<?php

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// Test
//
// * invalid request headers
// * test response codes and structure
// 
///////////////////////////////////////////////////////

// ====================================================
// 415 UNSUPPORTED_MEDIA_TYPE
// ====================================================

// ----------------------------------------------------
// 1) JSON API Content-Type header with media type params
//
// Specs:
// "Servers MUST respond with a 415 Unsupported Media
// Type status code if a request specifies the header
// Content-Type: application/vnd.api+json with any
// media type parameters."
//
// ----------------------------------------------------

$I->comment("when we make a request that includes the JSON API Content-Type header with media type params");
$I->haveHttpHeader('Content-Type', 'application/vnd.api+json; version=1');

$I->sendGET('/api');
// TODO: test other methods & endpoints

// ----------------------------------------------------

$I->expect("should return 415 HTTP code");
$I->seeResponseCodeIs(HttpCode::UNSUPPORTED_MEDIA_TYPE);

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
$I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::UNSUPPORTED_MEDIA_TYPE);
$I->seeResponseJsonPathType('$.errors[0].title', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[0].detail', 'string:!empty');

// ====================================================
// 406 NOT_ACCEPTABLE
// ====================================================

// ----------------------------------------------------
// 2) JSON API Accept with media type params
//
// Specs:
// "Servers MUST respond with a 406 Not Acceptable
// status code if a request’s Accept header contains
// the JSON API media type and all instances of that
// media type are modified with media type parameters."
//
// ----------------------------------------------------

$I->comment("when we make a GET request that includes the JSON API media type in an Accept header, but with media type params");
$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json; version=1');

$I->sendGET('/api');
// TODO: test other methods & endpoints

// ----------------------------------------------------

$I->expect("should return 406 HTTP code");
$I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);

// ----------------------------------------------------

$I->expect("should return an array of errors");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');

// ----------------------------------------------------

$I->expect("should return a single error object in errors array");
$errors = $I->grabResponseJsonPath('$.errors[*]');
$I->assertSame(count($errors), 1);

// ----------------------------------------------------

$I->expect("error object should contain a status, title and detail member");
$I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::NOT_ACCEPTABLE);
$I->seeResponseJsonPathType('$.errors[0].title', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[0].detail', 'string:!empty');
