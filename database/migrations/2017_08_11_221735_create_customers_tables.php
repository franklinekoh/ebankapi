<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('user_id');
            $table->string('phone');
            $table->string('residential_address');
            $table->string('business_address');
            $table->smallInteger('sms_status');
            $table->string('card_number');
            $table->timestamp('last_transaction');
            $table->string('next_of_kin');
            $table->string('next_of_kin_phone');
            $table->string('relationship_to_next_of_kin');
            $table->string('staff_id');
            $table->smallInteger('state_id');
            $table->smallInteger('local_govt_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
