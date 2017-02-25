<?php

use \Mockery as m;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeTopLevelPaginationMetaObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a top-level pagination meta object for JSON API response");

//-----------------------------------------------------
// valid paginator
//-----------------------------------------------------

$I->comment("given a valid paginator");

$paginator = m::mock('Illuminate\Contracts\Pagination\LengthAwarePaginator');
$paginator->shouldReceive('count')->andReturn(101);
$paginator->shouldReceive('perPage')->andReturn(102);
$paginator->shouldReceive('currentPage')->andReturn(103);
$paginator->shouldReceive('total')->andReturn(104);
$paginator->shouldReceive('lastPage')->andReturn(105);

$result = JsonApiUtils::makeTopLevelPaginationMetaObject($paginator);

//-----------------------------------------------------

$I->expect("should return correct pagination meta");
$I->seeJsonPathSame($result, '$.pagination.count', 101);
$I->seeJsonPathSame($result, '$.pagination.limit', 102);
$I->seeJsonPathSame($result, '$.pagination.offset', 103);
$I->seeJsonPathSame($result, '$.pagination.total_items', 104);
$I->seeJsonPathSame($result, '$.pagination.total_pages', 105);
