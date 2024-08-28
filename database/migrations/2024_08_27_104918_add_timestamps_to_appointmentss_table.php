<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampsToAppointmentssTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('appointmentss', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->after('duration');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
}