<?php

namespace App\Services;

use App\Models\LenticularProjectFile;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LenticularPromptService
{
    public function __construct(private readonly AiTranslationSettingsService $settings) {}

    /** @return array{prompt: string, analysis: array<string, string>, usage: array<string, int|null>} */
    public function build(LenticularProjectFile $image): array
    {
        if (! $this->settings->configured('openai')) {
            throw new RuntimeException('The scene analysis service is not configured.');
        }

        $bytes = Storage::disk($image->disk)->get($image->path);
        $dataUrl = 'data:'.$image->media_type.';base64,'.base64_encode($bytes);
        $response = Http::withToken((string) $this->settings->apiKey('openai'))
            ->acceptJson()->timeout($this->settings->timeout())
            ->post('https://api.openai.com/v1/responses', [
                'model' => $this->settings->model('openai'),
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $this->analysisInstructions()],
                        ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'high'],
                    ],
                ]],
                'text' => ['format' => ['type' => 'json_schema', 'name' => 'lenticular_scene_analysis', 'strict' => true, 'schema' => $this->schema()]],
            ]);

        $this->ensureSuccess($response);
        $json = $response->json();
        $analysis = json_decode($this->extractText($json), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($analysis)) {
            throw new RuntimeException('The scene analysis has an invalid format.');
        }

        return [
            'prompt' => $this->composePrompt($analysis),
            'analysis' => $analysis,
            'usage' => [
                'input_tokens' => data_get($json, 'usage.input_tokens'),
                'output_tokens' => data_get($json, 'usage.output_tokens'),
                'total_tokens' => data_get($json, 'usage.total_tokens'),
            ],
        ];
    }

    private function analysisInstructions(): string
    {
        return <<<'PROMPT'
Analyze this photograph for a lenticular 3D camera-orbit animation. Describe only visible facts. Select one stable, visually prominent subject or object near the center as the camera-orbit pivot. Inventory people, vehicles, architecture, vegetation, wires, text, markings and fine geometry that must remain rigid. Note occlusion boundaries that may need conservative reconstruction. Do not invent names, history or unseen details. Return the requested JSON only.
PROMPT;
    }

    /** @param array<string, string> $analysis */
    private function composePrompt(array $analysis): string
    {
        $scene = trim($analysis['scene_description']);
        $pivot = trim($analysis['pivot_description']);
        $details = trim($analysis['preservation_details']);

        return <<<PROMPT
Use the uploaded image as the exact first frame and as the strict visual reference for the entire shot.

Create a single continuous photorealistic shot intended specifically for extracting sequential frames for a lenticular 3D photograph.

VISIBLE SCENE: {$scene}
FIXED CAMERA PIVOT: {$pivot}
DETAILS THAT MUST BE PRESERVED: {$details}

The entire scene is completely frozen in time. Nothing in the scene moves. Every person remains perfectly motionless in exactly the same body position, pose, facial expression and location. No walking, blinking, breathing, head movement, hand movement, clothing movement or posture changes. All vehicles, buildings, structures, vegetation, sky, shadows, reflections and background objects remain completely static. No wind, moving leaves, moving clouds or changing light. Only the camera moves.

CAMERA MOVEMENT:
Perform one extremely slow, mechanically smooth horizontal orbital camera move to the right, following a shallow circular arc around the fixed camera pivot described above. The pivot remains the visual center of the entire move. Use approximately a 10–12 degree clockwise orbit around the pivot when viewed from above over the full duration. The camera physically translates sideways along the circular arc to produce natural horizontal parallax. This must not be a pan or a digital image slide.

Maintain the same distance from the pivot and exactly the same camera height. Keep the horizon perfectly level and keep the pivot at almost exactly the same screen position while foreground and background layers shift naturally through parallax. Use extremely slow, uniform movement with constant angular velocity. No acceleration, deceleration, shake, handheld motion, vertical movement, crane movement, dolly-in, dolly-out, push-in, pull-back, zoom, focal-length change, lens breathing, roll, rack focus, cuts or transitions.

Preserve the apparent focal length, perspective, exposure, depth of field and framing style of the original photograph.

GEOMETRIC CONSISTENCY IS CRITICAL:
All solid objects must remain rigid and dimensionally stable from frame to frame. Preserve exact dimensions, structure, faces, clothing, text, markings and mechanical or architectural details. No morphing, warping, stretching, changing proportions, duplicated or disappearing objects, newly generated people or vehicles, texture crawling, flicker or geometry popping at occlusion boundaries. When a slightly hidden surface becomes visible, reconstruct it conservatively and consistently with visible geometry.

LIGHTING:
Preserve the exact lighting of the reference frame, including sunlight direction, brightness, color temperature, sky and shadows throughout the shot.

IMAGE QUALITY:
Maximum photorealism and temporal consistency. Every frame must look like a sharp still photograph suitable for printing. Minimize motion blur and preserve fine details.

Duration: 4 seconds. Single continuous take. Only the camera moves. Everything else remains perfectly frozen in time.
PROMPT;
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        $field = ['type' => 'string', 'minLength' => 1];

        return ['type' => 'object', 'properties' => ['scene_description' => $field, 'pivot_description' => $field, 'preservation_details' => $field], 'required' => ['scene_description', 'pivot_description', 'preservation_details'], 'additionalProperties' => false];
    }

    private function ensureSuccess(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException('Scene analysis failed (HTTP '.$response->status().').');
        }
    }

    /** @param array<string, mixed> $json */
    private function extractText(array $json): string
    {
        foreach ((array) ($json['output'] ?? []) as $item) {
            foreach ((array) ($item['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text' && filled($content['text'] ?? null)) {
                    return (string) $content['text'];
                }
            }
        }

        throw new RuntimeException('Scene analysis returned no result.');
    }
}
