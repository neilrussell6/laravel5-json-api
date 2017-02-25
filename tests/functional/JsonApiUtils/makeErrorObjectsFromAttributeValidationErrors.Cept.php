<?php

use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;
use Codeception\Util\HttpCode;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeErrorObjectsFromAttributeValidationErrors
//
///////////////////////////////////////////////////////

$I->wantTo("given an array of attribute validation error messages, make an array error objects from  for JSON API response");

//-----------------------------------------------------
// 2 messages & no status arg
//-----------------------------------------------------

$I->comment("given 2 messages & no status arg");

$attribute_validation_error_messages = [
    'email' => [ "not a valid email address." ],
    'name' => [ "name field is required." ]
];
$result = JsonApiUtils::makeErrorObjectsFromAttributeValidationErrors($attribute_validation_error_messages);

//-----------------------------------------------------

$I->expect("should call makeErrorObjects with correctly formatted error messages and 422 http code");
// TODO: test

//-----------------------------------------------------
// unique message
//-----------------------------------------------------

$I->comment("given an error message that contains the word 'unique'");

$attribute_validation_error_messages = [
    'email' => [ "email field must be unique." ],
    'name' => [ "name field is required." ]
];
$result = JsonApiUtils::makeErrorObjectsFromAttributeValidationErrors($attribute_validation_error_messages);

//-----------------------------------------------------

$I->expect("error object for message containing 'unique' should include 409 status");
// TODO: test

$I->expect("error object for message not containing 'unique' should include 422 status");
// TODO: test
