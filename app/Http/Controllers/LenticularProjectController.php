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
        $project = LenticularProject::query()->create(['user_id' => $request->user()->id, 'name' => $request->validated('name')]);
        $upload = $request->file('video');
        $path = $upload->store("lenticular/sources/{$project->id}", config('lenticular_machine.disk'));
        $absolute = Storage::disk(config('lenticular_machine.disk'))->path($path);
        $file = LenticularProjectFile::query()->create(['lenticular_project_id' => $project->id, 'kind' => 'source_video', 'disk' => config('lenticular_machine.disk'), 'path' => $path, 'original_name' => $upload->getClientOriginalName(), 'media_type' => $upload->getMimeType(), 'size_bytes' => $upload->getSize(), 'sha256' => hash_file('sha256', $absolute)]);
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $file->id, 'operation' => 'analyze_video', 'status' => LenticularJobStatus::Queued, 'parameters' => []]);

        return redirect()->route('lab.projects.show', ['locale' => $locale, 'project' => $project]);
    }

    public function show(Request $request, string $locale, LenticularProject $project): View
    {
        abort_unless($project->user_id === $request->user()->id, 404);
        $project->load(['files', 'jobs']);

        return view('lab.projects.show', compact('project'));
    }

    public function selectFrames(Request $request, string $locale, LenticularProject $project): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 404);
        $source = $project->files()->where('kind', 'source_video')->firstOrFail();
        $last = max(0, ((int) ($source->metadata['frame_count'] ?? 1)) - 1);
        $validated = $request->validate(['start' => ['required', 'integer', 'min:0', "max:{$last}"], 'end' => ['required', 'integer', 'gte:start', "max:{$last}"], 'step' => ['required', 'integer', 'min:1'], 'jpeg_quality' => ['required', 'integer', 'between:1,100'], 'z_center' => ['required', 'numeric', 'between:0,1'], 'z_width' => ['required', 'numeric', 'between:0.01,0.5'], 'alignment_y' => ['required', 'numeric', 'between:0,1']]);
        $selection = collect($validated)->only(['start', 'end', 'step', 'jpeg_quality'])->all();
        $alignment = collect($validated)->only(['z_center', 'z_width', 'alignment_y'])->all();
        LenticularJob::query()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'align_sequence', 'status' => LenticularJobStatus::Queued, 'parameters' => ['selection' => $selection, 'alignment' => $alignment]]);

        return back()->with('status', 'Sekwencja została dodana do kolejki.');
    }
}
