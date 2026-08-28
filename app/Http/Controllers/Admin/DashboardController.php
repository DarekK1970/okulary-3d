<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $cards = [
            [
                'key' => 'content',
                'route' => 'admin.articles.index',
                'roles' => [
                    User::ROLE_EDITOR,
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
                'icon' => '✎',
            ],
            [
                'key' => 'shop',
                'route' => 'admin.shop',
                'roles' => [
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
                'icon' => '▣',
            ],
            [
                'key' => 'users',
                'route' => 'admin.users',
                'roles' => [
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
                'icon' => '◎',
            ],
            [
                'key' => 'translations',
                'route' => 'admin.translations',
                'roles' => [
                    User::ROLE_EDITOR,
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
                'icon' => '文',
            ],
            [
                'key' => 'orchestrator',
                'route' => 'admin.orchestrator',
                'roles' => [
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
                'icon' => '◇',
            ],
            [
                'key' => 'analytics',
                'route' => 'admin.analytics',
                'roles' => [
                    User::ROLE_EDITOR,
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
                'icon' => '↗',
            ],
            [
                'key' => 'settings',
                'route' => 'admin.settings',
                'roles' => [
                    User::ROLE_SUPER_ADMIN,
                ],
                'icon' => '⚙',
            ],
        ];

        return view('admin.dashboard', [
            'user' => $user,
            'cards' => $cards,
            'stats' => [
                'users' => User::query()->count(),
                'articles' => Article::query()->count(),
                'published' => Article::query()->published()->count(),
                'admins' => User::query()
                    ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                    ->count(),
            ],
        ]);
    }
}
