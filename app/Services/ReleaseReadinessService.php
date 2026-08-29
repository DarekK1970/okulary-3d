<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ReleaseReadinessService
{
    /**
     * @return array<string, array{
     *     ok: bool,
     *     required: bool,
     *     message: string
     * }>
     */
    public function runtimeChecks(): array
    {
        return [
            'database' => $this->check(
                required: true,
                callback: function (): string {
                    DB::connection()->getPdo();

                    return 'database connection OK';
                }
            ),
            'cache' => $this->check(
                required: true,
                callback: function (): string {
                    $key = 'release-health:'
                        . Str::uuid();
                    $value = Str::random(20);

                    Cache::put(
                        $key,
                        $value,
                        15
                    );

                    if (
                        Cache::get($key)
                        !== $value
                    ) {
                        throw new \RuntimeException(
                            'cache read/write mismatch'
                        );
                    }

                    Cache::forget($key);

                    return 'cache read/write OK';
                }
            ),
            'storage' => $this->check(
                required: true,
                callback: function (): string {
                    $directory = storage_path(
                        'framework/cache'
                    );

                    if (! is_dir($directory)) {
                        File::makeDirectory(
                            $directory,
                            0755,
                            true
                        );
                    }

                    $path = $directory
                        . DIRECTORY_SEPARATOR
                        . 'release-health-'
                        . Str::uuid()
                        . '.tmp';

                    if (
                        File::put(
                            $path,
                            'ok'
                        ) === false
                    ) {
                        throw new \RuntimeException(
                            'storage write failed'
                        );
                    }

                    File::delete($path);

                    return 'storage write OK';
                }
            ),
        ];
    }

    /**
     * @return array<string, array{
     *     ok: bool,
     *     required: bool,
     *     message: string
     * }>
     */
    public function fullChecks(
        bool $production = false
    ): array {
        $checks = $this->runtimeChecks();

        $checks = [
            'php' => $this->valueCheck(
                version_compare(
                    PHP_VERSION,
                    (string) config(
                        'release.minimum_php',
                        '8.3.0'
                    ),
                    '>='
                ),
                true,
                'PHP ' . PHP_VERSION
            ),
            'app_key' => $this->valueCheck(
                filled(config('app.key')),
                true,
                filled(config('app.key'))
                    ? 'APP_KEY configured'
                    : 'APP_KEY missing'
            ),
            ...$checks,
        ];

        $checks['migrations'] = $this->check(
            required: true,
            callback: function (): string {
                if (
                    ! Schema::hasTable(
                        'migrations'
                    )
                ) {
                    throw new \RuntimeException(
                        'migrations table missing'
                    );
                }

                $migrationFiles = collect(
                    File::files(
                        database_path('migrations')
                    )
                )
                    ->map(
                        static fn ($file): string =>
                            pathinfo(
                                $file->getFilename(),
                                PATHINFO_FILENAME
                            )
                    );

                $ran = DB::table(
                    'migrations'
                )
                    ->pluck('migration');

                $pending = $migrationFiles
                    ->diff($ran)
                    ->values();

                if ($pending->isNotEmpty()) {
                    throw new \RuntimeException(
                        $pending->count()
                        . ' pending migration(s)'
                    );
                }

                return 'no pending migrations';
            }
        );

        $checks['route_cache'] = $this->check(
            required: true,
            callback: function (): string {
                $routes =
                    Route::getRoutes();

                $validated = 0;

                foreach ($routes as $route) {
                    /*
                     * Laravel 13 can serialize supported route closures.
                     * Therefore, counting Closure actions is not a valid
                     * route-cache compatibility test.
                     *
                     * prepareForSerialization() is the framework-level
                     * operation used while preparing cached routes.
                     *
                     * Work on a clone so the release check never mutates
                     * the live route collection used by the current process.
                     */
                    $routeCopy =
                        clone $route;

                    $routeCopy
                        ->prepareForSerialization();

                    $validated++;
                }

                return $validated
                    . ' route(s) prepared for serialization';
            }
        );

        $checks['storage_link'] = $this->valueCheck(
            file_exists(
                public_path('storage')
            ),
            $production,
            file_exists(
                public_path('storage')
            )
                ? 'public/storage available'
                : 'public/storage missing'
        );

        $checks['vite_build'] = $this->valueCheck(
            file_exists(
                public_path(
                    'build/manifest.json'
                )
            ),
            $production,
            file_exists(
                public_path(
                    'build/manifest.json'
                )
            )
                ? 'Vite production manifest available'
                : 'Vite production manifest missing'
        );

        $queueConnection = (string) config(
            'queue.default',
            'sync'
        );

        $queueReady = $queueConnection
            !== 'sync'
            && (
                $queueConnection !== 'database'
                || Schema::hasTable('jobs')
            );

        $checks['queue'] = $this->valueCheck(
            $production
                ? $queueReady
                : true,
            $production,
            $queueReady
                ? 'queue=' . $queueConnection
                : 'queue=' . $queueConnection
                    . ' (production should use an asynchronous queue)'
        );

        $mailer = (string) config(
            'mail.default',
            ''
        );
        $disallowedMailers = config(
            'release.production.disallowed_mailers',
            ['array', 'log']
        );
        $mailReady = $mailer !== ''
            && ! in_array(
                $mailer,
                $disallowedMailers,
                true
            )
            && filled(
                config('mail.from.address')
            );

        $checks['mail'] = $this->valueCheck(
            $production
                ? $mailReady
                : true,
            $production,
            $mailReady
                ? 'mailer=' . $mailer
                : 'production mailer is not ready'
        );

        $debugOff = ! (bool) config(
            'app.debug'
        );
        $checks['debug'] = $this->valueCheck(
            $production
                ? $debugOff
                : true,
            $production,
            $debugOff
                ? 'APP_DEBUG=false'
                : 'APP_DEBUG=true'
        );

        $environmentProduction = app()
            ->environment('production');
        $checks['environment'] = $this->valueCheck(
            $production
                ? $environmentProduction
                : true,
            $production,
            'APP_ENV=' . app()->environment()
        );

        $appUrl = (string) config(
            'app.url'
        );
        $httpsUrl = str_starts_with(
            $appUrl,
            'https://'
        );
        $checks['https_url'] = $this->valueCheck(
            $production
                ? $httpsUrl
                : true,
            $production,
            'APP_URL=' . $appUrl
        );

        $secureCookie = config(
            'session.secure'
        ) === true;
        $checks['secure_session_cookie'] = $this->valueCheck(
            $production
                ? $secureCookie
                : true,
            $production,
            $secureCookie
                ? 'SESSION_SECURE_COOKIE=true'
                : 'secure session cookie disabled'
        );

        return $checks;
    }

    /**
     * @param array<string, array{
     *     ok: bool,
     *     required: bool,
     *     message: string
     * }> $checks
     */
    public function requiredChecksPass(
        array $checks
    ): bool {
        foreach ($checks as $check) {
            if (
                $check['required']
                && ! $check['ok']
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *     ok: bool,
     *     required: bool,
     *     message: string
     * }
     */
    private function check(
        bool $required,
        callable $callback
    ): array {
        try {
            $message = (string) $callback();

            return [
                'ok' => true,
                'required' => $required,
                'message' => $message,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'required' => $required,
                'message' => Str::limit(
                    $exception->getMessage(),
                    180,
                    ''
                ),
            ];
        }
    }

    /**
     * @return array{
     *     ok: bool,
     *     required: bool,
     *     message: string
     * }
     */
    private function valueCheck(
        bool $ok,
        bool $required,
        string $message
    ): array {
        return [
            'ok' => $ok,
            'required' => $required,
            'message' => $message,
        ];
    }
}
