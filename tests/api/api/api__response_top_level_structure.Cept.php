<?php
//
//use App\Models\Project;
//use App\Models\Task;
//use App\Models\User;
//use Codeception\Util\Fixtures;
//
//$I = new ApiTester($scenario);
//
/////////////////////////////////////////////////////////
////
//// before
////
/////////////////////////////////////////////////////////
//
//$I->comment("given 10 users");
//factory(User::class, 10)->create();
//$I->assertSame(10, User::all()->count());
//
//$I->comment("given 10 projects");
//factory(Project::class, 10)->create();
//$I->assertSame(10, Project::all()->count());
//
//$I->comment("given 10 tasks");
//factory(Task::class, 10)->create(['project_id' => 1]);
//$I->assertSame(10, Task::all()->count());
//
/////////////////////////////////////////////////////////
////
//// Test
////
//// * various requests
//// * test top level response structure
////
/////////////////////////////////////////////////////////
//
//$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
//$I->haveHttpHeader('Accept', 'application/vnd.api+json');
//
//$user_ids = array_column(User::all()->toArray(), 'id');
//$user_1_id = $user_ids[0];
//
//// ====================================================
//// all requests that return content (including errors)
//// ====================================================
//
//$I->comment("when we make any request that returns content (all expect delete)");
//
//$user = Fixtures::get('user');
//
//$update_user = $user;
//$update_user['data']['attributes']['email'] = "bbb@ccc.ddd"; // make email unique
//
//$invalid_password_user = $user;
//$invalid_password_user['data']['attributes']['password'] = '123';
//$invalid_password_user['data']['attributes']['password_confirmation'] = '123';
//
//$requests = [
//    // index
//    [ 'GET', '/api',  ],
//    [ 'GET', '/api/users' ],
//    [ 'GET', '/api/projects' ],
//    [ 'GET', '/api/tasks' ],
//    // create
//    [ 'POST', '/api/users', Fixtures::get('user') ],
//    [ 'POST', '/api/projects', Fixtures::get('project') ],
//    [ 'POST', '/api/tasks', Fixtures::get('task') ],
//    // update
//    [ 'PATCH', "/api/users/{$user_1_id}", $update_user ],
//    [ 'PATCH', "/api/projects/{$user_1_id}", Fixtures::get('project') ],
//    [ 'PATCH', "/api/tasks/{$user_1_id}", Fixtures::get('task') ],
//    // invalid attribute
//    [ 'POST', "/api/users", $invalid_password_user ],
//    [ 'PATCH', "/api/users/{$user_1_id}", $invalid_password_user ],
//];
//
//$I->sendMultiple($requests, function($request) use ($I) {
//
//    $I->comment("given we make a {$request[0]} request to {$request[1]}");
//
//    // ----------------------------------------------------
//    // 1) jsonapi.version
//    //
//    // Specs:
//    // "A document MAY contain ...
//    // jsonapi: an object describing the server’s
//    // implementation."
//    //
//    // ----------------------------------------------------
//
//    $I->expect("should return jsonapi & version");
//    $I->seeResponseJsonPath('$.jsonapi');
//    $I->seeResponseJsonPathSame('$.jsonapi.version', "1.0");
//
//    // ----------------------------------------------------
//    // 2) links
//    //
//    // Specs:
//    // "A document MAY contain ... links: a links object
//    // related to the primary data."
//    //
//    // ----------------------------------------------------
//
//    $I->expect("should return links");
//    $I->seeResponseJsonPath('$.links');
//
//    // ----------------------------------------------------
//    // 3) links.self
//    //
//    // Specs:
//    // "The top-level links object MAY contain ...
//    // self: the link that generated the current response
//    // document."
//    //
//    // ----------------------------------------------------
//
//    $I->expect("should return self link");
//    $I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api/');
//
//    // ----------------------------------------------------
//    // 4) links.related
//    //
//    // Specs:
//    // "The top-level links object MAY contain ...
//    // related: a related resource link when the primary
//    // data represents a resource relationship."
//    //
//    // ----------------------------------------------------
//
//    // TODO: test
//    //$I->comment("when we make any relationships request");
//    //$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
//    //$I->haveHttpHeader('Accept', 'application/vnd.api+json'); // this isn't required, but something, I think the Laravel5 Codeception module adds Accept headers, so for tests we need to be explicit.
//    //$I->sendGET('/api/users/1/relationships/tasks');
//    //
//    //$I->expect("should return related link");
//    //$I->seeResponseJsonPathRegex('$.links.related', '/^http\:\/\/[^\/]+\/api\/users\/1\/projects/');
//
//});
//
//// ====================================================
//// successful requests that return single entities
//// ====================================================
//
//$I->comment("when we make a request that results in a single entity (view, store, update)");
//
//$user = Fixtures::get('user');
//
//$new_user = $user;
//$new_user['data']['attributes']['email'] = "ccc@ddd.eee"; // make email unique
//
//$update_user = $user;
//$update_user['data']['attributes']['email'] = "ddd@eee.fff"; // make email unique
//
//$requests = [
//    // view
//    [ 'GET', "/api/users/{$user_1_id}" ],
//    [ 'GET', "/api/projects/{$user_1_id}" ],
//    [ 'GET', "/api/tasks/{$user_1_id}" ],
//    // create
//    [ 'POST', '/api/users', $new_user ],
//    [ 'POST', '/api/projects', Fixtures::get('project') ],
//    [ 'POST', '/api/tasks', Fixtures::get('task') ],
//    // update
//    [ 'PATCH', "/api/users/{$user_1_id}", $update_user ],
//    [ 'PATCH', "/api/projects/{$user_1_id}", Fixtures::get('project') ],
//    [ 'PATCH', "/api/tasks/{$user_1_id}", Fixtures::get('task') ],
//];
//
//$I->sendMultiple($requests, function($request) use ($I) {
//
//    $I->comment("given we make a {$request[0]} request to {$request[1]}");
//
//    // ----------------------------------------------------
//    // 5) data (single)
//    //
//    // Specs:
//    // "Primary data MUST be either ... a single resource
//    // object"
//    //
//    // ----------------------------------------------------
//
//    $I->expect("primary data should be an object");
//    $I->seeResponseJsonPathType('$.data', 'array:!empty'); // TODO: how can we test this is not and indexed array ?
//
//});
//
//// ====================================================
//// successful requests that return multiple entities
//// ====================================================
//
//$I->comment("when we make a request that results in multiple entities (index)");
//
//$requests = [
//    // index
//    [ 'GET', "/api/users" ],
//    [ 'GET', "/api/projects" ],
//    [ 'GET', "/api/tasks" ]
//];
//
//$I->sendMultiple($requests, function($request) use ($I) {
//
//    $I->comment("given we make a {$request[0]} request to {$request[1]}");
//
//    // ----------------------------------------------------
//    // 6) data (multiple)
//    //
//    // Specs:
//    // "Primary data MUST be either ... an array of
//    // resource objects"
//    //
//    // ----------------------------------------------------
//
//    $I->expect("primary data should be an array");
//    $I->seeResponseJsonPathType('$.data', 'array:!empty');
//
//});
