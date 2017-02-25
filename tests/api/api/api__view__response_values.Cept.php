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

$I->comment("given 10 users");
factory(User::class, 10)->create();
$I->assertSame(10, User::all()->count());

$I->comment("given 10 projects");
factory(Project::class, 10)->create([ 'status' => Project::STATUS_INCOMPLETE ]);
$I->assertSame(10, Project::all()->count());

$I->comment("given 10 tasks");
factory(Task::class, 10)->create([ 'project_id' => 1,  'status' => Project::STATUS_INCOMPLETE ]);
$I->assertSame(10, Task::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * view resource
// * test response values
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// view user 1
// ====================================================

$user_ids = array_column(User::all()->toArray(), 'id');
$user_1_id = $user_ids[0];

// ----------------------------------------------------

$I->comment("when we view user 1");
$I->sendGET("/api/users/{$user_1_id}");

// ----------------------------------------------------
// 1) top-level links
// ----------------------------------------------------

$I->expect("should return correct top-level self link");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users\/1$/');

// ----------------------------------------------------
// 2) id & type
// ----------------------------------------------------

$I->expect("should return correct type & id values in resource object");
$I->seeResponseJsonPathSame('$.data.type', 'users');
$I->seeResponseJsonPathSame('$.data.id', "{$user_1_id}");

// ----------------------------------------------------
// 3) attributes
// ----------------------------------------------------

$I->expect("should return correct attributes for the entity");
$I->seeResponseJsonPathType('$.data.attributes.name', 'string:!empty');
$I->seeResponseJsonPathType('$.data.attributes.email', 'string:!empty');

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
// view project 1
// ====================================================

$project_ids = array_column(Project::all()->toArray(), 'id');
$project_1_id = $project_ids[0];
$project = Fixtures::get('project');
$project['data']['attributes']['name'] = "BBB";

// ----------------------------------------------------

$I->comment("when we view project 1");
$I->sendGET("/api/projects/{$project_1_id}");

// ----------------------------------------------------
// 1) top-level links
// ----------------------------------------------------

$I->expect("should return correct top-level self link");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/projects\/1$/');

// ----------------------------------------------------
// 2) id & type
// ----------------------------------------------------

$I->expect("should return correct type & id values in resource object");
$I->seeResponseJsonPathSame('$.data.type', 'projects');
$I->seeResponseJsonPathSame('$.data.id', "{$project_1_id}");

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
// view task
// ====================================================

$task_ids = array_column(Task::all()->toArray(), 'id');
$task_1_id = $task_ids[0];
$task = Fixtures::get('task');
$task['data']['attributes']['name'] = "BBB";

// ----------------------------------------------------

$I->comment("when we view task 1");
$I->sendGET("/api/tasks/{$task_1_id}");

// ----------------------------------------------------
// 1) top-level links
// ----------------------------------------------------

$I->expect("should return correct top-level self link");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/tasks\/1$/');

// ----------------------------------------------------
// 2) id & type
// ----------------------------------------------------

$I->expect("should return correct type & id values in resource object");
$I->seeResponseJsonPathSame('$.data.type', 'tasks');
$I->seeResponseJsonPathSame('$.data.id', "{$task_1_id}");

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
