<?php

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// Test
//
// * valid request headers
// * response codes and structure
// 
///////////////////////////////////////////////////////

// ----------------------------------------------------
// 1)
//
// Request headers:
// * JSON API Content-Type
// * no Accept
//
// Response code:
// 200 OK
//
// Specs:
// "Clients MUST send all JSON API data in request
// documents with the header Content-Type:
// application/vnd.api+json without any media type
// parameters."
//
// ----------------------------------------------------

$I->comment("when we make a GET request to the api an include the JSON API Content-Type header, and JSON API media type in the Accept header");
$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');
$I->sendGET('/api');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

// ----------------------------------------------------
// 2)
//
// Request headers:
// * JSON API Content-Type
// * only JSON API Accept
//
// Response code:
// 200 OK
//
// Specs:
// "Clients that include the JSON API media type in
// their Accept header MUST specify the media type
// there at least once without any media type
// parameters."
//
// ----------------------------------------------------

$I->comment("when we make a GET request to the api an include the JSON API Content-Type header, and JSON API media type in the Accept header");
$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');
$I->sendGET('/api');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

// ----------------------------------------------------
// 3)
//
// Request headers:
// * JSON API Content-Type
// * JSON API Accept amongst other media types
//
// Response code:
// 200 OK
//
// ----------------------------------------------------

$I->comment("when we make a GET request to the api an include the JSON API Content-Type header, and JSON API media type amongst others in the Accept header");
$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'text/plain, application/vnd.api+json, application/json');
$I->sendGET('/api');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);
