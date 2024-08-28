<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service_Blog;
use App\Models\Site_Configuetation;
use App\Models\SiteConfiguration;
use App\Traits\UploadImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Traits\GeneralTrait;

class SiteConfigurationController extends Controller
{

    use GeneralTrait;


    public function storeSiteConfig(Request $request)
    {
        // Perform validation on the request
        $request->validate([
            'logo' => 'image|nullable',
            'text_logo' => 'nullable|string',
            'cover' => 'image|nullable',
            'second_cover' => 'image|nullable',
            'text_cover' => 'nullable|string',
            'main_background_color' => 'nullable|string|max:100',
            'secondry_background_color' => 'nullable|string|max:100',
            'first_text_color' => 'nullable|string|max:100',
            'second_text_color' => 'nullable|string|max:100',
            'slogan_title' => 'nullable|string',
            'slogan_body' => 'nullable|string',
            'sections' => 'nullable|string',
            'phone' => 'nullable|array',
            'video_url' => 'nullable|string',
            'address' => 'nullable|array',
            'social_media_links' => 'nullable|string',
        ]);
        // Retrieve existing SiteConfiguration or create a new one
        $siteConfiguration = SiteConfiguration::firstOrNew();

        $logo = $request->logo ?? $siteConfiguration->logo;
        $text_logo = $request->text_logo ?? $siteConfiguration->text_logo;
        $cover = $request->cover ?? $siteConfiguration->cover;
        $second_cover = $request->second_cover ?? $siteConfiguration->second_cover;
        $text_cover = $request->text_cover ?? $siteConfiguration->text_cover;
        $main_background_color = $request->main_background_color ?? $siteConfiguration->main_background_color;
        $secondry_background_color = $request->secondry_background_color ?? $siteConfiguration->secondry_background_color;
        $first_text_color = $request->first_text_color ?? $siteConfiguration->first_text_color;
        $second_text_color = $request->second_text_color ?? $siteConfiguration->second_text_color;
        $slogan_title = $request->slogan_title ?? $siteConfiguration->slogan_title;
        $slogan_body = $request->slogan_body ?? $siteConfiguration->slogan_body;
        $phone = $request->phone ?? $siteConfiguration->phone;
        $video = $request->video_url ?? $siteConfiguration->video_url;
        $address = $request->address ?? $siteConfiguration->address;
        //
        $services = $request->services ?? $siteConfiguration->services;
        $visability_services = $request->visability_services ?? $siteConfiguration->visability_services;
        // $visability_services = $request->visability_services == null ? '1' : $request->visability_services ;

        $ourdoctors = $request->ourdoctors ?? $siteConfiguration->ourdoctors;
        $visability_ourdoctors = $request->visability_ourdoctors ?? $siteConfiguration->visability_ourdoctors;
        // $visability_ourdoctors = $request->visability_ourdoctors;

        $book = $request->book ?? $siteConfiguration->book;
        $visability_book = $request->visability_book ?? $siteConfiguration->visability_book;
        // $visability_book = $request->visability_book;

        $specialties = $request->specialties ?? $siteConfiguration->specialties;
        $visability_specialties = $request->visability_specialties ?? $siteConfiguration->visability_specialties;
        // $visability_specialties = $request->visability_specialties;

        $video_section = $request->video ?? $siteConfiguration->video;
        $visability_video = $request->visability_video ?? $siteConfiguration->visability_video;
        // $visability_video = $request->visability_video;

        $blog = $request->blog ?? $siteConfiguration->blog;
        $visability_blog = $request->visability_blog ?? $siteConfiguration->visability_blog;
        // $visability_blog = $request->visability_blog;

        //
        // Upload the new logo if present
        if ($request->hasFile('logo')) {
            $logo = $this->UploadImage($request, 'users/assistants', 'logo');
        }

        // Upload the new cover if present
        if ($request->hasFile('cover')) {
            $cover = $this->UploadImage($request, 'users/assistants', 'cover');
        }

        // Upload the new second_cover if present
        if ($request->hasFile('second_cover')) {
            $second_cover = $this->UploadImage($request, 'users/assistants', 'second_cover');
        }
        // $checkTrue = 'true';
        // Initialize the sections array with the existing values or empty arrays
        // $sections = [
        //     "services" => ["disc" => $request->services, "visibility" => $request->visability_services == $checkTrue ? true : false],
        //     "ourdoctors" => ["disc" => $request->ourdoctors, "visibility" => $request->visability_ourdoctors == $checkTrue ? true : false],
        //     "book" => ["disc" => $request->book, "visibility" => $request->visability_book == $checkTrue ? true : false],
        //     "specialties" => ["disc" => $request->specialties, "visibility" => $request->visability_specialties == $checkTrue ? true : false],
        //     "video" => ["disc" => $request->video, "visibility" => $request->visability_video == $checkTrue ? true : false],
        //     "blog" => ["disc" => $request->blog, "visibility" => $request->visability_blog == $checkTrue ? true : false],
        // ];



        $socialMediaLinks = [
            'facebook' => $request->facebook ?? $siteConfiguration->social_media_links['facebook'] ?? 'facebook',
            'twitter' => $request->twitter ?? $siteConfiguration->social_media_links['twitter'] ?? 'twitter',
            'instgram' => $request->instgram ?? $siteConfiguration->social_media_links['instgram'] ?? 'instgram',
            'whatsapp' => $request->whatsapp ?? $siteConfiguration->social_media_links['whatsapp'] ?? 'whatsapp',
        ];

        $social_analytics = [
            'facebook_analytic' => $request->facebook_analytic ?? $siteConfiguration->social_analytics['facebook_analytic'] ?? 'facebook_analytic',
            'twitter_analytic' => $request->twitter_analytic ?? $siteConfiguration->social_analytics['twitter_analytic'] ?? 'twitter_analytic',
            'instgram_analytic' => $request->instgram_analytic ?? $siteConfiguration->social_analytics['instgram_analytic'] ?? 'instgram_analytic',
            'whatsapp_analytic' => $request->whatsapp_analytic ?? $siteConfiguration->social_analytics['whatsapp_analytic'] ?? 'whatsapp_analytic',
        ];
        // // Convert the sections array to JSON
        // $sectionsJson = json_encode($sections);

        // Update the remaining fields with the request values
        $updateData = [
            'text_logo' => $text_logo,
            'text_cover' => $text_cover,
            'main_background_color' => $main_background_color,
            'secondry_background_color' => $secondry_background_color,
            'first_text_color' => $first_text_color,
            'second_text_color' => $second_text_color,
            'slogan_title' => $slogan_title,
            'slogan_body' => $slogan_body,
            'logo' => $logo,
            'cover' => $cover,
            'second_cover' => $second_cover,
            'video_url' => $video,
            'phone' => $phone,
            'address' => $address,
            'social_media_links' =>  $socialMediaLinks,
            'social_analytics' =>  $social_analytics,
            'services' =>  $services,
            'visability_services' =>  $visability_services,
            'ourdoctors' =>  $ourdoctors,
            'visability_ourdoctors' =>  $visability_ourdoctors,
            'book' =>  $book,
            'visability_book' =>  $visability_book,
            'specialties' =>  $specialties,
            'visability_specialties' =>  $visability_specialties,
            'blog' =>  $blog,
            'visability_blog' =>  $visability_blog,
            'visability_video' =>  $visability_video,
        ];
        // Update or create the site configuration with the new values
        $siteConfiguration->updateOrCreate([], $updateData);

        // Return a success response
        return response()->json(['message' => 'created successfully']);
    }


    public function storeServicesBlogs(Request $request)
    {
        $logo = SiteConfiguration::first();
        $logo = $logo->logo;
        if ($request->type !== "service") {
            $image = '';
        } else if ($request->type == "service" && $request->image == null) {
            $image = $logo;
        } else if ($request->hasFile('image')) {
            $image = $this->UploadImage($request, 'users/assistants', 'image');
        }

        $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'nullable|image',
        ]);

        $type = $request->type == 'service' ? 'service' : ($request->type == 'blog' ? 'blog' : 'medical');

        $data = Service_Blog::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $type,
            'image' => $image,
        ]);

        return response()->json(['data' => $data, 'message' => ' created successfully']);
    }



    public function updateServiceBlog($subdomain, Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'nullable|image',
        ]);

        $serviceBlog = Service_Blog::find($id);

        if (!$serviceBlog) {
            return response()->json(['error' => 'data not found'], 404);
        }

        // Delete old image if a new image is uploaded
        if ($request->hasFile('image')) {

            if ($serviceBlog->image) {
                Storage::disk('s3')->delete($serviceBlog->image);
            }

            // Upload file on the server
            $image = $this->UploadImage($request, 'users/assistants', 'image');
            //update image on db
            $serviceBlog->image = $image;
        }

        $serviceBlog->title = $request->title;
        $serviceBlog->description = $request->description;
        $serviceBlog->type = $request->type;
        $serviceBlog->save();

        return response()->json(['message' => ' updated successfully']);
    }

    public function deleteServiceBlog($subdomain, $id)
    {
        $serviceBlog = Service_Blog::find($id);

        if (!$serviceBlog) {
            return response()->json(['error' => 'data not found'], 404);
        }

        // Delete associated image if it exists
        if ($serviceBlog->image) {
            Storage::disk('s3')->delete($serviceBlog->image);
        }

        $serviceBlog->delete();

        return response()->json(['message' => 'deleted successfully']);
    }

    public function getSiteConfig()
    {
        $siteConfiguration = SiteConfiguration::firstOrFail();
        if (!$siteConfiguration) {
            return response()->json(['error' => 'configuration not found'], 404);
        }

        $siteConfiguration['logo_path'] = $siteConfiguration->logo;
        $siteConfiguration['cover_path'] = $siteConfiguration->cover;
        $siteConfiguration['second_cover_path'] = $siteConfiguration->second_cover;
        $siteConfiguration['sections'] = json_decode(stripslashes($siteConfiguration->sections), true);

        return response()->json($siteConfiguration);
    }

    public function getServiceByType(Request $request)
    {
        $type = $request->query('type');

        $collection = Service_Blog::where('type', $type)->get();
        return response()->json(['data' => $collection]);
    }

    public function getLimitService()      //get latest four services
    {

        $data['services'] = Service_Blog::where('type', 'service')->latest()->take(4)->get();
        $data['medicals'] = Service_Blog::where('type', 'medical')->latest()->take(4)->get();
        $data['blogs'] = Service_Blog::where('type', 'blog')->latest()->take(4)->get();

        return response()->json(['data' => $data]);
    }


    public function resetImages(Request $request)
    {
        try {
            $config = SiteConfiguration::firstOrFail();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Site configuration not found'], 404);
        }

        $reset = $request->reset;
        if ($reset === 'reset') {
            try {
                $config->update([
                    'logo' => 'users/assistants/57641261709803506.png',
                    'cover' => 'users/assistants/16358331708519949.jpg',
                    'second_cover' => 'users/assistants/68791601708519950.jpg',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to reset images'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid reset value'], 400);
        }

        return response()->json(['message' => 'Images reset successfully']);
    }

}
