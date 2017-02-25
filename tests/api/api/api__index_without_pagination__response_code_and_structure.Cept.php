<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Codeception\Util\HttpCode;

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
factory(Task::class, 10)->create(['project_id' => 1]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * index resource with pagination
// * test response codes & structure
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// index resource
// ====================================================

$I->comment("when we make an index request and do not provide any pagination arguments, and the result count falls within the PAGINATION_LIMIT");

$requests = [
    [ 'GET', '/api/users' ],
    [ 'GET', '/api/projects' ],
    [ 'GET', '/api/tasks' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // response code
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // top-level links
    // ----------------------------------------------------

    $I->expect("should return a top-level links object");
    $I->seeResponseJsonPathType('$.links', 'array:!empty');

    // ... self link

    $I->expect("the top-level links object should include a self link");
    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/\w+$/');

    // ... pagination links

    $I->expect("the top-level links object should not include any pagination links");
    $I->seeNotResponseJsonPath('$.links.first');
    $I->seeNotResponseJsonPath('$.links.last');
    $I->seeNotResponseJsonPath('$.links.next');
    $I->seeNotResponseJsonPath('$.links.prev');

    // ----------------------------------------------------
    // meta
    // ----------------------------------------------------

    $I->expect("should not return any pagination meta");
    $I->seeNotResponseJsonPath('$.meta');

});
