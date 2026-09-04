<?php

namespace App\Http\Controllers;

use App\Enums\FalAiJobOperation;
use App\Jobs\PrepareSinglePhotoLenticularJob;
use App\Models\FalAiJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Services\AiTranslationSettingsService;
use App\Services\FalAiJobService;
use App\Services\FalAiSettingsService;
use App\Services\LenticularAccessService;
use App\Services\LenticularSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiLenticularProjectController extends Controller
{
    public function createPair(Request $request, string $locale, FalAiSettingsService $settings, LenticularAccessService $access): View
    {
        $plan = $this->authorizeAgentPlan($request, $access);

        return view('lab.projects.ai-pair', ['settings' => $settings, 'printSizes' => $access->agentPrintSizes($plan)]);
    }

    public function storePair(Request $request, string $locale, FalAiSettingsService $settings, FalAiJobService $jobs, LenticularAccessService $access): RedirectResponse
    {
        $plan = $this->authorizeAgentPlan($request, $access);
        abort_unless($settings->configured(), 503, 'AI service is temporarily unavailable.');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'start_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'end_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'print_size' => ['required', Rule::in($access->agentPrintSizes($plan))],
            'lpi' => ['required', Rule::in([50, 60, 75])],
            'confirm_ai_cost' => ['accepted'],
        ]);

        $this->ensureBudgetAvailable($settings);

        $project = LenticularProject::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'settings' => ['workflow' => 'ai_pair', 'print_size' => $validated['print_size'], 'dpi' => 1440, 'lpi' => (int) $validated['lpi'], 'max_frames' => 25],
        ]);
        $start = $this->storeImage($project, $request->file('start_image'), 'ai_start_image');
        $end = $this->storeImage($project, $request->file('end_image'), 'ai_end_image');

        $job = $jobs->create(
            $project,
            FalAiJobOperation::ImageToVideo,
            $settings->seedanceModel(),
            [
                'prompt' => 'A perfectly smooth horizontal camera orbit transition from the provided start frame to the provided end frame. The entire scene remains frozen in time. No subject movement, no wind, no background animation, no cuts, no zoom and no camera shake. Preserve identity, geometry, lighting and all scene details. Only the camera viewpoint changes along a precise steady horizontal arc.',
                'resolution' => $settings->resolution(),
                'duration' => (string) $settings->duration(),
                'aspect_ratio' => 'auto',
                'generate_audio' => false,
                'bitrate_mode' => 'high',
            ],
            (string) Str::uuid(),
            $start,
            $end,
            $settings->maximumJobCost(),
        );
        $jobs->queueForSubmission($job);

        return redirect()->route('lab.lenticular.ai.jobs.show', ['locale' => $locale, 'job' => $job]);
    }

    public function createSingle(Request $request, string $locale, FalAiSettingsService $settings, AiTranslationSettingsService $agentSettings, LenticularAccessService $access): View
    {
        $plan = $this->authorizeAgentPlan($request, $access);

        return view('lab.projects.ai-single', ['settings' => $settings, 'agentReady' => $agentSettings->configured('openai'), 'printSizes' => $access->agentPrintSizes($plan)]);
    }

    public function storeSingle(Request $request, string $locale, FalAiSettingsService $settings, AiTranslationSettingsService $agentSettings, FalAiJobService $jobs, LenticularAccessService $access): RedirectResponse
    {
        $plan = $this->authorizeAgentPlan($request, $access);
        abort_unless($settings->configured() && $agentSettings->configured('openai'), 503, 'AI service is temporarily unavailable.');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'source_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'print_size' => ['required', Rule::in($access->agentPrintSizes($plan))],
            'lpi' => ['required', Rule::in([50, 60, 75])],
            'confirm_ai_cost' => ['accepted'],
        ]);
        $this->ensureBudgetAvailable($settings);

        $project = LenticularProject::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'settings' => ['workflow' => 'ai_single', 'print_size' => $validated['print_size'], 'dpi' => 1440, 'lpi' => (int) $validated['lpi'], 'max_frames' => 25],
        ]);
        $source = $this->storeImage($project, $request->file('source_image'), 'ai_start_image');
        $job = $jobs->create(
            $project,
            FalAiJobOperation::ImageToVideo,
            $settings->seedanceModel(),
            ['resolution' => $settings->resolution(), 'duration' => (string) $settings->duration(), 'aspect_ratio' => 'auto', 'generate_audio' => false, 'bitrate_mode' => 'high'],
            (string) Str::uuid(),
            $source,
            estimatedCostUsd: $settings->maximumJobCost(),
        );
        PrepareSinglePhotoLenticularJob::dispatch($job->id);

        return redirect()->route('lab.lenticular.ai.jobs.show', ['locale' => $locale, 'job' => $job]);
    }

    public function showJob(Request $request, string $locale, FalAiJob $job): View
    {
        abort_unless($job->user_id === $request->user()->id, 404);
        $job->load(['lenticularProject', 'resultFile']);

        return view('lab.projects.ai-job', compact('job'));
    }

    public function createSequence(Request $request, string $locale, LenticularAccessService $access): View
    {
        $plan = $access->plan($request->user());

        return view('lab.projects.sequence', ['premium' => $plan === LenticularAccessService::PREMIUM, 'planLimit' => $access->sequenceLimit($plan), 'printSizes' => $access->sequencePrintSizes($plan)]);
    }

    public function storeSequence(Request $request, string $locale, LenticularSequenceService $sequences, LenticularAccessService $access): RedirectResponse
    {
        $plan = $access->plan($request->user());
        $planLimit = $access->sequenceLimit($plan);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'images' => ['required', 'array', 'between:2,'.$planLimit],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'print_size' => ['required', Rule::in($access->sequencePrintSizes($plan))],
            'printer_dpi' => ['required', 'integer', 'between:300,2400'],
            'lpi' => ['required', Rule::in([50, 60, 75])],
            'reverse' => ['nullable', 'boolean'],
        ]);
        $uploads = $validated['images'];
        abort_if(array_sum(array_map(fn (UploadedFile $file): int => $file->getSize(), $uploads)) > 104_857_600, 422, 'The complete sequence may not exceed 100 MB.');
        if ($request->boolean('reverse')) {
            $uploads = array_reverse($uploads);
        }
        $technicalLimit = intdiv((int) $validated['printer_dpi'], (int) $validated['lpi']);
        abort_if(count($uploads) > $technicalLimit, 422, "These print settings support up to {$technicalLimit} frames.");

        $project = LenticularProject::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'settings' => ['workflow' => 'photo_sequence', 'print_size' => $validated['print_size'], 'print_service' => false, 'dpi' => (int) $validated['printer_dpi'], 'lpi' => (int) $validated['lpi'], 'max_frames' => min($planLimit, $technicalLimit)],
        ]);
        try {
            $sequences->store($project, $uploads);
        } catch (\RuntimeException $exception) {
            $project->delete();
            throw ValidationException::withMessages(['images' => $exception->getMessage()]);
        }

        return redirect()->route('lab.projects.show', ['locale' => $locale, 'project' => $project]);
    }

    private function storeImage(LenticularProject $project, UploadedFile $upload, string $kind): LenticularProjectFile
    {
        $path = $upload->store("lenticular/sources/{$project->id}", config('lenticular_machine.disk'));
        $absolute = Storage::disk(config('lenticular_machine.disk'))->path($path);

        return LenticularProjectFile::query()->create([
            'lenticular_project_id' => $project->id, 'kind' => $kind,
            'disk' => config('lenticular_machine.disk'), 'path' => $path,
            'original_name' => $upload->getClientOriginalName(), 'media_type' => $upload->getMimeType(),
            'size_bytes' => $upload->getSize(), 'sha256' => hash_file('sha256', $absolute),
        ]);
    }

    private function authorizeAgentPlan(Request $request, LenticularAccessService $access): string
    {
        $plan = $access->plan($request->user());
        abort_unless($access->canUseAgent($plan), 403);

        return $plan;
    }

    private function ensureBudgetAvailable(FalAiSettingsService $settings): void
    {
        $reservedToday = (float) FalAiJob::query()->whereDate('created_at', today())
            ->whereNotIn('status', ['failed', 'cancelled'])
            ->sum(DB::raw('COALESCE(actual_cost_usd, estimated_cost_usd, 0)'));
        abort_if($reservedToday + $settings->maximumJobCost() > $settings->dailyBudget(), 429, 'Daily AI budget has been reached.');
    }
}
