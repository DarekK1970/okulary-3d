<?php

namespace App\Http\Controllers;

use App\Enums\LenticularJobStatus;
use App\Http\Requests\StoreLenticularProjectRequest;
use App\Models\LenticularArtifact;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Services\LenticularProjectArchiveService;
use App\Services\LenticularSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LenticularProjectController extends Controller
{
    public function create(string $locale): View
    {
        return view('lab.projects.create');
    }

    public function store(StoreLenticularProjectRequest $request, string $locale): RedirectResponse
    {
        $validated = $request->validated();
        $dpi = $request->boolean('print_service') ? 1440 : (int) $validated['printer_dpi'];
        $project = LenticularProject::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'settings' => ['workflow' => 'flip', 'print_size' => $validated['print_size'], 'print_service' => $request->boolean('print_service'), 'dpi' => $dpi, 'lpi' => (int) $validated['lpi'], 'max_frames' => 6, 'lens_orientation' => 'horizontal'],
        ]);

        return redirect()->route('lab.projects.show', ['locale' => $locale, 'project' => $project]);
    }

    public function uploadVideo(Request $request, string $locale, LenticularProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        abort_if($project->files()->where('kind', 'source_video')->exists(), 409, 'Video has already been uploaded.');
        $request->validate(['video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:102400']]);
        $upload = $request->file('video');
        $path = $upload->store("lenticular/sources/{$project->id}", config('lenticular_machine.disk'));
        $absolute = Storage::disk(config('lenticular_machine.disk'))->path($path);
        $file = LenticularProjectFile::query()->create(['lenticular_project_id' => $project->id, 'kind' => 'source_video', 'disk' => config('lenticular_machine.disk'), 'path' => $path, 'original_name' => $upload->getClientOriginalName(), 'media_type' => $upload->getMimeType(), 'size_bytes' => $upload->getSize(), 'sha256' => hash_file('sha256', $absolute)]);
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $file->id, 'operation' => 'analyze_video', 'status' => LenticularJobStatus::Queued, 'parameters' => []]);

        return redirect()->route('lab.projects.show', ['locale' => $locale, 'project' => $project]);
    }

    public function uploadImages(Request $request, string $locale, LenticularProject $project, LenticularSequenceService $sequences): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        abort_if($project->files()->whereIn('kind', ['source_video', 'source_sequence'])->exists(), 409, 'Source has already been uploaded.');
        $validated = $request->validate([
            'images' => ['required', 'array', 'between:2,6'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
        ]);
        abort_if(array_sum(array_map(fn ($file): int => $file->getSize(), $validated['images'])) > 104_857_600, 422, 'The complete sequence may not exceed 100 MB.');
        try {
            $sequences->store($project, $validated['images']);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['images' => $exception->getMessage()]);
        }

        return redirect()->route('lab.projects.show', ['locale' => $locale, 'project' => $project]);
    }

    public function show(Request $request, string $locale, LenticularProject $project): View
    {
        $this->authorizeOwner($request, $project);
        $project->load(['files', 'jobs']);

        return view('lab.projects.show', compact('project'));
    }

    public function selectFrames(Request $request, string $locale, LenticularProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $source = $project->files()->where('kind', 'source_video')->firstOrFail();
        $last = max(0, ((int) ($source->metadata['frame_count'] ?? 1)) - 1);
        $validated = $request->validate(['start' => ['required', 'integer', 'min:0', "max:{$last}"], 'end' => ['required', 'integer', 'gte:start', "max:{$last}"], 'step' => ['required', 'integer', 'between:1,10'], 'jpeg_quality' => ['required', 'integer', 'between:1,100']]);
        $selection = collect($validated)->only(['start', 'end', 'step', 'jpeg_quality'])->map(fn ($value): int => (int) $value)->all();
        abort_if(((int) floor(($selection['end'] - $selection['start']) / $selection['step'])) + 1 > (int) ($project->settings['max_frames'] ?? 1), 422, 'Selected range contains too many frames.');
        abort_if(((int) floor(($selection['end'] - $selection['start']) / $selection['step'])) + 1 < 2, 422, 'Select at least two frames.');
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'extract_video_frames', 'status' => LenticularJobStatus::Queued, 'parameters' => ['selection' => $selection]]);
        $project->update(['settings' => array_merge($project->settings ?? [], ['selection' => $selection])]);

        return back()->with('status', 'Klatki zostały dodane do kolejki pobierania.');
    }

    public function alignFrames(Request $request, string $locale, LenticularProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $source = $this->source($project);
        $extraction = $project->jobs()->whereIn('operation', ['extract_video_frames', 'import_sequence'])->where('status', LenticularJobStatus::Completed)->latest()->first();
        $selection = $project->settings['selection'] ?? $extraction?->parameters['selection'] ?? null;
        abort_unless(is_array($selection) && $extraction !== null, 409, 'Frames must be extracted first.');
        $alignment = collect($request->validate(['z_center' => ['required', 'numeric', 'between:0,1'], 'z_width' => ['required', 'numeric', 'between:0.01,0.5'], 'alignment_y' => ['required', 'numeric', 'between:0,1']]))->map(fn ($value): float => (float) $value)->all();
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'align_sequence', 'status' => LenticularJobStatus::Queued, 'parameters' => ['selection' => $selection, 'alignment' => $alignment]]);

        return back()->with('status', 'Automatyczne wyrównanie zostało uruchomione.');
    }

    public function finalize(Request $request, string $locale, LenticularProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $source = $this->source($project);
        $alignmentJob = $project->jobs()->where('operation', 'align_sequence')->where('status', LenticularJobStatus::Completed)->latest()->first();
        $selection = $project->settings['selection'] ?? null;
        abort_unless($alignmentJob && is_array($selection), 409, 'Alignment must be completed first.');
        $validated = $request->validate(['crop_x' => ['required', 'numeric', 'between:0,1'], 'crop_y' => ['required', 'numeric', 'between:0,1'], 'crop_width' => ['required', 'numeric', 'between:0.01,1'], 'crop_height' => ['required', 'numeric', 'between:0.01,1'], 'reverse' => ['nullable', 'boolean']]);
        $crop = ['x' => (float) $validated['crop_x'], 'y' => (float) $validated['crop_y'], 'width' => (float) $validated['crop_width'], 'height' => (float) $validated['crop_height']];
        abort_if($crop['x'] + $crop['width'] > 1.00001 || $crop['y'] + $crop['height'] > 1.00001, 422, 'Crop exceeds image bounds.');
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'finalize_sequence', 'status' => LenticularJobStatus::Queued, 'parameters' => ['selection' => $selection, 'alignment' => $alignmentJob->parameters['alignment'], 'finalization' => ['crop' => $crop, 'reverse' => $request->boolean('reverse'), 'basename' => $project->name, 'lens_orientation' => 'horizontal']]]);

        return back()->with('status', 'Kadrowanie i zapis sekwencji zostały uruchomione.');
    }

    public function download(Request $request, string $locale, LenticularProject $project): BinaryFileResponse
    {
        $this->authorizeOwner($request, $project);
        $job = $project->jobs()->where('operation', 'finalize_sequence')->where('status', LenticularJobStatus::Completed)->latest()->firstOrFail();
        $artifact = LenticularArtifact::query()->where('lenticular_job_id', $job->id)->where('kind', 'final')->firstOrFail();

        return response()->download(Storage::disk($artifact->disk)->path($artifact->path), $project->name.'_JPG.zip', ['Content-Type' => 'application/zip']);
    }

    public function files(Request $request, string $locale, LenticularProject $project): View
    {
        $this->authorizeOwner($request, $project);
        $project->load(['files', 'jobs.artifacts']);

        return view('account.project-files', compact('project'));
    }

    public function file(Request $request, string $locale, LenticularProject $project, LenticularProjectFile $file): BinaryFileResponse
    {
        $this->authorizeOwner($request, $project);
        abort_unless($file->lenticular_project_id === $project->id, 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $path = Storage::disk($file->disk)->path($file->path);

        return $request->boolean('download')
            ? response()->download($path, $file->original_name)
            : response()->file($path, ['Content-Type' => $file->media_type ?: 'application/octet-stream']);
    }

    public function archive(Request $request, string $locale, LenticularProject $project, LenticularProjectArchiveService $archives): BinaryFileResponse
    {
        $this->authorizeOwner($request, $project);

        try {
            $archive = $archives->create($project);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response()->download($archive['path'], $archive['name'], ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function artifact(Request $request, string $locale, LenticularProject $project, LenticularArtifact $artifact): BinaryFileResponse
    {
        $this->authorizeOwner($request, $project);
        abort_unless($artifact->lenticularJob?->lenticular_project_id === $project->id, 404);
        abort_unless(Storage::disk($artifact->disk)->exists($artifact->path), 404);

        return response()->download(Storage::disk($artifact->disk)->path($artifact->path));
    }

    public function destroy(Request $request, string $locale, LenticularProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $project->load(['files', 'jobs.artifacts']);

        $storedFiles = $project->files
            ->map(fn (LenticularProjectFile $file): array => [$file->disk, $file->path])
            ->concat($project->jobs->flatMap(
                fn (LenticularJob $job) => $job->artifacts->map(
                    fn (LenticularArtifact $artifact): array => [$artifact->disk, $artifact->path]
                )
            ))
            ->unique(fn (array $file): string => implode(':', $file));

        $project->delete();

        $storedFiles->each(fn (array $file) => Storage::disk($file[0])->delete($file[1]));

        return redirect()->route('account', ['locale' => $locale])
            ->with('status', __('portal_auth.projects.deleted'));
    }

    private function authorizeOwner(Request $request, LenticularProject $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 404);
    }

    private function source(LenticularProject $project): LenticularProjectFile
    {
        return $project->files()->whereIn('kind', ['source_video', 'source_sequence'])->firstOrFail();
    }
}
