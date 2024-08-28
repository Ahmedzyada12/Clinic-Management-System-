<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVitalSignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->references('id')->on('users')->onDelete('cascade');    //paitent

            $table->integer('heart_rate')->comment('bpm');                      //bpm
            $table->integer('systolic_blood_pressure')->comment('mmHg');         //mmHg
            $table->integer('diastolic_blood_pressure')->comment('mmHg');        //mmHg
            $table->integer('temperature')->comment('°C');                     //°C
            $table->integer('oxygen_saturation')->comment('%');               //%
            $table->integer('respiratory_rate')->comment('bpm');                //bpm
            $table->integer('bmi_weight')->comment('Kg');                      //Kg
            $table->integer('bmi_height')->comment('Cm');                      //Cm


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
        Schema::dropIfExists('vital_signs');
    }
}
