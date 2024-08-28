<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientPyamentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_pyaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->references('id')->on('visits')->onDelete('cascade');
            $table->foreignId('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('order_id');
            $table->string('amount');
            $table->string('status');
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
        Schema::dropIfExists('patient_pyaments');
    }
}
