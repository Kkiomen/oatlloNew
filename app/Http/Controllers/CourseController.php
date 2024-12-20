<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCategoryLesson;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function list(Request $request): View
    {
        $courses = Course::get();
        return view('courses.list', [
            'courses' => $courses,  
        ]);
    }

    public function add(Request $request): mixed
    {
        $course = new Course();
        $course->is_published = false;
        $course->save();

        return Redirect::route('courses.edit', ['id' => $course]);
    }
    public function addCategory(Request $request, Course $course): mixed
    {
        $courseCategory = new CourseCategory();
        $courseCategory->course_id = $course->id;
        $courseCategory->lang = $course->lang;
        $courseCategory->category_name = 'Nowy rozdział';
        $courseCategory->sort = 0;
        $courseCategory->is_published = false;
        $courseCategory->save();

        return Redirect::back();
    }

    public function store(Request $request, ImageService $imageService): mixed
    {
        if(!empty($request->get('id'))){
            $course = Course::find($request->get('id'));
            $data = $request->all();

            if(isset($data['is_published']) && $data['is_published'] === '1'){
                $course->is_published = true;
            }else{
                $course->is_published = false;
            }
            unset($data['id']);
            unset($data['is_published']);

            $course->update($data);

            $course->save();

            $image = $request->file('image');
            if ($image) {
                $filePath = $imageService->uploadImage($image);
                $course->image = $filePath;
                $course->save();
            }
        }

        return Redirect::back();
    }

    public function edit(Request $request, int $id): View
    {
        $defaultLangue = env('APP_LOCALE');
        $course = Course::find($id);
        $courseCategories = CourseCategory::where('course_id', $id)->orderBy('sort', 'asc')->get();

        $lessonsNotIn = [];
        foreach (CourseCategoryLesson::get() as $lesson){
            $lessonsNotIn[] = $lesson->lesson_id;
        }

        $articles = Article::where('language', $defaultLangue)->whereNotIn('id', $lessonsNotIn)->where('is_published', false)->get();


        return view('courses.edit', [
            'course' => $course,
            'courseCategories' => $courseCategories,
            'articles' => $articles,
        ]);
    }

    public function editCategory(Request $request, CourseCategory $category): mixed
    {
        $data = $request->all();

        if(isset($data['is_published']) && $data['is_published'] === '1'){
            $category->is_published = true;
        }else{
            $category->is_published = false;
        }
        unset($data['id']);
        unset($data['is_published']);

        $category->update($data);

        $category->save();

        return Redirect::back();
    }

    public function editCategoryShort(Request $request): mixed
    {

        foreach ($request->get('category_name') as $couseCategortId => $value){
            $courseCategory = CourseCategory::find($couseCategortId);
            $courseCategory->category_name = $value;
            $courseCategory->save();
        }

        foreach ($request->get('slug') as $couseCategortId => $value){
            $courseCategory = CourseCategory::find($couseCategortId);
            $courseCategory->slug = $value;
            $courseCategory->save();
        }

        foreach ($request->get('sort') as $couseCategortId => $value){
            $courseCategory = CourseCategory::find($couseCategortId);
            $courseCategory->sort = $value;
            $courseCategory->save();
        }


        return Redirect::back();
    }

    public function fetchLessonToChoose(Request $request, CourseCategory $category): mixed
    {
        $defaultLangue = env('APP_LOCALE');
        $articles = Article::where('language', $defaultLangue)->where('is_published', false)->get();
        $results = [];

        foreach ($articles as $article){
            $results[] = [
                'id' => $article->id,
                'title' => $article->name,
            ];
        }

        return response()->json($results);
    }

    public function chooseLesson(Request $request): mixed
    {
        $data = $request->all();

        $lesson = new CourseCategoryLesson();
        $lesson->course_category_id = $data['category'];
        $lesson->lesson_id = $data['article'];
        $lesson->save();


        return Redirect::back();
    }

    public function editCategoryLessonsPositions(Request $request): mixed
    {
        $data = $request->all();

        foreach ($data['sort'] as $categoryLessonId => $value){
            $lesson = CourseCategoryLesson::find($categoryLessonId);
            $lesson->sort = $value;
            $lesson->save();
        }

        return Redirect::back();
    }

    public function removeCategoryLessonsPositions(Request $request, int $id): mixed
    {
        $lesson = CourseCategoryLesson::find($id);
        $lesson->delete();

        return Redirect::back();
    }
}
