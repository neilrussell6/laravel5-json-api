<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// before
//
///////////////////////////////////////////////////////

$I->comment("given 1 user");
factory(User::class, 1)->create(['email' => 'aaa@bbb.ccc']);
$I->assertSame(1, User::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * invalid request attribute uniqueness
// * response codes and structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// users
// ====================================================

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];

// ----------------------------------------------------
// 409 CONFLICT
// ----------------------------------------------------

$I->comment("when we attempt to update a user, but provide an email that already exists");

$same_email_user = Fixtures::get('user');
$same_email_user['data']['id'] = $user_1_id;
$same_email_user['data']['attributes']['email'] = 'aaa@bbb.ccc';

$requests = [
    [ 'PATCH', "/api/users/{$user_1_id}", $same_email_user ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 409 HTTP code");
    $I->seeResponseCodeIs(HttpCode::CONFLICT);

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
    $I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::CONFLICT);
    $I->seeResponseJsonPathType('$.errors[0].title', 'string:!empty');
    $I->seeResponseJsonPathType('$.errors[0].detail', 'string:!empty');
});

// ====================================================
// projects
// ====================================================

// TODO: test

// ====================================================
// tasks
// ====================================================

// TODO: test