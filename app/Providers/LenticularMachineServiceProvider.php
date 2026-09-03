<?php

namespace App\Providers;

use App\Http\Controllers\Api\Worker\V1\JobController;
use App\Http\Controllers\Api\Worker\V1\TransferController;
use App\Http\Middleware\VerifyProcessingMachineSignature;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LenticularMachineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('lenticular_machine.php'), 'lenticular_machine');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('processing-machines', fn (Request $request): Limit => Limit::perMinute(180)->by((string) $request->header('X-Machine-Id', $request->ip())));

        Route::middleware(SubstituteBindings::class)->prefix('api/worker/v1')->name('worker.')->group(function (): void {
            Route::middleware([VerifyProcessingMachineSignature::class, 'throttle:processing-machines'])->group(function (): void {
                Route::post('/jobs/claim', [JobController::class, 'claim'])->name('jobs.claim');
                Route::post('/jobs/{job}/heartbeat', [JobController::class, 'heartbeat'])->name('jobs.heartbeat');
                Route::post('/jobs/{job}/progress', [JobController::class, 'progress'])->name('jobs.progress');
                Route::post('/jobs/{job}/complete', [JobController::class, 'complete'])->name('jobs.complete');
                Route::post('/jobs/{job}/fail', [JobController::class, 'fail'])->name('jobs.fail');
            });
            Route::get('/transfers/{job}/source', [TransferController::class, 'source'])->name('transfers.source');
            Route::put('/transfers/{job}/result', [TransferController::class, 'result'])->name('transfers.result');
        });
    }
}
