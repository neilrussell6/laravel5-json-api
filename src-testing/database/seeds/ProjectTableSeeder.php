<?php namespace Neilrussell6\Laravel5JsonApi\Testing\database\seeds;

use \App\Models\Project;
use \App\Models\User;
use \Illuminate\Database\Seeder;
use \Faker\Generator;

class ProjectTableSeeder extends Seeder
{
    protected $faker;

    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // a few projects for user 1
        $collection = factory(Project::class, $this->faker->numberBetween(2, 4))->create();
        $collection->each(function($project) {
            $project->users()->attach(1);
        });

        // and a few projects for each of the other users
        User::where('id', '!=', 1)->each(function($user) {

            $result = factory(Project::class, $this->faker->numberBetween(0, 3))->create();

            // factory creating a single record (as might happen with numberBetween(0, n)) does not return a collection
            // and calling ->each on that result will process all records of that type (why is this?)
            // hence this precaution

            if ($result instanceof \Illuminate\Support\Collection) {
                $result->each(function($project) use ($user) {
                    $project->users()->attach($user['id']);
                });
            } else {
                $result->users()->attach($user['id']);
            }
        });
    }
}
