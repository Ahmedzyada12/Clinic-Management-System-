<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('user_id');
            // $table->unsignedBigInteger('appointment_id');
            // $table->unsignedBigInteger('doctor_id');
            // $table->tinyInteger('status')->default(0)->comment('0 Not visited, 1 visited');
            // $table->text('description')->nullable();
            // $table->longText('file')->nullable();
            // $table->string('type')->nullable();
            // $table->integer('amount')->default(0);
            // $table->integer('extra_amount')->default(0);
            // $table->timestamps();
            // $table->integer('main_amount_paid')->default(0);
            // // Foreign keys
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('appointment_id')->references('id')->on('appointments');
            // $table->foreign('doctor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visits');
    }
}