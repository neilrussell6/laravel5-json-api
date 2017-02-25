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
////
// Test
//
// * index resource with pagination
// * test response values
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// index users
// ====================================================

$user_ids = array_column(User::all()->toArray(), 'id');

// ----------------------------------------------------
// 1) page 1 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 1 of 5");
$I->sendGET('/api/users?page[limit]=2&page[offset]=1');

//-----------------------------------------------------

$I->expect("should return first 2 entities");
$ids = $I->grabResponseJsonPath('$.data[*].id');
$I->assertContains($user_ids[0], $ids);
$I->assertContains($user_ids[1], $ids);

// ----------------------------------------------------
// 2) page 2 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 2 of 5");
$I->sendGET('/api/users?page[limit]=2&page[offset]=2');

//-----------------------------------------------------

$I->expect("should return records 3 & 4");
$ids = $I->grabResponseJsonPath('$.data[*].id');
$I->assertContains($user_ids[2], $ids);
$I->assertContains($user_ids[3], $ids);

// ----------------------------------------------------
// 3) page 3 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 3 of 5");
$I->sendGET('/api/users?page[limit]=2&page[offset]=3');

//-----------------------------------------------------

$I->expect("should return records 5 & 6");
$ids = $I->grabResponseJsonPath('$.data[*].id');
$I->assertContains($user_ids[4], $ids);
$I->assertContains($user_ids[5], $ids);

// ----------------------------------------------------
// 4) page 4 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 4 of 5");
$I->sendGET('/api/users?page[limit]=2&page[offset]=4');

//-----------------------------------------------------

$I->expect("should return records 7 & 8");
$ids = $I->grabResponseJsonPath('$.data[*].id');
$I->assertContains($user_ids[6], $ids);
$I->assertContains($user_ids[7], $ids);

// ----------------------------------------------------
// 5) page 5 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 5 of 5");
$I->sendGET('/api/users?page[limit]=2&page[offset]=5');

//-----------------------------------------------------

$I->expect("should return records 9 & 10");
$ids = $I->grabResponseJsonPath('$.data[*].id');
$I->assertContains($user_ids[8], $ids);
$I->assertContains($user_ids[9], $ids);
