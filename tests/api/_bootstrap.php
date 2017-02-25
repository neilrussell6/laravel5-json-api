<?php

use Codeception\Util\Fixtures;

Fixtures::add('unknown', [
    'data' => [
        'type' => 'unknown',
        'attributes' => []
    ]
]);

Fixtures::add('user', [
    'data' => [
        'type' => 'users',
        'attributes' => [
            'name' => "AAA",
            'email' => "AAA@BBB.CCC",
            'password' => "Abc123!",
            'password_confirmation' => "Abc123!"
        ]
    ]
]);

Fixtures::add('project', [
    'data' => [
        'type' => 'projects',
        'attributes' => [
            'name' => "AAA"
        ]
    ]
]);

Fixtures::add('task', [
    'data' => [
        'type' => 'tasks',
        'attributes' => [
            'project_id' => 1,
            'name' => "AAA"
        ]
    ]
]);
