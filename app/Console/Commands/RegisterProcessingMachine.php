<?php

namespace App\Console\Commands;

use App\Models\ProcessingMachine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('lenticular:machine-register {machine_id} {--key-id=}')]
#[Description('Register or rotate credentials for a lenticular processing machine')]
class RegisterProcessingMachine extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $machineId = trim((string) $this->argument('machine_id'));
        if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $machineId)) {
            $this->error('Machine ID may contain only letters, numbers, underscores and hyphens.');

            return self::FAILURE;
        }

        $keyId = trim((string) ($this->option('key-id') ?: Str::uuid()));
        $secret = Str::random(64);
        ProcessingMachine::query()->updateOrCreate(
            ['machine_id' => $machineId],
            ['api_key_id' => $keyId, 'api_secret' => $secret, 'capabilities' => ['analyze_video:v1', 'extract_video_frames:v1', 'align_sequence:v1'], 'is_active' => true]
        );

        $this->info('Processing machine credentials:');
        $this->line("LENTICULAR_MACHINE_ID={$machineId}");
        $this->line("LENTICULAR_API_KEY_ID={$keyId}");
        $this->line("LENTICULAR_API_SECRET={$secret}");
        $this->warn('The secret is displayed once. Store it in the worker .env file now.');

        return self::SUCCESS;
    }
}
