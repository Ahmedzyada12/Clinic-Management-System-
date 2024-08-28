<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateServicesBlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services_blogs', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->longText('description');
            $table->tinyText('type');
            $table->string('image');
            $table->timestamps();
        });
        
        DB::table('services_blogs')->insert([
    'title' => 'Default Title',
    'description' => 'Default Description',
    'type' => 'service',
    'image' => 'default_image.jpg',
]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services_blogs');
    }
}
