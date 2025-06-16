<?php

namespace App\Helpers;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;


class Helper
{
    // File or Image Upload
    public static function fileUpload($file, string $folder, string $name): ?string
    {
        // Ensure that file exists in the request
        if (!$file || !$file->isValid()) return null;

        // Get the original file name
        $originalName = $file->getClientOriginalName();
        $path = public_path('uploads/' . $folder);

        // Create directory if it doesn't exist
        if (!file_exists($path)) mkdir($path, 0755, true);

        // Move the file to the directory
        $file->move($path, $originalName);

        // Return the path of the uploaded file
        return 'uploads/' . $folder . '/' . $originalName;
    }

    // File or Image Delete
    public static function fileDelete(string $path): void
    {
        // If path is a URL, convert to local path
        if (str_starts_with($path, 'http')) {
            $path = parse_url($path, PHP_URL_PATH);
            $path = public_path($path);
        }
        // If path is relative (uploads/...), make it absolute
        elseif (!str_starts_with($path, public_path())) {
            $path = public_path($path);
        }

        if (file_exists($path)) {
            unlink($path);
        }
    }

    // Generate Slug
    public static function makeSlug($model, string $name): string
    {
        $slug = Str::slug($name);
        while ($model::where('slug', $slug)->exists()) {
            $randomString = Str::random(5);
            $slug         = Str::slug($name) . '-' . $randomString;
        }
        return $slug;
    }

    // Generate Unique Slug
    public function createUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $count = Product::where('slug', $slug)->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }
    //generate SKU
    public function generateUniqueSku()
    {
        do {
            $sku = (string) Str::uuid();
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
