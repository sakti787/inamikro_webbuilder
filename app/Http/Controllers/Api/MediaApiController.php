<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadMediaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaApiController extends Controller
{
    /**
     * Store an uploaded image in the authenticated UMKM user's tenant directory on the public disk.
     */
    public function store(UploadMediaRequest $request): JsonResponse
    {
        $path = $request->file('image')->store('sites/'.$request->user()->id, 'public');

        return response()->json([
            'url' => '/storage/'.$path,
        ], 201);
    }

    /**
     * Delete a media file owned by the authenticated UMKM user after enforcing strict path checks.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'url' => ['required', 'string', 'max:1000'],
            ],
            [
                'url.required' => 'URL gambar wajib diisi.',
                'url.string' => 'URL gambar harus berupa teks.',
                'url.max' => 'URL gambar maksimal 1000 karakter.',
            ],
        );

        $url = $validated['url'];
        $expectedPrefix = '/storage/sites/'.$request->user()->id.'/';

        if ($this->hasUnsafePath($url) || ! str_starts_with($url, $expectedPrefix)) {
            abort(403, 'Akses ditolak.');
        }

        $filename = substr($url, strlen($expectedPrefix));

        if ($filename === '' || str_contains($filename, '/')) {
            abort(403, 'Akses ditolak.');
        }

        $relativePath = substr($url, strlen('/storage/'));
        Storage::disk('public')->delete($relativePath);

        return response()->json([
            'deleted' => true,
        ]);
    }

    /**
     * Detect directory traversal tokens, backslashes, null bytes, or URL-encoded traversal sequences.
     */
    private function hasUnsafePath(string $url): bool
    {
        if (str_contains($url, '..') || str_contains($url, '\\') || str_contains($url, "\0")) {
            return true;
        }

        if (preg_match('/%(?:2e|5c|00)/i', $url) === 1) {
            return true;
        }

        $decoded = rawurldecode($url);

        return str_contains($decoded, '..') || str_contains($decoded, '\\') || str_contains($decoded, "\0");
    }
}
