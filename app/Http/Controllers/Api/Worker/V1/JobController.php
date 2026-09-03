<?php

namespace App\Http\Controllers\Api\Worker\V1;

use App\Enums\LenticularJobStatus;
use App\Http\Controllers\Controller;
use App\Models\LenticularArtifact;
use App\Models\LenticularJob;
use App\Models\LenticularJobEvent;
use App\Models\LenticularProjectFile;
use App\Models\ProcessingMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function claim(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lease_seconds' => ['required', 'integer', 'min:30', 'max:'.config('lenticular_machine.maximum_lease_seconds')],
            'capabilities' => ['required', 'array', 'min:1'],
            'capabilities.*' => ['required', 'string', 'max:100'],
        ]);
        /** @var ProcessingMachine $machine */
        $machine = $request->attributes->get('processingMachine');

        $job = DB::transaction(function () use ($machine, $validated): ?LenticularJob {
            $operations = collect($validated['capabilities'])
                ->map(fn (string $capability): string => Str::before($capability, ':'))
                ->unique()->all();
            $job = LenticularJob::query()
                ->whereIn('operation', $operations)
                ->where(function ($query): void {
                    $query->where('status', LenticularJobStatus::Queued)
                        ->orWhere(function ($expired): void {
                            $expired->whereIn('status', [LenticularJobStatus::Leased, LenticularJobStatus::Downloading, LenticularJobStatus::Processing, LenticularJobStatus::Uploading])
                                ->where('lease_expires_at', '<=', now());
                        });
                })
                ->orderBy('priority')->orderBy('created_at')->lockForUpdate()->first();

            if ($job === null) {
                return null;
            }

            $job->update([
                'processing_machine_id' => $machine->id,
                'status' => LenticularJobStatus::Leased,
                'lease_token' => Str::random(64),
                'lease_expires_at' => now()->addSeconds($validated['lease_seconds']),
                'attempts' => $job->attempts + 1,
                'started_at' => $job->started_at ?? now(),
                'progress' => 0,
                'stage' => 'leased',
            ]);

            return $job->fresh(['sourceFile']);
        });

        if ($job === null) {
            return response()->json(null, 204);
        }

        LenticularJobEvent::query()->create(['lenticular_job_id' => $job->id, 'type' => 'leased', 'payload' => ['machine_id' => $machine->machine_id]]);
        $source = $job->sourceFile;

        return response()->json([
            'job_id' => $job->id,
            'lease_token' => $job->lease_token,
            'operation' => $job->operation,
            'artifact_kind' => $this->artifactKind($job),
            'source' => [
                'url' => $this->absoluteUrl(URL::temporarySignedRoute('worker.transfers.source', now()->addMinutes(config('lenticular_machine.transfer_url_minutes')), ['job' => $job->id, 'lease_token' => $job->lease_token], absolute: false)),
                'sha256' => $source->sha256,
                'size_bytes' => $source->size_bytes,
                'filename' => $source->original_name,
            ],
            'upload_url' => $this->absoluteUrl(URL::temporarySignedRoute('worker.transfers.result', now()->addMinutes(config('lenticular_machine.transfer_url_minutes')), ['job' => $job->id, 'lease_token' => $job->lease_token], absolute: false)),
            'selection' => $job->parameters['selection'] ?? null,
            'alignment' => $job->parameters['alignment'] ?? null,
        ]);
    }

    public function heartbeat(Request $request, LenticularJob $job): JsonResponse
    {
        $validated = $this->validateLeaseRequest($request);
        $this->assertLease($request, $job, $validated['lease_token']);
        $job->update(['lease_expires_at' => now()->addSeconds($validated['lease_seconds'])]);

        return response()->json(['lease_expires_at' => $job->lease_expires_at->toISOString()]);
    }

    public function progress(Request $request, LenticularJob $job): JsonResponse
    {
        $validated = $request->validate(['lease_token' => ['required', 'string', 'size:64'], 'percent' => ['required', 'integer', 'between:0,99'], 'stage' => ['required', 'string', 'max:100']]);
        $this->assertLease($request, $job, $validated['lease_token']);
        $job->update(['progress' => $validated['percent'], 'stage' => $validated['stage'], 'status' => $this->statusForStage($validated['stage'])]);
        LenticularJobEvent::query()->create(['lenticular_job_id' => $job->id, 'type' => 'progress', 'payload' => ['percent' => $validated['percent'], 'stage' => $validated['stage']]]);

        return response()->json(['status' => $job->status->value, 'progress' => $job->progress]);
    }

    public function complete(Request $request, LenticularJob $job): JsonResponse
    {
        $analysisPresence = $job->operation === 'analyze_video' ? 'required' : 'nullable';
        $alignmentPresence = $job->operation === 'align_sequence' ? 'required' : 'nullable';
        $validated = $request->validate([
            'lease_token' => ['required', 'string', 'size:64'],
            'artifact.sha256' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'artifact.size_bytes' => ['required', 'integer', 'min:1'],
            'artifact.media_type' => ['required', 'in:application/zip'],
            'result.video' => [$analysisPresence, 'array'],
            'result.video.width' => [$analysisPresence, 'integer', 'min:1'],
            'result.video.height' => [$analysisPresence, 'integer', 'min:1'],
            'result.video.frame_count' => [$analysisPresence, 'integer', 'min:1'],
            'result.video.fps' => [$analysisPresence, 'numeric', 'gt:0'],
            'result.video.duration_seconds' => [$analysisPresence, 'numeric', 'gt:0'],
            'result.thumbnails' => [$analysisPresence, 'array', 'size:3'],
            'result.thumbnails.*' => ['string', 'max:1400000'],
            'result.timeline' => [$analysisPresence, 'array', 'between:1,20'],
            'result.timeline.*.frame_index' => ['required_with:result.timeline', 'integer', 'min:0'],
            'result.timeline.*.image' => ['required_with:result.timeline', 'string', 'max:500000'],
            'result.alignment' => [$alignmentPresence, 'array'],
            'result.alignment.crop' => [$alignmentPresence, 'array', 'size:4'],
            'result.alignment.crop.*' => ['integer', 'min:0'],
            'result.alignment.transforms' => [$alignmentPresence, 'array', 'min:2'],
            'result.alignment.transforms.*.filename' => ['required_with:result.alignment', 'string', 'max:255'],
            'result.alignment.transforms.*.x' => ['required_with:result.alignment', 'numeric'],
            'result.alignment.transforms.*.y' => ['required_with:result.alignment', 'numeric'],
            'result.alignment.transforms.*.score' => ['required_with:result.alignment', 'numeric'],
            'result.previews' => [$alignmentPresence, 'array', 'between:1,2'],
            'result.previews.*' => ['string', 'max:1400000'],
        ]);
        if ($job->status === LenticularJobStatus::Completed) {
            /** @var ProcessingMachine $machine */
            $machine = $request->attributes->get('processingMachine');
            abort_unless($job->processing_machine_id === $machine->id && $job->lease_token !== null && hash_equals($job->lease_token, $validated['lease_token']), 409, 'Job lease is invalid.');

            return response()->json(['status' => 'completed']);
        }
        $this->assertLease($request, $job, $validated['lease_token']);
        $artifact = LenticularArtifact::query()->where('lenticular_job_id', $job->id)->where('kind', $this->artifactKind($job))->first();
        abort_if($artifact === null || ! hash_equals($artifact->sha256, $validated['artifact']['sha256']) || $artifact->size_bytes !== $validated['artifact']['size_bytes'], 422, 'Uploaded artifact does not match completion metadata.');
        if ($job->operation === 'analyze_video') {
            $this->storeAnalysisResult($job, $validated['result']);
        } elseif ($job->operation === 'align_sequence') {
            $this->storeAlignmentResult($job, $validated['result']);
        }
        $job->update(['status' => LenticularJobStatus::Completed, 'progress' => 100, 'stage' => 'completed', 'completed_at' => now(), 'lease_expires_at' => null]);
        LenticularJobEvent::query()->create(['lenticular_job_id' => $job->id, 'type' => 'completed', 'payload' => ['artifact_id' => $artifact->id]]);

        return response()->json(['status' => 'completed']);
    }

    public function fail(Request $request, LenticularJob $job): JsonResponse
    {
        $validated = $request->validate(['lease_token' => ['required', 'string', 'size:64'], 'error.code' => ['required', 'string', 'max:100'], 'error.message' => ['required', 'string', 'max:1000']]);
        $this->assertLease($request, $job, $validated['lease_token']);
        $job->update(['status' => LenticularJobStatus::Failed, 'stage' => 'failed', 'error_code' => $validated['error']['code'], 'error_message' => $validated['error']['message'], 'lease_expires_at' => null, 'completed_at' => now()]);
        LenticularJobEvent::query()->create(['lenticular_job_id' => $job->id, 'type' => 'failed', 'payload' => $validated['error']]);

        return response()->json(['status' => 'failed']);
    }

    private function validateLeaseRequest(Request $request): array
    {
        return $request->validate(['lease_token' => ['required', 'string', 'size:64'], 'lease_seconds' => ['required', 'integer', 'min:30', 'max:'.config('lenticular_machine.maximum_lease_seconds')]]);
    }

    private function assertLease(Request $request, LenticularJob $job, string $token): void
    {
        /** @var ProcessingMachine $machine */
        $machine = $request->attributes->get('processingMachine');
        abort_unless($job->processing_machine_id === $machine->id && $job->lease_token !== null && hash_equals($job->lease_token, $token) && $job->lease_expires_at?->isFuture(), 409, 'Job lease is invalid or expired.');
    }

    private function statusForStage(string $stage): LenticularJobStatus
    {
        return match ($stage) {
            'downloading' => LenticularJobStatus::Downloading,
            'uploading' => LenticularJobStatus::Uploading,
            default => LenticularJobStatus::Processing,
        };
    }

    private function absoluteUrl(string $relativeUrl): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($relativeUrl, '/');
    }

    private function artifactKind(LenticularJob $job): string
    {
        return match ($job->operation) {
            'analyze_video' => 'analysis', 'align_sequence' => 'aligned', default => 'frames'
        };
    }

    private function storeAnalysisResult(LenticularJob $job, array $result): void
    {
        $source = $job->sourceFile;
        $source?->update(['metadata' => $result['video']]);
        foreach ($result['thumbnails'] ?? [] as $index => $encoded) {
            $contents = base64_decode($encoded, true);
            abort_unless(is_string($contents) && strlen($contents) <= 1048576 && str_starts_with($contents, "\xFF\xD8\xFF"), 422, 'Invalid analysis thumbnail.');
            $path = "lenticular/previews/{$job->lenticular_project_id}/thumbnail_{$index}.jpg";
            Storage::disk(config('lenticular_machine.disk'))->put($path, $contents);
            LenticularProjectFile::query()->updateOrCreate(
                ['lenticular_project_id' => $job->lenticular_project_id, 'kind' => "analysis_thumbnail_{$index}"],
                ['disk' => config('lenticular_machine.disk'), 'path' => $path, 'original_name' => "thumbnail_{$index}.jpg", 'media_type' => 'image/jpeg', 'size_bytes' => strlen($contents), 'sha256' => hash('sha256', $contents), 'metadata' => []]
            );
        }
        foreach ($result['timeline'] ?? [] as $index => $timeline) {
            $contents = base64_decode($timeline['image'], true);
            abort_unless(is_string($contents) && strlen($contents) <= 350000 && str_starts_with($contents, "\xFF\xD8\xFF"), 422, 'Invalid timeline thumbnail.');
            $path = "lenticular/previews/{$job->lenticular_project_id}/timeline_{$index}.jpg";
            Storage::disk(config('lenticular_machine.disk'))->put($path, $contents);
            LenticularProjectFile::query()->updateOrCreate(
                ['lenticular_project_id' => $job->lenticular_project_id, 'kind' => "timeline_thumbnail_{$index}"],
                ['disk' => config('lenticular_machine.disk'), 'path' => $path, 'original_name' => "timeline_{$index}.jpg", 'media_type' => 'image/jpeg', 'size_bytes' => strlen($contents), 'sha256' => hash('sha256', $contents), 'metadata' => ['frame_index' => $timeline['frame_index']]]
            );
        }
    }

    private function storeAlignmentResult(LenticularJob $job, array $result): void
    {
        $project = $job->lenticularProject;
        $project->update(['settings' => array_merge($project->settings ?? [], ['alignment' => $result['alignment']])]);
        foreach ($result['previews'] as $index => $encoded) {
            $contents = base64_decode($encoded, true);
            abort_unless(is_string($contents) && strlen($contents) <= 1048576 && str_starts_with($contents, "\xFF\xD8\xFF"), 422, 'Invalid alignment preview.');
            $path = "lenticular/previews/{$job->lenticular_project_id}/alignment_{$index}.jpg";
            Storage::disk(config('lenticular_machine.disk'))->put($path, $contents);
            LenticularProjectFile::query()->updateOrCreate(
                ['lenticular_project_id' => $job->lenticular_project_id, 'kind' => "alignment_preview_{$index}"],
                ['disk' => config('lenticular_machine.disk'), 'path' => $path, 'original_name' => "alignment_{$index}.jpg", 'media_type' => 'image/jpeg', 'size_bytes' => strlen($contents), 'sha256' => hash('sha256', $contents), 'metadata' => []]
            );
        }
    }
}
