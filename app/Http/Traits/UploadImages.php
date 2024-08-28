<?php
namespace App\Traits;
use App\Models\SiteConfiguration;
use Illuminate\Support\Facades\Storage;

trait UploadImages
{
    public function uploadImagesForConfigs($file, $attribute, $disk)
    {
        if ($file) {
            $filename = $file->getClientOriginalName();
            $siteConfiguration = new SiteConfiguration();

            // Delete old file if it exists
            if ($siteConfiguration->$attribute) {
                Storage::disk($disk)->delete($siteConfiguration->$attribute);
            }

            // Store file on server
            $path = $file->storeAs('', $filename, $disk);

            // Update or create the SiteConfiguration record
            $siteConfiguration->updateOrCreate([], [$attribute => $filename]);
        }
    }
    public function uploadImages($file, $disk)
    {
        if ($file) {
            $filename = $file->getClientOriginalName();
            // Store file on server
            $path = $file->storeAs('', $filename, $disk);

        }
    }


    }
