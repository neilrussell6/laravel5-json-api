<?php namespace Neilrussell6\Laravel5JsonApi\Testing\database\seeds;

use \App\Models\User;
use \Illuminate\Database\Seeder;
use \Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Administrator',
            'email' => env('ADMIN_EMAIL'),
            'password' => bcrypt('ADMIN_PASSWORD')
        ]);
        factory(User::class, 10)->create();
    }
}
