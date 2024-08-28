<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateClinicInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinic_infos', function (Blueprint $table) {
            $table->id();
            $table->string('clinic_name_ar');
            $table->string('clinic_name_en');
            $table->text('clinic_bio_ar');
            $table->text('clinic_bio_en');
            $table->string('clinic_address_ar');
            $table->string('clinic_address_en');
            $table->string('clinic_department_ar');
            $table->string('clinic_department_en');
            $table->string('clinic_image')->nullable();
            $table->string('doc_image')->nullable();
            $table->string('clinic_fblink')->nullable();
            $table->string('doctor_name');
            $table->string('doctor_email');
            $table->string('doctor_phone');
            $table->integer('amount');
            $table->integer('consultation_amount');
            $table->timestamps();
        });

        DB::table('clinic_infos')->insert([
            'clinic_name_ar'    => 'العيادة',
            'clinic_name_en'    => 'el3yada',
            'clinic_bio_en' => 'Specialized medical services with the latest cutting- edge equipment ',
            'clinic_bio_ar' => 'خدمات طبية متخصصة بأحدث الأجهزة المتطورة',
            'clinic_address_ar' => '26985 Brighton Lane, Lake Forest, CA 92630.',
            'clinic_address_en' => '26985 برايتون لين ، ليك فورست ، كاليفورنيا 92630.',
            'clinic_department_ar'  => 'علم الأورام',
            'clinic_department_en'  => 'Oncology (Medical)',
            'clinic_fblink' => 'https://facebook.com',
            'doctor_name'    => 'jhon',
            'doctor_email'  => 'jhon@test.com',
            'doctor_phone'  => '+201111111111',
            'amount'  => 0,
            'consultation_amount'  => 0,
               ]);
    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clinic_infos');
    }
}
