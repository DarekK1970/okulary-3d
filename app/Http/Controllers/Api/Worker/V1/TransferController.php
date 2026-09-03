<?php

namespace App\Http\Controllers\Api\Worker\V1;

use App\Enums\LenticularJobStatus;
use App\Http\Controllers\Controller;
use App\Models\LenticularArtifact;
use App\Models\LenticularJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransferController extends Controller
{
    public function source(Request $request, LenticularJob $job): BinaryFileResponse
    {
        $this->assertSignedLease($request, $job);
        $source = $job->sourceFile;
        abort_if($source === null || ! Storage::disk($source->disk)->exists($source->path), 404);

        return response()->file(Storage::disk($source->disk)->path($source->path), [
            'Content-Type' => $source->media_type ?? 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.addslashes($source->original_name).'"',
        ]);
    }

    public function result(Request $request, LenticularJob $job): JsonResponse
    {
        $this->assertSignedLease($request, $job);
        abort_unless(in_array($job->status, [LenticularJobStatus::Leased, LenticularJobStatus::Downloading, LenticularJobStatus::Processing, LenticularJobStatus::Uploading], true), 409);
        $kind = $job->operation === 'analyze_video' ? 'analysis' : 'frames';
        $path = "lenticular/results/{$job->id}/{$kind}.zip";
        $stream = $request->getContent(true);
        abort_unless(is_resource($stream), 422, 'Artifact body is required.');
        abort_unless(Storage::disk(config('lenticular_machine.disk'))->writeStream($path, $stream), 500, 'Artifact could not be stored.');
        if (is_resource($stream)) {
            fclose($stream);
        }
        $absolutePath = Storage::disk(config('lenticular_machine.disk'))->path($path);
        $size = filesize($absolutePath);
        abort_if($size === false || $size < 1, 422, 'Artifact body is required.');
        $sha256 = hash_file('sha256', $absolutePath);
        $artifact = LenticularArtifact::query()->updateOrCreate(
            ['lenticular_job_id' => $job->id, 'kind' => $kind],
            ['disk' => config('lenticular_machine.disk'), 'path' => $path, 'media_type' => 'application/zip', 'size_bytes' => $size, 'sha256' => $sha256]
        );

        return response()->json(['sha256' => $artifact->sha256, 'size_bytes' => $artifact->size_bytes], 201);
    }

    private function assertSignedLease(Request $request, LenticularJob $job): void
    {
        $token = (string) $request->query('lease_token', '');
        abort_unless($request->hasValidRelativeSignature() && $job->lease_token !== null && hash_equals($job->lease_token, $token) && $job->lease_expires_at?->isFuture(), 403);
    }
}
