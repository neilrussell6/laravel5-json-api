<?php
//
//use Codeception\Util\Fixtures;
//use App\Models\Project;
//use App\Models\Task;
//use App\Models\User;
//use Codeception\Util\HttpCode;
//
//$I = new ApiTester($scenario);
//
/////////////////////////////////////////////////////////
////
//// before
////
/////////////////////////////////////////////////////////
//
//$I->comment("given no users");
//$I->assertSame(0, User::all()->count());
//
//$I->comment("given no projects");
//$I->assertSame(0, Project::all()->count());
//
//$I->comment("given no tasks");
//$I->assertSame(0, Task::all()->count());
//
/////////////////////////////////////////////////////////
////
//// Test
////
//// * unknown endpoint
//// * test response error objects
////
/////////////////////////////////////////////////////////
//
//$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
//$I->haveHttpHeader('Accept', 'application/vnd.api+json');
//
//// ====================================================
//// Unknown endpoint
//// ====================================================
//
//$I->comment("when we make any request of a resource that does not exist");
//
//$requests = [
//    [ 'GET', '/api/users/123' ],
//    [ 'POST', '/api/users/123', Fixtures::get('user') ],
//    [ 'PATCH', '/api/users/123', Fixtures::get('user') ],
//    [ 'DELETE', '/api/users/123' ],
//    [ 'GET', '/api/projects/123' ],
//    [ 'POST', '/api/projects/123', Fixtures::get('project') ],
//    [ 'PATCH', '/api/projects/123', Fixtures::get('project') ],
//    [ 'DELETE', '/api/projects/123' ],
//    [ 'GET', '/api/tasks/123' ],
//    [ 'POST', '/api/tasks/123', Fixtures::get('task') ],
//    [ 'PATCH', '/api/tasks/123', Fixtures::get('task') ],
//    [ 'DELETE', '/api/tasks/123' ],
//];
//
//$I->sendMultiple($requests, function($request) use ($I) {
//
//    $I->comment("given we make a {$request[0]} request to {$request[1]}");
//
//    // ----------------------------------------------------
//    // 1) resource not found -> 404 Not Found
//    //
//    // Specs:
//    // "A server MUST return 404 Not Found when processing
//    // a request to modify a resource that does not exist."
//    //
//    // ----------------------------------------------------
//
//    $I->expect("should return 404 HTTP code");
//    $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
//
//    // ----------------------------------------------------
//
//    $I->expect("should not return a links object");
//    $I->seeNotResponseJsonPath('$.links');
//
//    // ----------------------------------------------------
//
//    $I->expect("should return an array of errors");
//    $I->seeResponseJsonPathType('$.errors', 'array:!empty');
//
//    // ----------------------------------------------------
//
//    $I->expect("should return a single error object in errors array");
//    $errors = $I->grabResponseJsonPath('$.errors[*]');
//    $I->assertSame(count($errors), 1);
//
//    // ----------------------------------------------------
//
//    $I->expect("error object should contain a status, title and detail member");
//    $I->seeResponseJsonPathSame('$.errors[0].status', HttpCode::NOT_FOUND);
//    $I->seeResponseJsonPathType('$.errors[0].title', 'string:!empty');
//    $I->seeResponseJsonPathType('$.errors[0].detail', 'string:!empty');
//
//});
