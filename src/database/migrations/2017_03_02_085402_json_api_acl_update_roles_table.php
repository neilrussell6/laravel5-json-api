<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JsonApiAclUpdateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('roles')) {

            Schema::table('roles', function (Blueprint $table) {

                if (Schema::hasColumn('roles', 'hierarchy')) {

                    // modify column
                    $table->integer('hierarchy')->unsigned()->default(0)->change();
                    $table->boolean('is_hierarchical')->default(false)->change();
                }

                else {
                    // add column
                    $table->integer('hierarchy')->unsigned()->default(0);
                    $table->boolean('is_hierarchical')->default(false);
                }
            });

        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'hierarchy')) {

            // drop column
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn(['hierarchy']);
            });
        }
    }
}
