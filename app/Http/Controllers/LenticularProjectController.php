<?php

namespace App\Http\Controllers;

use App\Enums\LenticularJobStatus;
use App\Http\Requests\StoreLenticularProjectRequest;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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
            'settings' => ['print_size' => $validated['print_size'], 'print_service' => $request->boolean('print_service'), 'dpi' => $dpi, 'lpi' => (int) $validated['lpi'], 'max_frames' => intdiv($dpi, (int) $validated['lpi'])],
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
        $selection = collect($validated)->only(['start', 'end', 'step', 'jpeg_quality'])->all();
        abort_if(((int) floor(($selection['end'] - $selection['start']) / $selection['step'])) + 1 > (int) ($project->settings['max_frames'] ?? 1), 422, 'Selected range contains too many frames.');
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'extract_video_frames', 'status' => LenticularJobStatus::Queued, 'parameters' => ['selection' => $selection]]);
        $project->update(['settings' => array_merge($project->settings ?? [], ['selection' => $selection])]);

        return back()->with('status', 'Klatki zostały dodane do kolejki pobierania.');
    }

    public function alignFrames(Request $request, string $locale, LenticularProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $source = $project->files()->where('kind', 'source_video')->firstOrFail();
        $extraction = $project->jobs()->where('operation', 'extract_video_frames')->where('status', LenticularJobStatus::Completed)->latest()->first();
        $selection = $project->settings['selection'] ?? $extraction?->parameters['selection'] ?? null;
        abort_unless(is_array($selection) && $extraction !== null, 409, 'Frames must be extracted first.');
        $alignment = $request->validate(['z_center' => ['required', 'numeric', 'between:0,1'], 'z_width' => ['required', 'numeric', 'between:0.01,0.5'], 'alignment_y' => ['required', 'numeric', 'between:0,1']]);
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'align_sequence', 'status' => LenticularJobStatus::Queued, 'parameters' => ['selection' => $selection, 'alignment' => $alignment]]);

        return back()->with('status', 'Automatyczne wyrównanie zostało uruchomione.');
    }

    private function authorizeOwner(Request $request, LenticularProject $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 404);
    }
}
