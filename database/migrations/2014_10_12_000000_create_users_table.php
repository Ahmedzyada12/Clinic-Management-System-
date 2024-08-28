<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->foreignId('specialization_id')->nullable()->constrained('specializations')->nullOnDelete()->cascadeOnUpdate();
            $table->string('consultant_price') ->nullable()->comment("سعر الاستشارة");
            $table->string('disclosure_price')->nullable()->comment("سعر الكشف");
            $table->string('password');
            $table->string('phone');
            $table->longText('image')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });


        DB::table('users')->insert([
            'full_name'    => 'Omar',
            'password'      => Hash::make('1234567'),
            'email'         => 'admin@gmail.com',
            'phone'         => '123456789',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
