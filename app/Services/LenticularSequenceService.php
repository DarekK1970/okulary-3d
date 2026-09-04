<?php

namespace App\Services;

use App\Enums\LenticularJobStatus;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PharData;
use RuntimeException;

class LenticularSequenceService
{
    /** @param list<UploadedFile> $uploads */
    public function store(LenticularProject $project, array $uploads): LenticularProjectFile
    {
        $disk = (string) config('lenticular_machine.disk', 'local');
        $directory = "lenticular/sources/{$project->id}";
        $path = "{$directory}/sequence.tar";
        Storage::disk($disk)->makeDirectory($directory);
        $absolute = Storage::disk($disk)->path($path);
        if (is_file($absolute)) {
            unlink($absolute);
        }

        $archive = new PharData($absolute);
        $dimensions = null;
        foreach ($uploads as $index => $upload) {
            $size = getimagesize($upload->getRealPath());
            if ($size === false) {
                throw new RuntimeException('One of the sequence images cannot be decoded.');
            }
            $current = [(int) $size[0], (int) $size[1]];
            $dimensions ??= $current;
            if ($current !== $dimensions) {
                throw new RuntimeException('All sequence images must have equal dimensions.');
            }
            $extension = strtolower($upload->getClientOriginalExtension()) === 'jpeg' ? 'jpg' : strtolower($upload->getClientOriginalExtension());
            $archive->addFile($upload->getRealPath(), sprintf('frame_%04d.%s', $index + 1, $extension));
        }

        $file = LenticularProjectFile::query()->create([
            'lenticular_project_id' => $project->id,
            'kind' => 'source_sequence',
            'disk' => $disk,
            'path' => $path,
            'original_name' => 'sequence.tar',
            'media_type' => 'application/x-tar',
            'size_bytes' => filesize($absolute),
            'sha256' => hash_file('sha256', $absolute),
            'metadata' => ['width' => $dimensions[0], 'height' => $dimensions[1], 'frame_count' => count($uploads), 'fps' => 1, 'duration_seconds' => count($uploads)],
        ]);

        $selection = ['start' => 0, 'end' => count($uploads) - 1, 'step' => 1, 'jpeg_quality' => 95];
        LenticularJob::query()->create([
            'lenticular_project_id' => $project->id,
            'source_file_id' => $file->id,
            'operation' => 'import_sequence',
            'status' => LenticularJobStatus::Completed,
            'parameters' => ['selection' => $selection],
            'progress' => 100,
            'stage' => 'completed',
            'completed_at' => now(),
        ]);
        $project->update(['settings' => [...$project->settings, 'selection' => $selection]]);
        $this->storePreviews($project, $uploads, $disk);

        return $file;
    }

    /** @param list<UploadedFile> $uploads */
    private function storePreviews(LenticularProject $project, array $uploads, string $disk): void
    {
        $indexes = array_values(array_unique([0, intdiv(count($uploads) - 1, 2), count($uploads) - 1]));
        foreach ($indexes as $sequence => $index) {
            $contents = file_get_contents($uploads[$index]->getRealPath());
            $extension = strtolower($uploads[$index]->getClientOriginalExtension()) === 'jpeg' ? 'jpg' : strtolower($uploads[$index]->getClientOriginalExtension());
            $path = "lenticular/previews/{$project->id}/sequence_{$sequence}.{$extension}";
            Storage::disk($disk)->put($path, $contents);
            LenticularProjectFile::query()->create([
                'lenticular_project_id' => $project->id,
                'kind' => "sequence_preview_{$sequence}",
                'disk' => $disk,
                'path' => $path,
                'original_name' => basename($path),
                'media_type' => $uploads[$index]->getMimeType(),
                'size_bytes' => strlen($contents),
                'sha256' => hash('sha256', $contents),
                'metadata' => ['sequence_index' => $index],
            ]);
        }
    }
}
