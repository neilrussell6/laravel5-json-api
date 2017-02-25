<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(Project::class, function (Faker\Generator $faker) {

    static $status;

    return [
        'name' => $faker->words(3, true),
        'status' => $status ?: $faker->numberBetween(1, 3),
    ];
});

$factory->define(Task::class, function (Faker\Generator $faker) {
    static $project_id;

    return [
        'name' => $faker->sentence(5),
        'project_id' => $project_id ?: null,
        'status' => $faker->numberBetween(1, 3),
    ];
});
