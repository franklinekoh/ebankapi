<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('user_id');
            $table->string('staff_id');
            $table->string('residential_address');
            $table->string('qualification');
            $table->string('phone');
            $table->string('next_of_kin');
            $table->string('next_of_kin_phone');
            $table->string('relationship_to_next_of_kin');
            $table->string('next_of_kin_age');
            $table->string('next_of_kin_gender');
            $table->smallInteger('state_id');
            $table->smallInteger('local_govt_id');
            $table->tinyInteger('online_status');
            $table->tinyInteger('clocking_status');
            $table->timestamp('clock_in_time')->null;
            $table->timestamp('last_transaction');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff');
    }
}
