<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:set-role {email} {role}', function (string $email, string $role) {
    $roles = [
        User::ROLE_USER,
        User::ROLE_EDITOR,
        User::ROLE_ADMIN,
        User::ROLE_SUPER_ADMIN,
    ];

    if (! in_array($role, $roles, true)) {
        $this->error('Nieprawidłowa rola. Dostępne: ' . implode(', ', $roles));

        return 1;
    }

    $user = User::query()
        ->where('email', $email)
        ->first();

    if (! $user) {
        $this->error("Nie znaleziono użytkownika: {$email}");

        return 1;
    }

    $previousRole = $user->role;
    $user->role = $role;
    $user->save();

    $this->info("Zmieniono rolę {$email}: {$previousRole} -> {$role}");

    return 0;
})->purpose('Nadaj użytkownikowi rolę RBAC');

Schedule::command('articles:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('newsletter:send-due --limit=100')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('portal:analytics-prune --days=180')
    ->dailyAt('03:30')
    ->withoutOverlapping();

/*
 * The configured update time lives in the database. The lightweight
 * command is therefore invoked by Laravel Scheduler every minute and
 * exits immediately unless current HH:MM matches the saved setting.
 *
 * The existing Plesk "artisan schedule:run" task is sufficient.
 */
Schedule::command('currency:rates-update --scheduled')
    ->everyMinute()
    ->withoutOverlapping();
