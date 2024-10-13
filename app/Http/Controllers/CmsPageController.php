<?php

namespace App\Http\Controllers;

use App\Services\CmsPageService;
use App\Services\ImageService;
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

    public function uploadImage(Request $request, CmsPageService $cmsPageService, ImageService $imageService)
    {
        $data = $request->all();

        // Przechowywanie obrazu
        $file = $request->file('file');
        if ($file) {
            $filePath = $imageService->uploadImage($file);

            if($filePath == null){
                return response()->json(['status' => 'error']);
            }

            // Update the CMS with the WebP file path
            $cmsPageService->updateKey($data['website'], $data['key'] . '0001000file', $filePath);

            return response()->json(['filePath' => asset($filePath)]);
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
