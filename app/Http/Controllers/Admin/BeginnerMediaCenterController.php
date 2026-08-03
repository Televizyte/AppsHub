<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Support\ActiveApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BeginnerMediaCenterController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        if ($activeAppId < 1) {
            return response()->json([
                'ok' => false,
                'message' => 'No active app selected.',
            ], 422);
        }

        // 🔥 SUPPORT BOTH IMAGE + VIDEO
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:204800'], // 200MB
            'bucket' => ['nullable', 'string', 'max:80'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');

        if (! $file) {
            return response()->json([
                'ok' => false,
                'message' => 'No file received.',
            ], 422);
        }

        $mime = $file->getMimeType();

        // 🔥 DETECT TYPE
        $type = str_starts_with($mime, 'video/') ? 'video' : 'image';

        $bucket = $this->cleanBucket($validated['bucket'] ?? 'media-center');
        $label = trim((string) ($validated['label'] ?? ''));

        if ($label === '') {
            $label = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Media Upload';
        }

        $safeLabel = Str::slug($label) ?: 'media-upload';
        $extension = strtolower($file->getClientOriginalExtension() ?: ($type === 'video' ? 'mp4' : 'jpg'));

        $filename = $safeLabel . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(6)) . '.' . $extension;

        $path = $file->storeAs(
            'assets/app-' . $activeAppId . '/media-center/' . $bucket,
            $filename,
            'public'
        );

        $url = Storage::disk('public')->url($path);

        $asset = MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => $type,
            'label' => $label,
            'bucket' => $bucket,
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $mime,
            'size' => $file->getSize(),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return response()->json([
            'ok' => true,
            'asset' => [
                'id' => $asset->id,
                'url' => $url,
                'type' => $type,
                'label' => $label,
                'bucket' => $bucket,
            ],
        ]);
    }

    private function cleanBucket(?string $bucket): string
    {
        $bucket = strtolower(trim((string) $bucket));
        $bucket = preg_replace('/[^a-z0-9\-_]+/', '-', $bucket) ?: 'media-center';
        return trim($bucket, '-_') ?: 'media-center';
    }
}
