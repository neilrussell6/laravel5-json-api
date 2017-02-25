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

// ----------------------------------------------------
// Specs:
// "A server MAY choose to limit the number of
// resources returned in a response to a subset (“page”)
// of the whole set available.
//
// A server MAY provide links to traverse a paginated
// data set (“pagination links”).
//
// Pagination links MUST appear in the links object
// that corresponds to a collection. To paginate the
// primary data, supply pagination links in the
// top-level links object. To paginate an included
// collection returned in a compound document, supply
// pagination links in the corresponding links object.
//
// The following keys MUST be used for pagination links:
//
// first: the first page of data
// last: the last page of data
// prev: the previous page of data
// next: the next page of data
//
// Keys MUST either be omitted or have a null value to
// indicate that a particular link is unavailable.
//
// ----------------------------------------------------

// ----------------------------------------------------
// 1) page 1 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 1 of 5");

$requests = [
    [ 'GET', '/api/users?page[limit]=2&page[offset]=1' ],
    [ 'GET', '/api/projects?page[limit]=2&page[offset]=1' ],
    [ 'GET', '/api/tasks?page[limit]=2&page[offset]=1' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // response code
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // links
    // ----------------------------------------------------

    $I->expect("should return pagination link urls for all, except 'prev' link");
    $I->seeResponseJsonPathType('$.links.first', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.last', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.next', 'string:!empty');
    $I->seeResponseJsonPathSame('$.links.prev', null);
    $I->seeResponseJsonPathType('$.links.self', 'string:!empty');

    $I->expect("should include 'limit' query param all link urls, except 'prev' link");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Blimit\%5D\=2/');

    $I->expect("should include correct 'offset' query param all link urls, except 'prev' link");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Boffset\%5D\=1/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Boffset\%5D\=5/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Boffset\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Boffset\%5D\=1/');

    // ----------------------------------------------------
    // meta
    // ----------------------------------------------------

    $I->expect("should return correct pagination meta");
    $I->seeResponseJsonPathSame('$.meta.pagination.count', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.limit', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.offset', 1);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_items', 10);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_pages', 5);

    // ----------------------------------------------------
    // limit data
    // ----------------------------------------------------

    $I->expect("should limit return data");
    $data = $I->grabResponseJsonPath('$.data[*]');
    $I->assertSame(count($data), 2);

});

// ----------------------------------------------------
// 2) page 2 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 2 of 5");

$requests = [
    [ 'GET', '/api/users?page[limit]=2&page[offset]=2' ],
    [ 'GET', '/api/projects?page[limit]=2&page[offset]=2' ],
    [ 'GET', '/api/tasks?page[limit]=2&page[offset]=2' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // response code
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // links
    // ----------------------------------------------------

    $I->expect("should return pagination link urls for all links");
    $I->seeResponseJsonPathType('$.links.first', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.last', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.next', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.prev', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.self', 'string:!empty');

    $I->expect("should include provided 'limit' query param all link urls");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Blimit\%5D\=2/');

    $I->expect("should include correct 'offset' query param all link urls");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Boffset\%5D\=1/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Boffset\%5D\=5/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Boffset\%5D\=3/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Boffset\%5D\=1/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Boffset\%5D\=2/');

    // ----------------------------------------------------
    // meta
    // ----------------------------------------------------

    $I->expect("should return correct pagination meta");
    $I->seeResponseJsonPathSame('$.meta.pagination.count', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.limit', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.offset', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_items', 10);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_pages', 5);

    // ----------------------------------------------------
    // limit data
    // ----------------------------------------------------

    $I->expect("should limit return data");
    $data = $I->grabResponseJsonPath('$.data[*]');
    $I->assertSame(count($data), 2);

});

// ----------------------------------------------------
// 3) page 3 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 3 of 5");

$requests = [
    [ 'GET', '/api/users?page[limit]=2&page[offset]=3' ],
    [ 'GET', '/api/projects?page[limit]=2&page[offset]=3' ],
    [ 'GET', '/api/tasks?page[limit]=2&page[offset]=3' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // response code
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // links
    // ----------------------------------------------------

    $I->expect("should return pagination link urls for all links");
    $I->seeResponseJsonPathType('$.links.first', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.last', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.next', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.prev', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.self', 'string:!empty');

    $I->expect("should include provided 'limit' query param all link urls");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Blimit\%5D\=2/');

    $I->expect("should include correct 'offset' query param all link urls");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Boffset\%5D\=1/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Boffset\%5D\=5/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Boffset\%5D\=4/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Boffset\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Boffset\%5D\=3/');

    // ----------------------------------------------------
    // meta
    // ----------------------------------------------------

    $I->expect("should return correct pagination meta");
    $I->seeResponseJsonPathSame('$.meta.pagination.count', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.limit', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.offset', 3);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_items', 10);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_pages', 5);

    // ----------------------------------------------------
    // limit data
    // ----------------------------------------------------

    $I->expect("should limit return data");
    $data = $I->grabResponseJsonPath('$.data[*]');
    $I->assertSame(count($data), 2);

});

// ----------------------------------------------------
// 4) page 4 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 4 of 5");

$requests = [
    [ 'GET', '/api/users?page[limit]=2&page[offset]=4' ],
    [ 'GET', '/api/projects?page[limit]=2&page[offset]=4' ],
    [ 'GET', '/api/tasks?page[limit]=2&page[offset]=4' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // response code
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // links
    // ----------------------------------------------------

    $I->expect("should return pagination link urls for all links");
    $I->seeResponseJsonPathType('$.links.first', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.last', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.next', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.prev', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.self', 'string:!empty');

    $I->expect("should include provided 'limit' query param all link urls");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Blimit\%5D\=2/');

    $I->expect("should include correct 'offset' query param all link urls");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Boffset\%5D\=1/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Boffset\%5D\=5/');
    $I->seeResponseJsonPathRegex('$.links.next', '/\?.*page\%5Boffset\%5D\=5/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Boffset\%5D\=3/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Boffset\%5D\=4/');

    // ----------------------------------------------------
    // meta
    // ----------------------------------------------------

    $I->expect("should return correct pagination meta");
    $I->seeResponseJsonPathSame('$.meta.pagination.count', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.limit', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.offset', 4);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_items', 10);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_pages', 5);

    // ----------------------------------------------------
    // limit data
    // ----------------------------------------------------

    $I->expect("should limit return data");
    $data = $I->grabResponseJsonPath('$.data[*]');
    $I->assertSame(count($data), 2);

});

// ----------------------------------------------------
// 5) page 5 of 5
// ----------------------------------------------------

$I->comment("when we make a request that results in 10 entities (index) and provide a page.limit of 2 and a page.offset for page 5 of 5");

$requests = [
    [ 'GET', '/api/users?page[limit]=2&page[offset]=5' ],
    [ 'GET', '/api/projects?page[limit]=2&page[offset]=5' ],
    [ 'GET', '/api/tasks?page[limit]=2&page[offset]=5' ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------
    // response code
    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    // ----------------------------------------------------
    // links
    // ----------------------------------------------------

    $I->expect("should return pagination link urls for all links, except 'next' link");
    $I->seeResponseJsonPathType('$.links.first', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.last', 'string:!empty');
    $I->seeResponseJsonPathNull('$.links.next', null);
    $I->seeResponseJsonPathType('$.links.prev', 'string:!empty');
    $I->seeResponseJsonPathType('$.links.self', 'string:!empty');

    $I->expect("should include provided 'limit' query param all link urls, except 'next' link");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Blimit\%5D\=2/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Blimit\%5D\=2/');

    $I->expect("should include correct 'offset' query param all link urls, except 'next' link");
    $I->seeResponseJsonPathRegex('$.links.first', '/\?.*page\%5Boffset\%5D\=1/');
    $I->seeResponseJsonPathRegex('$.links.last', '/\?.*page\%5Boffset\%5D\=5/');
    $I->seeResponseJsonPathRegex('$.links.prev', '/\?.*page\%5Boffset\%5D\=4/');
    $I->seeResponseJsonPathRegex('$.links.self', '/\?.*page\%5Boffset\%5D\=5/');

    // ----------------------------------------------------
    // meta
    // ----------------------------------------------------

    $I->expect("should return correct pagination meta");
    $I->seeResponseJsonPathSame('$.meta.pagination.count', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.limit', 2);
    $I->seeResponseJsonPathSame('$.meta.pagination.offset', 5);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_items', 10);
    $I->seeResponseJsonPathSame('$.meta.pagination.total_pages', 5);

    // ----------------------------------------------------
    // limit data
    // ----------------------------------------------------

    $I->expect("should limit return data");
    $data = $I->grabResponseJsonPath('$.data[*]');
    $I->assertSame(count($data), 2);

});
