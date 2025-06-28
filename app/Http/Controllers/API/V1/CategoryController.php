<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ResponseTrait;

    // Create a new category
    public function categoryCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:20480',
            'status' => 'nullable|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $slug = Str::slug($request->name);
            $slug = $this->makeUniqueSlug($slug);

            $filePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filePath = Helper::fileUpload($file, 'categories', $file->getClientOriginalName());
                if (!$filePath) {
                    throw new Exception('Image upload failed');
                }
            }

            $category = Category::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $filePath,
                'slug' => $slug,
                'status' => $request->status ?? 'active',
            ]);

            // Prepare response data with image_url
            $responseData = $category->toArray();
            $responseData['image'] = $filePath;
            $responseData['image_url'] = $filePath ? asset($filePath) : null;

            DB::commit();
            return $this->sendResponse($responseData, 'Category created successfully', 201);
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            Log::error('Duplicate entry during category creation: ' . $e->getMessage());
            return $this->sendError('Category name or slug already exists', 409);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Category creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    // Update an existing category
    public function categoryUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'status' => 'nullable|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (!$category) {
                DB::rollBack();
                return $this->sendError('Category not found', 404);
            }

            $slug = Str::slug($request->name);
            $slug = $this->makeUniqueSlug($slug, $id);

            $filePath = $category->getRawOriginal('image');
            if ($request->hasFile('image')) {
                if ($filePath) {
                    Helper::fileDelete($filePath);
                }
                $file = $request->file('image');
                $filePath = Helper::fileUpload($file, 'categories', $file->getClientOriginalName());
                if (!$filePath) {
                    throw new \Exception('Image upload failed');
                }
            }

            $category->update([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $filePath,
                'slug' => $slug,
                'status' => $request->status ?? $category->status,
            ]);

            // Prepare response data with image_url
            $responseData = $category->toArray();
            $responseData['image'] = $filePath;
            $responseData['image_url'] = $filePath ? asset($filePath) : null;

            DB::commit();
            return $this->sendResponse($responseData, 'Category updated successfully');
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            Log::error('Duplicate entry during category update: ' . $e->getMessage());
            return $this->sendError('Category name or slug already exists', 409);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Category update failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    // List all categories
    public function categoryList()
    {
        try {
            $categories = Category::latest()
                ->select(['id', 'name', 'description', 'image', 'slug', 'status', 'created_at', 'updated_at'])
                ->get();


            // Prepare response data with image_url
            $responseData = $categories->map(function ($category) {
                $data = $category->toArray();
                $data['image'] = $category->getRawOriginal('image');
                $data['image_url'] = $data['image'] ? asset($data['image']) : null;
                return $data;
            })->toArray();

            return $this->sendResponse($responseData, 'Category list retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve category list: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }

    // Delete a category
    public function categoryDelete($id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return $this->sendError('Category not found', 404);
            }

            // Delete image if exists
            $imagePath = $category->getRawOriginal('image');
            if ($imagePath) {
                Helper::fileDelete($imagePath);
            }

            $category->delete();

            return $this->sendResponse(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Category deletion failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }

    // Helper method to generate unique slugs
    protected function makeUniqueSlug($slug, $excludeId = null)
    {
        $originalSlug = $slug;
        $count = 1;

        while (Category::where('slug', $slug)
            ->where('id', '!=', $excludeId)
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }
}
