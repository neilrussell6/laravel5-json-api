<?php

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
factory(Task::class, 10)->create(['project_id' => 1]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * index resource
// * test response values
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// index users
// ====================================================

$user_ids = array_column(User::all()->toArray(), 'id');

$I->comment("when we index users");
$I->sendGet('/api/users');

// ----------------------------------------------------
// 1) top-level links
// ----------------------------------------------------

$I->expect("should include correct top-level self link");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users$/');

// ----------------------------------------------------
// 1) id & type
// ----------------------------------------------------

$I->expect("should return correct type value in each resource object");
$I->seeResponseJsonPathSame('$.data[*].type', 'users');

$I->expect("should return correct id values in each resource object");
$I->seeResponseJsonPathSame('$.data[0].id', "{$user_ids[0]}");
$I->seeResponseJsonPathSame('$.data[1].id', "{$user_ids[1]}");
$I->seeResponseJsonPathSame('$.data[2].id', "{$user_ids[2]}");
$I->seeResponseJsonPathSame('$.data[3].id', "{$user_ids[3]}");
$I->seeResponseJsonPathSame('$.data[4].id', "{$user_ids[4]}");
$I->seeResponseJsonPathSame('$.data[5].id', "{$user_ids[5]}");
$I->seeResponseJsonPathSame('$.data[6].id', "{$user_ids[6]}");
$I->seeResponseJsonPathSame('$.data[7].id', "{$user_ids[7]}");
$I->seeResponseJsonPathSame('$.data[8].id', "{$user_ids[8]}");
$I->seeResponseJsonPathSame('$.data[9].id', "{$user_ids[9]}");

// ----------------------------------------------------
// 2) attributes
// ----------------------------------------------------

$I->expect("should return correct attributes for each entity");
$I->seeResponseJsonPathType('$.data[*].attributes.name', 'string:!empty');
$I->seeResponseJsonPathType('$.data[*].attributes.email', 'string:!empty');

$I->expect("attributes object should not include type or id");
$I->seeNotResponseJsonPath('$.data[*].attributes.type');
$I->seeNotResponseJsonPath('$.data[*].attributes.id');

// ----------------------------------------------------
// 3) links
// ----------------------------------------------------

$I->expect("should return correct self link for each entity");
$I->seeResponseJsonPathRegex('$.data[*].links.self', '/^http\:\/\/[^\/]+\/api\/users\/\d+$/');

// ====================================================
// index projects
// ====================================================

// TODO: test

// ====================================================
// index tasks
// ====================================================

// TODO: test
