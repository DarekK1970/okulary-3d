<?php

namespace App\Services;

use App\Models\LenticularProject;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PharData;
use RuntimeException;

class LenticularProjectArchiveService
{
    /** @return array{path: string, name: string} */
    public function create(LenticularProject $project): array
    {
        $project->loadMissing(['files', 'jobs.artifacts']);
        $directory = storage_path('app/private/project-archives');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/'.Str::uuid().'.zip';
        $archive = new PharData($path);
        $entries = [];

        foreach ($project->files as $file) {
            if (! Storage::disk($file->disk)->exists($file->path)) {
                continue;
            }

            $name = $this->entryName('project-files', $file->kind, $file->original_name, $entries);
            $archive->addFile(Storage::disk($file->disk)->path($file->path), $name);
            $entries[] = $name;
        }

        foreach ($project->jobs->flatMap->artifacts as $artifact) {
            if (! Storage::disk($artifact->disk)->exists($artifact->path)) {
                continue;
            }

            $name = $this->entryName('artifacts', $artifact->kind, basename($artifact->path), $entries);
            $archive->addFile(Storage::disk($artifact->disk)->path($artifact->path), $name);
            $entries[] = $name;
        }

        unset($archive);

        if ($entries === []) {
            File::delete($path);
            throw new RuntimeException('Project does not contain downloadable files.');
        }

        $slug = Str::slug($project->name) ?: 'project';

        return ['path' => $path, 'name' => $slug.'-files.zip'];
    }

    /** @param list<string> $entries */
    private function entryName(string $group, string $kind, string $filename, array $entries): string
    {
        $safeKind = Str::slug($kind) ?: 'file';
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/u', '-', basename($filename)) ?: 'file';
        $candidate = "{$group}/{$safeKind}/{$safeFilename}";
        $extension = pathinfo($safeFilename, PATHINFO_EXTENSION);
        $stem = pathinfo($safeFilename, PATHINFO_FILENAME);
        $suffix = 2;

        while (in_array($candidate, $entries, true)) {
            $numbered = $stem.'-'.$suffix++.($extension !== '' ? '.'.$extension : '');
            $candidate = "{$group}/{$safeKind}/{$numbered}";
        }

        return $candidate;
    }
}
