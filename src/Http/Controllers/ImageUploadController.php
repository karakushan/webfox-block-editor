<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Upload an image file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif,webp,svg,avif',
                'max:10240', // 10MB max
            ],
            'oldPath' => 'nullable|string',
        ]);

        try {
            // Delete old image if provided
            $oldPath = $request->input('oldPath');
            if ($oldPath) {
                $this->deleteImageByPath($oldPath);
            }

            $file = $request->file('image');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());

            // Ensure extension is preserved correctly for SVG, AVIF, WEBP
            $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg', 'avif'];
            if (!in_array($extension, $allowedExtensions)) {
                // Try to detect from MIME type
                $mimeType = $file->getMimeType();
                $extensionMap = [
                    'image/svg+xml' => 'svg',
                    'image/avif' => 'avif',
                    'image/webp' => 'webp',
                ];
                if (isset($extensionMap[$mimeType])) {
                    $extension = $extensionMap[$mimeType];
                }
            }

            // Generate unique filename using Laravel's built-in hash
            $hashName = $file->hashName();
            $hashWithoutExt = pathinfo($hashName, PATHINFO_FILENAME);
            $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . $hashWithoutExt . '.' . $extension;

            // Store in public storage
            $path = $file->storeAs('images/blocks', $fileName, 'public');

            if (! $path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image',
                ], 500);
            }

            // Return relative URL path (without domain)
            // Storage::url() returns full URL, but we need relative path for asset()
            $url = '/storage/' . $path;

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload image', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete image by path (helper method).
     *
     * @param  string  $path
     * @return void
     */
    protected function deleteImageByPath(string $path): void
    {
        try {
            // Remove 'storage/' prefix if present
            $path = str_replace('storage/', '', $path);
            // Remove '/storage/' prefix if present
            $path = str_replace('/storage/', '', $path);
            // Remove 'public/' prefix if present
            $path = str_replace('public/', '', $path);

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            Log::warning('Failed to delete old image', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);
        }
    }

    /**
     * Delete an uploaded image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $request->input('path');
            $this->deleteImageByPath($path);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete image', [
                'error' => $e->getMessage(),
                'path' => $request->input('path'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload image for TinyMCE editor.
     * Returns response in TinyMCE expected format.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadForTinyMCE(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif,webp,svg,avif',
                'max:10240', // 10MB max
            ],
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());

            // Ensure extension is preserved correctly for SVG, AVIF, WEBP
            $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg', 'avif'];
            if (!in_array($extension, $allowedExtensions)) {
                // Try to detect from MIME type
                $mimeType = $file->getMimeType();
                $extensionMap = [
                    'image/svg+xml' => 'svg',
                    'image/avif' => 'avif',
                    'image/webp' => 'webp',
                ];
                if (isset($extensionMap[$mimeType])) {
                    $extension = $extensionMap[$mimeType];
                }
            }

            // Generate unique filename using Laravel's built-in hash
            $hashName = $file->hashName();
            $hashWithoutExt = pathinfo($hashName, PATHINFO_FILENAME);
            $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . $hashWithoutExt . '.' . $extension;

            // Store in public storage
            $path = $file->storeAs('images/blocks', $fileName, 'public');

            if (! $path) {
                return response()->json([
                    'location' => null,
                    'error' => 'Failed to upload image',
                ], 500);
            }

            // Return URL in TinyMCE expected format
            // TinyMCE expects 'location' field with full URL
            $url = url('/storage/' . $path);

            return response()->json([
                'location' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload image for TinyMCE', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'location' => null,
                'error' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }
}

