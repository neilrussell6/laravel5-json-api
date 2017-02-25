<?php namespace Neilrussell6\Laravel5JsonApi\Testing\database\seeds;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        App::make('Illuminate\Database\Eloquent\Factory')->load(__DIR__ . '/../factories');

        Model::unguard();

        $this->call(UserTableSeeder::class);
        $this->call(ProjectTableSeeder::class);
        $this->call(TaskTableSeeder::class);

        Model::reguard();
    }
}
