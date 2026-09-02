<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleAiImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArticleAiController extends Controller
{
    public function generateImage(
        Request $request,
        Article $article,
        ArticleAiImageService $images
    ): RedirectResponse {
        try {
            $media =
                $images->generate(
                    $article,
                    $request->user()
                );
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'article_ai_image' =>
                    $exception
                        ->getMessage(),
            ]);
        }

        return back()->with(
            'status',
            __(
                'article_ai.messages.image_generated',
                [
                    'id' =>
                        $media->id,
                ]
            )
        );
    }
}
