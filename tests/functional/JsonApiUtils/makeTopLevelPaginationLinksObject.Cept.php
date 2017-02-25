<?php

use \Mockery as m;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeTopLevelPaginationLinksObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a top-level pagination links object for JSON API response");

//-----------------------------------------------------
// page 1 of 5
//-----------------------------------------------------

$I->comment("given paginator for page 1 of 5");

$paginator = m::mock('Illuminate\Contracts\Pagination\LengthAwarePaginator');
$paginator->shouldReceive('currentPage')->andReturn(1);
$paginator->shouldReceive('lastPage')->andReturn(5);
$paginator->shouldReceive('hasMorePages')->andReturn(true);
$paginator->shouldReceive('onFirstPage')->andReturn(true);

$full_base_url = "http://aaa.bbb?page[offset]=1&page[limit]=2";
$base_url = "http://aaa.bbb";
$query_params = [
    'page' => [
        'offset' => 1,
        'limit' => 2,
    ]
];

$result = JsonApiUtils::makeTopLevelPaginationLinksObject($paginator, $full_base_url, $base_url, $query_params);

//-----------------------------------------------------

$I->expect("should return pagination link urls for all, except 'prev' link");
$I->seeJsonPathType($result, '$.first', 'string:!empty');
$I->seeJsonPathType($result, '$.last', 'string:!empty');
$I->seeJsonPathType($result, '$.next', 'string:!empty');
$I->seeJsonPathSame($result, '$.prev', null);

$I->expect("should include 'limit' query param all link urls, except 'prev' link");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Blimit\%5D\=2/');

$I->expect("should include correct 'offset' query param all link urls, except 'prev' link");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Boffset\%5D\=1/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Boffset\%5D\=5/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Boffset\%5D\=2/');

//-----------------------------------------------------
// page 2 of 5
//-----------------------------------------------------

$I->comment("given paginator for page 2 of 5");

$paginator = m::mock('Illuminate\Contracts\Pagination\LengthAwarePaginator');
$paginator->shouldReceive('currentPage')->andReturn(2);
$paginator->shouldReceive('lastPage')->andReturn(5);
$paginator->shouldReceive('hasMorePages')->andReturn(true);
$paginator->shouldReceive('onFirstPage')->andReturn(false);

$full_base_url = "http://aaa.bbb?page[offset]=1&page[limit]=2";
$base_url = "http://aaa.bbb";
$query_params = [
    'page' => [
        'offset' => 2,
        'limit' => 2,
    ]
];

$result = JsonApiUtils::makeTopLevelPaginationLinksObject($paginator, $full_base_url, $base_url, $query_params);

//-----------------------------------------------------

$I->expect("should return pagination link urls for all links");
$I->seeJsonPathType($result, '$.first', 'string:!empty');
$I->seeJsonPathType($result, '$.last', 'string:!empty');
$I->seeJsonPathType($result, '$.next', 'string:!empty');
$I->seeJsonPathType($result, '$.prev', 'string:!empty');

$I->expect("should include provided 'limit' query param all link urls");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Blimit\%5D\=2/');

$I->expect("should include correct 'offset' query param all link urls");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Boffset\%5D\=1/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Boffset\%5D\=5/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Boffset\%5D\=3/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Boffset\%5D\=1/');

//-----------------------------------------------------
// page 3 of 5
//-----------------------------------------------------

$I->comment("given paginator for page 3 of 5");

$paginator = m::mock('Illuminate\Contracts\Pagination\LengthAwarePaginator');
$paginator->shouldReceive('currentPage')->andReturn(3);
$paginator->shouldReceive('lastPage')->andReturn(5);
$paginator->shouldReceive('hasMorePages')->andReturn(true);
$paginator->shouldReceive('onFirstPage')->andReturn(false);

$full_base_url = "http://aaa.bbb?page[offset]=1&page[limit]=2";
$base_url = "http://aaa.bbb";
$query_params = [
    'page' => [
        'offset' => 3,
        'limit' => 2,
    ]
];

$result = JsonApiUtils::makeTopLevelPaginationLinksObject($paginator, $full_base_url, $base_url, $query_params);

//-----------------------------------------------------

$I->expect("should return pagination link urls for all links");
$I->seeJsonPathType($result, '$.first', 'string:!empty');
$I->seeJsonPathType($result, '$.last', 'string:!empty');
$I->seeJsonPathType($result, '$.next', 'string:!empty');
$I->seeJsonPathType($result, '$.prev', 'string:!empty');

$I->expect("should include provided 'limit' query param all link urls");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Blimit\%5D\=2/');

$I->expect("should include correct 'offset' query param all link urls");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Boffset\%5D\=1/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Boffset\%5D\=5/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Boffset\%5D\=4/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Boffset\%5D\=2/');

//-----------------------------------------------------
// page 4 of 5
//-----------------------------------------------------

$I->comment("given paginator for page 4 of 5");

$paginator = m::mock('Illuminate\Contracts\Pagination\LengthAwarePaginator');
$paginator->shouldReceive('currentPage')->andReturn(4);
$paginator->shouldReceive('lastPage')->andReturn(5);
$paginator->shouldReceive('hasMorePages')->andReturn(true);
$paginator->shouldReceive('onFirstPage')->andReturn(false);

$full_base_url = "http://aaa.bbb?page[offset]=1&page[limit]=2";
$base_url = "http://aaa.bbb";
$query_params = [
    'page' => [
        'offset' => 4,
        'limit' => 2,
    ]
];

$result = JsonApiUtils::makeTopLevelPaginationLinksObject($paginator, $full_base_url, $base_url, $query_params);

//-----------------------------------------------------

$I->expect("should return pagination link urls for all links");
$I->seeJsonPathType($result, '$.first', 'string:!empty');
$I->seeJsonPathType($result, '$.last', 'string:!empty');
$I->seeJsonPathType($result, '$.next', 'string:!empty');
$I->seeJsonPathType($result, '$.prev', 'string:!empty');

$I->expect("should include provided 'limit' query param all link urls");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Blimit\%5D\=2/');

$I->expect("should include correct 'offset' query param all link urls");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Boffset\%5D\=1/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Boffset\%5D\=5/');
$I->seeJsonPathRegex($result, '$.next', '/\?.*page\%5Boffset\%5D\=5/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Boffset\%5D\=3/');

//-----------------------------------------------------
// page 5 of 5
//-----------------------------------------------------

$I->comment("given paginator for page 5 of 5");

$paginator = m::mock('Illuminate\Contracts\Pagination\LengthAwarePaginator');
$paginator->shouldReceive('currentPage')->andReturn(5);
$paginator->shouldReceive('lastPage')->andReturn(5);
$paginator->shouldReceive('hasMorePages')->andReturn(false);
$paginator->shouldReceive('onFirstPage')->andReturn(false);

$full_base_url = "http://aaa.bbb?page[offset]=1&page[limit]=2";
$base_url = "http://aaa.bbb";
$query_params = [
    'page' => [
        'offset' => 5,
        'limit' => 2,
    ]
];

$result = JsonApiUtils::makeTopLevelPaginationLinksObject($paginator, $full_base_url, $base_url, $query_params);

//-----------------------------------------------------

$I->expect("should return pagination link urls for all links, except 'next' link");
$I->seeJsonPathType($result, '$.first', 'string:!empty');
$I->seeJsonPathType($result, '$.last', 'string:!empty');
$I->seeJsonPathNull($result, '$.next');
$I->seeJsonPathType($result, '$.prev', 'string:!empty');

$I->expect("should include provided 'limit' query param all link urls, except 'next' link");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Blimit\%5D\=2/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Blimit\%5D\=2/');

$I->expect("should include correct 'offset' query param all link urls, except 'next' link");
$I->seeJsonPathRegex($result, '$.first', '/\?.*page\%5Boffset\%5D\=1/');
$I->seeJsonPathRegex($result, '$.last', '/\?.*page\%5Boffset\%5D\=5/');
$I->seeJsonPathRegex($result, '$.prev', '/\?.*page\%5Boffset\%5D\=4/');
