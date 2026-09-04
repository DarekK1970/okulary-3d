<?php

namespace App\Services;

use App\Enums\FalAiJobOperation;
use App\Models\FalAiJob;
use App\Models\LenticularProjectFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FalAiResultService
{
    private const MAX_BYTES = 157_286_400;

    public function store(FalAiJob $job, array $payload): LenticularProjectFile
    {
        $url = (string) (data_get($payload, 'video.url') ?? data_get($payload, 'url'));
        if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('fal.ai response does not contain a valid HTTPS result URL.');
        }

        $disk = (string) config('lenticular_machine.disk', 'local');
        $extension = $this->extension($url, (string) data_get($payload, 'video.content_type'));
        $path = "lenticular/{$job->lenticular_project_id}/fal-ai/{$job->id}/result.{$extension}";
        $absolutePath = Storage::disk($disk)->path($path);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $response = Http::timeout(180)->sink($absolutePath)->get($url);
        if (! $response->successful() || ! is_file($absolutePath)) {
            @unlink($absolutePath);
            throw new RuntimeException('Could not download the fal.ai result.');
        }

        $size = filesize($absolutePath);
        if ($size === false || $size < 1 || $size > self::MAX_BYTES) {
            @unlink($absolutePath);
            throw new RuntimeException('The fal.ai result has an invalid size.');
        }

        return LenticularProjectFile::query()->create([
            'lenticular_project_id' => $job->lenticular_project_id,
            'kind' => $job->operation === FalAiJobOperation::ImageToVideo ? 'source_video' : 'fal_ai_result',
            'disk' => $disk,
            'path' => $path,
            'original_name' => "fal-ai-{$job->id}.{$extension}",
            'media_type' => $response->header('Content-Type') ?: data_get($payload, 'video.content_type', 'video/mp4'),
            'size_bytes' => $size,
            'sha256' => hash_file('sha256', $absolutePath),
            'metadata' => ['provider' => 'fal.ai', 'source_url_host' => parse_url($url, PHP_URL_HOST)],
        ]);
    }

    private function extension(string $url, string $contentType): string
    {
        if (str_contains(strtolower($contentType), 'webm')) {
            return 'webm';
        }

        return strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) === 'webm' ? 'webm' : 'mp4';
    }
}
