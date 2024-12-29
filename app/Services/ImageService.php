<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\UnsplashApi;
use Gumlet\ImageResize;
use Illuminate\Http\UploadedFile;

class ImageService
{
    public function uploadImage(UploadedFile $file): ?string
    {
        if ($file) {
            // Get the original file extension and path
            $originalExtension = strtolower($file->getClientOriginalExtension()); // Convert to lowercase for consistency

            // If the file is already WebP, just store it without conversion
            if ($originalExtension === 'webp') {
                $filePath = $file->storeAs('uploads', time() . '.webp', 'public');

                return 'storage/' . $filePath;
            }

            // If the file is already Ico, just store it without conversion
            if ($originalExtension === 'ico') {
                $filePath = $file->storeAs('uploads', time() . '.ico', 'public');

                return 'storage/' . $filePath;
            }


            // Store the file temporarily in its original format
            $filePath = $file->storeAs('uploads', time() . '.' . $originalExtension, 'public');
            $originalFilePath = storage_path('app/public/' . $filePath);

            // Create the new WebP filename and path
            $webpFilePath = 'uploads/' . time() . '.webp';
            $webpFullPath = storage_path('app/public/' . $webpFilePath);

            try{
                $filename = explode('/', $originalFilePath);
                $filename = end($filename);

                $image = new ImageResize($originalFilePath);
                $image->resizeToWidth(1280);

                // Zapisz obraz w oryginalnym formacie tymczasowo
                $image->save($webpFullPath, IMAGETYPE_WEBP);

            }catch (\Exception $e){
                return null;
            }


            // Delete the original file as it’s no longer needed
            unlink($originalFilePath);

            return 'storage/' . $webpFilePath;
        }

        return null;
    }

    public function generateImageByQuery(string $queryImage): ?string
    {
        $images = UnsplashApi::getImages($queryImage);

        if(!empty($images)) {
            $image = $images[rand(0, count($images) - 1)];
            $fileContents = file_get_contents($image['url']);

            // Ścieżka, gdzie zapiszemy plik tymczasowo
            $tempFilePath = storage_path('app/temp_image.jpg');

            // Zapisanie pliku w systemie plików
            file_put_contents($tempFilePath, $fileContents);

            // Utworzenie obiektu UploadedFile
            $uploadedFile = new UploadedFile(
                $tempFilePath,
                'image.jpg', // Nazwa pliku
                mime_content_type($tempFilePath), // Typ MIME pliku
                null, // Rozmiar pliku - można pominąć, jeśli nie jest wymagane
                true // Czy plik był przesłany przez HTTP (ustawiamy na true)
            );
            $filePath = $this->uploadImage($uploadedFile);
            unlink($tempFilePath);

            return $filePath;
        }

        return null;
    }

    public static function prepareUrlImage(?string $path): string
    {
        $currentImage = empty($path) ? 'storage/uploads/empty_image.jpg' : $path;
        $pattern = "/asset\('(.+?)'\)/";
        if (preg_match($pattern, $currentImage, $matches)) {
            $currentImage = $matches[1];
        }
        return str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
    }
}
