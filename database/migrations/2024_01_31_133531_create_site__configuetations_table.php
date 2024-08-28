<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\SiteConfiguration;

class CreateSiteConfiguetationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site__configurtations', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->text('text_logo')->nullable();
            $table->string('cover')->nullable();
            $table->string('second_cover')->nullable();
            $table->text('text_cover')->nullable();
            $table->string('main_background_color', 100)->nullable();
            $table->string('secondry_background_color', 100)->nullable();
            $table->string('first_text_color', 100)->nullable();
            $table->string('second_text_color', 100)->nullable();
            $table->text('slogan_title')->nullable();
            $table->text('slogan_body')->nullable();
            $table->longText('sections')->nullable();
            $table->string('video_url')->nullable();
            //
            $table->longText('services')->nullable();
            $table->longText('visability_services')->nullable();
            $table->longText('ourdoctors')->nullable();
            $table->longText('visability_ourdoctors')->nullable();
            $table->longText('book')->nullable();
            $table->longText('visability_book')->nullable();
            $table->longText('specialties')->nullable();
            $table->longText('visability_specialties')->nullable();
            $table->longText('video')->nullable();
            $table->longText('visability_video')->nullable();
            $table->longText('blog')->nullable();
            $table->longText('visability_blog')->nullable();


            //
            $table->text('phone')->nullable();
            $table->text('address')->nullable();
            $table->longText('social_media_links')->nullable();
            $table->longText('social_analytics')->nullable();
            $table->timestamps();
        });
        DB::table('site__configurtations')->insert([
            'logo'    => 'users/assistants/57641261709803506.png',
            'text_logo'    => 'New Text Logo',
            'cover' => 'users/assistants/16358331708519949.jpg',
            'second_cover' => 'users/assistants/68791601708519950.jpg',
            'text_cover' => 'New Text Cover',
            'main_background_color' => '#567de2',
            'secondry_background_color'  => '#7202bb',

            'first_text_color'  => '#ffffff',
            'second_text_color' => '#000000',
            'slogan_title'    => 'New Slogan Title',
            'slogan_body'  => 'New Slogan Body',
            'sections'  => "",
            'video_url' => 'L1B2SOzKR_s',
            'phone' => 'Default Phone Number',
            'address' => 'Default Address',
            'social_media_links' => '{"facebook":"","twitter":"","instagram":"","linkedin":""}',
            'social_analytics' => '{"facebook":"","twitter":"","instagram":"","linkedin":""}',
            'services' =>   'services',
            'visability_services' =>  '1',
            'ourdoctors' =>  'ourdoctors',
            'visability_ourdoctors' => '1',
            'book' =>  'book',
            'visability_book' => '1',
            'specialties' => 'specialties',
            'visability_specialties' =>  '1',
            'blog' => 'blog',
            'visability_blog' =>  '1',
            'video' => 'video',
            'visability_video' =>  '1',

        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site__configuetations');
    }
}
