<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'lenticular_plan',
        'preferred_locale',
        'plan_expires_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
        ], true);
    }

    public function canAccessAdminPanel(): bool
    {
        return in_array($this->role, [
            self::ROLE_EDITOR,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
        ], true);
    }

    public function falAiJobs(): HasMany
    {
        return $this->hasMany(FalAiJob::class);
    }

    public function lenticularProjects(): HasMany
    {
        return $this->hasMany(LenticularProject::class);
    }

    public function tokenLensTransactions(): HasMany
    {
        return $this->hasMany(TokenLensTransaction::class);
    }
}
