<?php namespace Neilrussell6\Laravel5JsonApi\Seeds;

use App\Models\Task;
use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;

class TaskTableSeeder extends Seeder
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
        // a few tasks for each of user 1's projects
        User::find(1)->projects()->get()->each(function($project) {

            $collection = factory(Task::class, $this->faker->numberBetween(5, 10))->create(['project_id' => $project['id']]);

            $collection->each(function($task) {
                $task->users()->attach(1);
            });
        });

        // and a few tasks for each of the other user's projects
        User::where('id', '!=', 1)->each(function($user) {
            $user->projects()->get()->each(function($project) use ($user) {

                $collection = factory(Task::class, $this->faker->numberBetween(2, 5))->create(['project_id' => $project['id']]);

                $collection->each(function($task) use ($user) {
                    $task->users()->attach($user['id']);
                });
            });
        });
    }
}
