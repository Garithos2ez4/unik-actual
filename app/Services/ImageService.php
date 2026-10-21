<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class ImageService
{
    public function createImage($file, $name, $destinationPath)
    {
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $quality = 80;
        $filename = 'IMGPRO' . $name . '.webp';
        $webpFullPath = $destinationPath . '/' . $filename;

        $manager = new ImageManager(Driver::class);

        try {
            if ($file instanceof UploadedFile) {
                $image = $manager->read($file->getPathname());
            } elseif (is_string($file) && (str_starts_with($file, 'http://') || str_starts_with($file, 'https://'))) {
                // Descargar imagen remota (p.ej. fotos de Deltron)
                $response = Http::withoutVerifying()->timeout(15)->get($file);
                if (!$response->successful()) {
                    Log::warning('No se pudo descargar imagen remota: ' . $file . ' (Status: ' . $response->status() . ')');
                    return false;
                }
                $image = $manager->read($response->body());
            } elseif (is_string($file) && file_exists($file)) {
                $image = $manager->read($file);
            } else {
                return false;
            }

            $encoded = $image->encode(new WebpEncoder(quality: $quality));
            file_put_contents($webpFullPath, $encoded);
            return true;
        } catch (\Exception $e) {
            Log::error('Error procesando imagen ' . $filename . ': ' . $e->getMessage());
            return false;
        }
    }
}