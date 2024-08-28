<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappBotMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_bot_messages', function (Blueprint $table) {
            $table->id();
            $table->string('profile_name')->nullable();
            $table->string('number');
            $table->text('message')->nullable();
            $table->text('extra_date')->nullable();
            $table->string('operation');    //unknow //inquiry //book
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
        Schema::dropIfExists('whatsapp_bot_messages');
    }
}
