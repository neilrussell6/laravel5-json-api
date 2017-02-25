<?php

use \Mockery as m;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// Test: JsonApiUtils::makeResourceObject
//
///////////////////////////////////////////////////////

$I->wantTo("make a resource object for JSON API response");

$user_model = m::mock('User');
$user_model->default_includes = ['projects'];
$user_model->type = 'users';

//-----------------------------------------------------
// all required values
//-----------------------------------------------------

$I->comment("given all required values");

$data = [
    'id' => 123,
    'name' => "AAA",
    'email' => "aaa@bbb.ccc",
];
$base_url = "http://aaa.bbb.ccc/ddd/1";
$links = [
    'self' => "http://aaa.bbb.ccc/ddd/1"
];

$result = JsonApiUtils::makeResourceObject($data, $user_model, $base_url, $links);

//-----------------------------------------------------

$I->expect("should return id (as string), type, attributes & links as top level members");
$I->seeJsonPathType($result, '$.id', 'string:!empty');
$I->seeJsonPathType($result, '$.type', 'string:!empty');
$I->seeJsonPathType($result, '$.attributes', 'array:!empty');
$I->seeJsonPathType($result, '$.links', 'array:!empty');

$I->expect("should correctly set id & type");
$I->seeJsonPathSame($result, '$.id', '123');
$I->seeJsonPathSame($result, '$.type', 'users');

$I->expect("attributes member should contain all provided data except id");
$I->seeNotJSONPath($result, '$.attributes.id');
$I->seeJsonPathSame($result, '$.attributes.name', "AAA");
$I->seeJsonPathSame($result, '$.attributes.email', "aaa@bbb.ccc");

$I->expect("links member should contain provided self link");
$I->seeJsonPathSame($result, '$.links.self', $links['self']);

$I->expect("should include relationships array by default, if default includes are set on the model");
$I->seeJsonPathType($result, '$.relationships', 'array:!empty');

//-----------------------------------------------------
// exclude relationships
//-----------------------------------------------------

$I->comment("given relationships are marked as excluded");

$data = [
    'id' => 123,
    'name' => "AAA",
    'email' => "aaa@bbb.ccc",
];
$base_url = "http://aaa.bbb.ccc/ddd/1";
$links = [
    'self' => "http://aaa.bbb.ccc/ddd/1"
];
$include_relationships = false;

$result = JsonApiUtils::makeResourceObject($data, $user_model, $base_url, $links, $include_relationships);

//-----------------------------------------------------

$I->expect("should include relationships array by default, if default includes are set on the model");
$I->seeNotJSONPath($result, '$.relationships');

//-----------------------------------------------------
// not type or id
//-----------------------------------------------------

$I->comment("given all required values");

$data = [
    'id' => 123,
    'type' => "something",
    'name' => "AAA",
    'email' => "aaa@bbb.ccc",
    'task_id' => 234,
    'project_id' => 345,
    'parent_record_id' => 456,
];
$base_url = "http://aaa.bbb.ccc/ddd/1";
$links = [
    'self' => "http://aaa.bbb.ccc/ddd/1"
];

$result = JsonApiUtils::makeResourceObject($data, $user_model, $base_url, $links);

//-----------------------------------------------------

$I->expect("should not include fields name id or type in attributes");
$I->seeNotJSONPath($result, '$.attributes.id');
$I->seeNotJSONPath($result, '$.attributes.type');

//-----------------------------------------------------
// not foreign keys or pivot object
//-----------------------------------------------------

$I->comment("given all required values");

$data = [
    'id' => 123,
    'type' => "something",
    'name' => "AAA",
    'email' => "aaa@bbb.ccc",
    'task_id' => 234,
    'project_id' => 345,
    'parent_record_id' => 456,
    'pivot' => [
        'task_id' => 234,
        'project_id' => 345,
        'parent_record_id' => 456,
    ]
];
$base_url = "http://aaa.bbb.ccc/ddd/1";
$links = [
    'self' => "http://aaa.bbb.ccc/ddd/1"
];

$result = JsonApiUtils::makeResourceObject($data, $user_model, $base_url, $links);

//-----------------------------------------------------

$I->expect("should not include any foreign keys in attributes");
$I->seeNotJSONPath($result, '$.attributes.task_id');
$I->seeNotJSONPath($result, '$.attributes.project_id');
$I->seeNotJSONPath($result, '$.attributes.parent_record_id');

$I->expect("should not include pivot object in attributes");
$I->seeNotJSONPath($result, '$.attributes.pivot');

//-----------------------------------------------------
// minimal
//-----------------------------------------------------

$I->comment("given the is_minimal flag is set to true");

$data = [
    'id' => 123,
    'type' => "something",
    'name' => "AAA",
    'email' => "aaa@bbb.ccc",
    'task_id' => 234,
    'project_id' => 345,
    'parent_record_id' => 456,
];
$base_url = "http://aaa.bbb.ccc/ddd/1";
$links = [
    'self' => "http://aaa.bbb.ccc/ddd/1"
];
$include_relationships = false;
$is_minimal = true;

$result = JsonApiUtils::makeResourceObject($data, $user_model, $base_url, $links, $include_relationships, $is_minimal);

//-----------------------------------------------------

$I->expect("should include type and id");
$I->seeJSONPath($result, '$.type');
$I->seeJSONPath($result, '$.id');

$I->expect("should not include attributes, meta or links");
$I->seeNotJSONPath($result, '$.attributes');
$I->seeNotJSONPath($result, '$.meta');
$I->seeNotJSONPath($result, '$.links');
