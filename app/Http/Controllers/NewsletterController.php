<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Services\NewsletterSubscriberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function subscribe(
        Request $request,
        string $locale,
        NewsletterSubscriberService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],
            'consent' => [
                'accepted',
            ],
        ]);

        $service->subscribe(
            $validated['email'],
            $locale,
            'footer'
        );

        return back()->with(
            'newsletter_status',
            __('newsletter.public.check_email')
        );
    }

    public function confirm(
        string $locale,
        NewsletterSubscriber $subscriber,
        string $token,
        NewsletterSubscriberService $service
    ): View {
        $confirmed = $service->confirm(
            $subscriber,
            $token
        );

        return view('newsletter.result', [
            'success' => $confirmed,
            'title' => $confirmed
                ? __('newsletter.public.confirmed_title')
                : __('newsletter.public.invalid_title'),
            'message' => $confirmed
                ? __('newsletter.public.confirmed_text')
                : __('newsletter.public.invalid_text'),
        ]);
    }

    public function unsubscribeForm(
        string $locale,
        NewsletterSubscriber $subscriber,
        string $token
    ): View {
        $valid = hash_equals(
            (string) $subscriber->unsubscribe_token,
            $token
        );

        return view('newsletter.unsubscribe', [
            'subscriber' => $subscriber,
            'token' => $token,
            'valid' => $valid,
        ]);
    }

    public function unsubscribe(
        string $locale,
        NewsletterSubscriber $subscriber,
        string $token,
        NewsletterSubscriberService $service
    ): View {
        $unsubscribed = $service->unsubscribe(
            $subscriber,
            $token
        );

        return view('newsletter.result', [
            'success' => $unsubscribed,
            'title' => $unsubscribed
                ? __('newsletter.public.unsubscribed_title')
                : __('newsletter.public.invalid_title'),
            'message' => $unsubscribed
                ? __('newsletter.public.unsubscribed_text')
                : __('newsletter.public.invalid_text'),
        ]);
    }
}
