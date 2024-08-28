<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPatientShowToVitalHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vital_histories', function (Blueprint $table) {
            $table->boolean('patient_show')->default(false)->after('date');
        });
    }

    public function down()
    {
        Schema::table('vital_histories', function (Blueprint $table) {
            $table->dropColumn('patient_show');
        });
    }
}
