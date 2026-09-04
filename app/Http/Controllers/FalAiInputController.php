<?php

namespace App\Http\Controllers;

use App\Models\LenticularProjectFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FalAiInputController extends Controller
{
    public function __invoke(LenticularProjectFile $file): BinaryFileResponse
    {
        abort_unless(request()->hasValidSignature(), 403);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return response()->file(Storage::disk($file->disk)->path($file->path), [
            'Content-Type' => $file->media_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
