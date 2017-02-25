<?php

use Codeception\Util\Fixtures;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// before
//
///////////////////////////////////////////////////////

$I->comment("given no users");
$I->assertSame(0, User::all()->count());

$I->comment("given no projects");
$I->assertSame(0, Project::all()->count());

$I->comment("given no tasks");
$I->assertSame(0, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * create resource
// * test response values
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// create user
// ====================================================

$I->comment("when we create a user");
$I->sendPOST('/api/users', Fixtures::get('user'));

// ----------------------------------------------------
// 1) top-level links
// ----------------------------------------------------

$I->expect("top-level self link should include newly created id");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users\/1$/');

// ----------------------------------------------------
// 2) id & type
// ----------------------------------------------------

$I->expect("should return correct type & id values in resource object");
$I->seeResponseJsonPathSame('$.data.type', 'users');
$I->seeResponseJsonPathSame('$.data.id', '1');

// ----------------------------------------------------
// 3) attributes
// ----------------------------------------------------

$I->expect("should return correct attributes for the entity");
$I->seeResponseJsonPathType('$.data.attributes.name', 'string:!empty');

$I->expect("attributes object should not include type or id");
$I->seeNotResponseJsonPath('$.data.attributes.type');
$I->seeNotResponseJsonPath('$.data.attributes.id');

// ----------------------------------------------------
// 4) relationships
// ----------------------------------------------------

// ... tasks

$I->expect("should not return a 'tasks' relationship (because it is not a 'default include')");
$I->seeNotResponseJsonPath('$.data.relationships.tasks');

// ... projects

$I->expect("should return a 'projects' relationship");
$I->seeResponseJsonPathType('$.data.relationships.projects', 'array:!empty');

// ... projects ... links

$I->expect("should return links for 'projects' relationship");
$I->seeResponseJsonPathType('$.data.relationships.projects.links', 'array:!empty');

$I->expect("should return self & related links for 'projects' relationship");
$I->seeResponseJsonPathRegex('$.data.relationships.projects.links.self', '/^http\:\/\/[^\/]+\/api\/users\/\d+\/relationships\/projects/');
$I->seeResponseJsonPathRegex('$.data.relationships.projects.links.related', '/^http\:\/\/[^\/]+\/api\/users\/\d+\/projects/');

// ====================================================
// create project
// ====================================================

$I->comment("when we create a project");
$I->sendPOST('/api/projects', Fixtures::get('project'));

// ----------------------------------------------------
// 1) top-level links
// ----------------------------------------------------

$I->expect("top-level self link should include newly created id");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/projects\/1$/');

// ----------------------------------------------------
// 2) id & type
// ----------------------------------------------------

$I->expect("should return correct type & id values in resource object");
$I->seeResponseJsonPathSame('$.data.type', 'projects');
$I->seeResponseJsonPathSame('$.data.id', '1');

// ----------------------------------------------------
// 3) attributes
// ----------------------------------------------------

$I->expect("should return correct attributes for the entity");
$I->seeResponseJsonPathType('$.data.attributes.name', 'string:!empty');

$I->expect("attributes object should include all those set during creation, even if they were not not included in request");
$I->seeResponseJsonPathSame('$.data.attributes.status', Project::STATUS_INCOMPLETE);

$I->expect("attributes object should not include type or id");
$I->seeNotResponseJsonPath('$.data.attributes.type');
$I->seeNotResponseJsonPath('$.data.attributes.id');

// ----------------------------------------------------
// 4) relationships
// ----------------------------------------------------

// ... users

$I->expect("should not return a 'users' relationship (because it is not a 'default include')");
$I->seeNotResponseJsonPath('$.data.relationships.users');

// ... tasks

$I->expect("should return a 'tasks' relationship");
$I->seeResponseJsonPathType('$.data.relationships.tasks', 'array:!empty');

// ... tasks ... links

$I->expect("should return links for 'tasks' relationship");
$I->seeResponseJsonPathType('$.data.relationships.tasks.links', 'array:!empty');

$I->expect("should return self & related links for 'tasks' relationship");
$I->seeResponseJsonPathRegex('$.data.relationships.tasks.links.self', '/^http\:\/\/[^\/]+\/api\/projects\/\d+\/relationships\/tasks/');
$I->seeResponseJsonPathRegex('$.data.relationships.tasks.links.related', '/^http\:\/\/[^\/]+\/api\/projects\/\d+\/tasks/');

// ====================================================
// create task
// ====================================================

$I->comment("when we create a task");
$I->sendPOST('/api/tasks', Fixtures::get('task'));

// ----------------------------------------------------
// 1) top-level links
// ----------------------------------------------------

$I->expect("top-level self link should include newly created id");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/tasks\/1$/');

// ----------------------------------------------------
// 2) id & type
// ----------------------------------------------------

$I->expect("should return correct type & id values in resource object");
$I->seeResponseJsonPathSame('$.data.type', 'tasks');
$I->seeResponseJsonPathSame('$.data.id', '1');

// ----------------------------------------------------
// 3) attributes
// ----------------------------------------------------

$I->expect("should return correct attributes for the entity");
$I->seeResponseJsonPathType('$.data.attributes.name', 'string:!empty');

$I->expect("attributes object should include all those set during creation, even if they were not not included in request");
$I->seeResponseJsonPathSame('$.data.attributes.status', Task::STATUS_INCOMPLETE);

$I->expect("attributes object should not include type or id");
$I->seeNotResponseJsonPath('$.data.attributes.type');
$I->seeNotResponseJsonPath('$.data.attributes.id');

$I->expect("attributes object should not include any foreign keys");
$I->seeNotResponseJsonPath('$.data.attributes.project_id');

// ----------------------------------------------------
// 4) relationships
// ----------------------------------------------------

// ... users

$I->expect("should not return a 'users' relationship (because it is not a 'default include')");
$I->seeNotResponseJsonPath('$.data.relationships.users');

// ... projects

$I->expect("should return a 'projects' relationship");
$I->seeResponseJsonPathType('$.data.relationships.projects', 'array:!empty');

// ... projects ... links

$I->expect("should return links for 'projects' relationship");
$I->seeResponseJsonPathType('$.data.relationships.projects.links', 'array:!empty');

$I->expect("should return self & related links for 'projects' relationship");
$I->seeResponseJsonPathRegex('$.data.relationships.projects.links.self', '/^http\:\/\/[^\/]+\/api\/tasks\/\d+\/relationships\/projects/');
$I->seeResponseJsonPathRegex('$.data.relationships.projects.links.related', '/^http\:\/\/[^\/]+\/api\/tasks\/\d+\/projects/');
