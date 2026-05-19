<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class ImageService
{
    public function createImage(UploadedFile $file,$name,$destinationPath)
    {
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $quality = 80;
        $filename = 'IMGPRO'.$name.'.webp';
        $webpFullPath = $destinationPath . '/' . $filename;
        
        $imagePath = $file->getPathname();
        
        $manager = new ImageManager(Driver::class);
        $image = $manager->read($imagePath);
        $encoded = $image->encode(new WebpEncoder(quality: $quality));
        
         file_put_contents($webpFullPath, $encoded);
    
    }
}