<?php

use Illuminate\Database\Migrations\Migration;

class CreateGeneralImplementation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        (new ImplementationsTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down()
    {
        //
    }
}
