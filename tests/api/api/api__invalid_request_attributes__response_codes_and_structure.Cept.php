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

$I->comment("given 10 users");
factory(User::class, 10)->create();
$I->assertSame(10, User::all()->count());

$I->comment("given 10 projects");
factory(Project::class, 10)->create();
$I->assertSame(10, Project::all()->count());

$I->comment("given 10 tasks");
factory(Task::class, 10)->create([ 'project_id' => 1,  'status' => Project::STATUS_INCOMPLETE ]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * invalid request attributes
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
// 422 UNPROCESSABLE_ENTITY
// ----------------------------------------------------

$I->comment("when we attempt to create or update a user, but provide data that does not pass the entities attribute validation");

$user = Fixtures::get('user');

$no_password_confiramtion_user = $user;
unset($no_password_confiramtion_user['data']['attributes']['password_confirmation']);

$invalid_password_user = $user;
$invalid_password_user['data']['attributes']['password'] = '123';
$invalid_password_user['data']['attributes']['password_confirmation'] = '123';

$requests = [
    [ 'POST', "/api/users", $no_password_confiramtion_user ],
    [ 'POST', "/api/users", $invalid_password_user ],
    [ 'PATCH', "/api/users/{$user_1_id}", $invalid_password_user ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 422 HTTP code");
    $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);

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
    $I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::UNPROCESSABLE_ENTITY);
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