<?php

use Codeception\Util\HttpCode;

$I = new AcceptanceTester($scenario);

///////////////////////////////////////////////////////
//
// Test
//
// * test response headers
//
///////////////////////////////////////////////////////

// This Acceptance test tests response headers, while
// the API tests test request headers and response
// bodies.
//
// This is because we cannot test response headers in
// the API tests, as accessing response headers
// requires PHPBrowser, which conflicts with the
// Laravel5 Codeception module we use in the API tests.

// ----------------------------------------------------
// 1) Content-Type response headers
//
// Specs:
// "Servers MUST send all JSON API data in response
// documents with the header Content-Type:
// application/vnd.api+json without any media type
// parameters."
//
// ----------------------------------------------------

$I->comment("when we make a GET request to the api");
$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');

$I->sendGET('/api');
// TODO: test other methods & endpoints

$I->expect("should return Content-Type: application/vnd.api+json header without any media type parameters");
$I->seeHttpHeader('Content-Type', 'application/vnd.api+json');
$I->seeHttpHeaderOnce('Content-Type');
