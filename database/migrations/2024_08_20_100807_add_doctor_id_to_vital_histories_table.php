<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDoctorIdToVitalHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vital_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('vital_histories', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->after('patient_id');

                // $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vital_histories', function (Blueprint $table) {
            if (Schema::hasColumn('vital_histories', 'doctor_id')) {
                $table->dropForeign(['doctor_id']);
                $table->dropColumn('doctor_id');
            }
        });
    }
}