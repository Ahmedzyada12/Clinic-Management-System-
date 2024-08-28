<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDoctorPaymentConfsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_payment_confs', function (Blueprint $table) {
            $table->id();
            $table->string('iframe_id');
            $table->string('integration_online_card_id')->nullable();
            $table->string('integration_mobile_wallet_id')->nullable();
            $table->text('api_key');
            $table->text('hmac');
            $table->timestamps();
        });

        DB::table('doctor_payment_confs')->insert([
            'iframe_id' => 'enter your data here...',
            'integration_online_card_id'    => 'enter your data here...',
            'integration_mobile_wallet_id'  => 'enter your data here...',
            'api_key'   => 'enter your data here...',
            'hmac'  => 'enter your data here...'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_payment_confs');
    }
}
