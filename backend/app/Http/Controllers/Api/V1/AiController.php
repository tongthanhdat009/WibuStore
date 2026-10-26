<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ai\LlmService;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiController extends Controller
{
    public function chat(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|string|in:user,ai,assistant',
            'history.*.text' => 'required_with:history|string|max:1000',
        ]);

        try {
            $out = LlmService::shopAssistant($data['message'], $data['history'] ?? []);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $code = str_contains($msg, 'LLM_API_KEY') ? 'LLM_NOT_CONFIGURED' : 'LLM_UNAVAILABLE';

            return response()->json(['error' => ['code' => $code, 'message' => $msg]], 502);
        }

        return response()->json(['data' => [
            'reply' => $out['text'],
            'model' => $out['model'],
            'products' => $out['products'] ?? [],
        ]]);
    }

    public function vision(Request $request)
    {
        $data = $request->validate([
            'image' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'prompt' => 'nullable|string|max:500',
        ]);

        $file = $data['image'];
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $bytes = file_get_contents($file->getRealPath());
        if ($bytes === false || $bytes === '') {
            return response()->json(['error' => ['code' => 'INVALID_IMAGE', 'message' => 'Khong doc duoc file anh.']], 422);
        }

        try {
            $out = LlmService::describeImage($bytes, $mime, $data['prompt'] ?? null);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $code = str_contains($msg, 'LLM_API_KEY') ? 'LLM_NOT_CONFIGURED' : 'LLM_UNAVAILABLE';

            return response()->json(['error' => ['code' => $code, 'message' => $msg]], 502);
        }

        return response()->json(['data' => [
            'description' => $out['text'],
            'model' => $out['model'],
            'mime_type' => $mime,
        ]]);
    }

    public function image(Request $request)
    {
        $data = $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        try {
            $out = LlmService::generateImage($data['prompt']);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $code = str_contains($msg, 'LLM_API_KEY') ? 'LLM_NOT_CONFIGURED' : 'LLM_UNAVAILABLE';

            return response()->json(['error' => ['code' => $code, 'message' => $msg]], 502);
        }

        $ext = $out['mime'] === 'image/jpeg' ? 'jpg' : ($out['mime'] === 'image/webp' ? 'webp' : 'png');
        $key = 'public/ai/'.now()->format('Y/m').'/'.Str::uuid().'.'.$ext;
        Storage::disk(config('filesystems.default'))->put($key, $out['bytes']);
        [$w, $h] = @getimagesizefromstring($out['bytes']) ?: [null, null];
        $asset = MediaAsset::create([
            'object_key' => $key, 'disk' => config('filesystems.default'), 'mime_type' => $out['mime'],
            'file_size_bytes' => strlen($out['bytes']), 'width' => $w, 'height' => $h,
            'checksum' => hash('sha256', $out['bytes']), 'alt_text' => mb_substr($data['prompt'], 0, 255),
            'visibility' => 'public', 'status' => 'ready', 'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => [
            'url' => $asset->url(),
            'mime_type' => $out['mime'],
            'model' => $out['model'],
            'media_asset_id' => $asset->id,
        ]], 201);
    }

    public function tryOn(Request $request)
    {
        $data = $request->validate([
            'person' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'outfits' => 'required|array|min:1|max:3',
            'outfits.*' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'prompt' => 'nullable|string|max:300',
        ]);

        $personFile = $data['person'];
        $personBytes = file_get_contents($personFile->getRealPath());
        if ($personBytes === false || $personBytes === '') {
            return response()->json(['error' => ['code' => 'INVALID_IMAGE', 'message' => 'Khong doc duoc anh nguoi.']], 422);
        }
        $outfits = [];
        foreach ($data['outfits'] as $f) {
            $b = file_get_contents($f->getRealPath());
            if ($b === false || $b === '') {
                return response()->json(['error' => ['code' => 'INVALID_IMAGE', 'message' => 'Khong doc duoc anh trang phuc.']], 422);
            }
            $outfits[] = ['bytes' => $b, 'mime' => $f->getMimeType() ?: $f->getClientMimeType()];
        }

        try {
            $out = LlmService::tryOn(
                $personBytes,
                $personFile->getMimeType() ?: $personFile->getClientMimeType(),
                $outfits,
                $data['prompt'] ?? null
            );
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $code = str_contains($msg, 'LLM_API_KEY') ? 'LLM_NOT_CONFIGURED' : 'LLM_UNAVAILABLE';

            return response()->json(['error' => ['code' => $code, 'message' => $msg]], 502);
        }

        $ext = $out['mime'] === 'image/jpeg' ? 'jpg' : ($out['mime'] === 'image/webp' ? 'webp' : 'png');
        $key = 'public/ai/'.now()->format('Y/m').'/'.Str::uuid().'.'.$ext;
        Storage::disk(config('filesystems.default'))->put($key, $out['bytes']);
        [$w, $h] = @getimagesizefromstring($out['bytes']) ?: [null, null];
        $asset = MediaAsset::create([
            'object_key' => $key, 'disk' => config('filesystems.default'), 'mime_type' => $out['mime'],
            'file_size_bytes' => strlen($out['bytes']), 'width' => $w, 'height' => $h,
            'checksum' => hash('sha256', $out['bytes']), 'alt_text' => 'Virtual try-on',
            'visibility' => 'public', 'status' => 'ready', 'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => [
            'url' => $asset->url(),
            'mime_type' => $out['mime'],
            'model' => $out['model'],
            'media_asset_id' => $asset->id,
        ]], 201);
    }
}
