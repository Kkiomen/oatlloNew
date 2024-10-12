<?php

namespace App\Http\Controllers;

use App\Services\CmsPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsPageController extends Controller
{
    public function edit(string $slug)
    {
        $cmsPages = \App\Models\CmsPage::get();
        $foundedPage = null;
        foreach ($cmsPages as $page){
            if($slug == Str::slug($page->name)){
                $foundedPage = $page;
                break;
            }
        }

        if(!$foundedPage){
            abort(404);
        }

//        dump($foundedPage->json_page);
        return view('cms_page.edit', [
            'page' => $foundedPage->json_page,
            'namePage' => $foundedPage->name
        ]);
    }

    public function uploadImage(Request $request, CmsPageService $cmsPageService)
    {
        $data = $request->all();

        // Przechowywanie obrazu
        $file = $request->file('file');
        if ($file) {
            // Get the original file extension and path
            $originalExtension = strtolower($file->getClientOriginalExtension()); // Convert to lowercase for consistency

            // If the file is already WebP, just store it without conversion
            if ($originalExtension === 'webp') {
                $filePath = $file->storeAs('uploads', time() . '.webp', 'public');

                // Update the CMS with the WebP file path
                $cmsPageService->updateKey($data['website'], $data['key'] . '0001000file', 'storage/' . $filePath);

                return response()->json(['filePath' => asset('storage/' . $filePath)]);
            }

            // If the file is already Ico, just store it without conversion
            if ($originalExtension === 'ico') {
                $filePath = $file->storeAs('uploads', time() . '.ico', 'public');

                // Update the CMS with the Ico file path
                $cmsPageService->updateKey($data['website'], $data['key'] . '0001000file', 'storage/' . $filePath);

                return response()->json(['filePath' => asset('storage/' . $filePath)]);
            }


            // Store the file temporarily in its original format
            $filePath = $file->storeAs('uploads', time() . '.' . $originalExtension, 'public');
            $originalFilePath = storage_path('app/public/' . $filePath);

            // Create the new WebP filename and path
            $webpFilePath = 'uploads/' . time() . '.webp';
            $webpFullPath = storage_path('app/public/' . $webpFilePath);

            // Convert the image to WebP based on its format
            switch ($originalExtension) {
                case 'jpeg':
                case 'jpg':
                    $image = imagecreatefromjpeg($originalFilePath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($originalFilePath);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($originalFilePath);
                    break;
                default:
                    return response()->json(['status' => 'error', 'message' => 'Unsupported image format']);
            }

            // Save the image as WebP
            imagewebp($image, $webpFullPath, 80); // Quality set to 80 (adjust as needed)
            imagedestroy($image); // Free up memory

            // Delete the original file as it’s no longer needed
            unlink($originalFilePath);

            // Update the CMS with the WebP file path
            $cmsPageService->updateKey($data['website'], $data['key'] . '0001000file', 'storage/' . $webpFilePath);

            return response()->json(['filePath' => asset('storage/' . $webpFilePath)]);
        }

        return response()->json(['status' => 'error']);
    }

    public function update(Request $request, CmsPageService $cmsPageService)
    {
        $updatedData = $request->all();
        if(!isset($updatedData['website']) || count($updatedData) !== 2){
            abort(404);
        }

        $websiteName = $updatedData['website'];
        unset($updatedData['website']);

        // Tu zawsze jest jeden element
        foreach ($updatedData as $key => $value){
           $updatedInfo = $cmsPageService->updateKey($websiteName, $key, $value);
        }

        return response()->json(['status' => 'success', 'changes' => $updatedInfo['changes']]);
    }
}
