<?php

use Illuminate\Database\Migrations\Migration;

class ChangeDeletedFolderIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('folders')->where('type', 110)
            ->update(['type' => 70]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('folders')->where('type', 70)
            ->update(['type' => 110]);
    }
}
