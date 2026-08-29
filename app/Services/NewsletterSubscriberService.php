<?php

namespace App\Services;

use App\Enums\NewsletterSubscriberStatus;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class NewsletterSubscriberService
{
    public function subscribe(
        string $email,
        string $locale,
        string $source = 'footer'
    ): NewsletterSubscriber {
        $email = Str::lower(trim($email));

        $subscriber = DB::transaction(function () use (
            $email,
            $locale,
            $source
        ): NewsletterSubscriber {
            $subscriber = NewsletterSubscriber::query()
                ->where('email', $email)
                ->first();

            if ($subscriber?->isActive()) {
                return $subscriber;
            }

            $subscriber ??= new NewsletterSubscriber();

            $subscriber->fill([
                'email' => $email,
                'locale' => $locale,
                'status' => NewsletterSubscriberStatus::Pending,
                'source' => $source,
                'confirmation_token' => Str::random(64),
                'unsubscribe_token' => $subscriber->unsubscribe_token
                    ?: Str::random(64),
                'consent_requested_at' => now(),
                'confirmed_at' => null,
                'unsubscribed_at' => null,
            ]);

            $subscriber->save();

            return $subscriber;
        });

        if ($subscriber->status === NewsletterSubscriberStatus::Pending) {
            try {
                Mail::to($subscriber->email)
                    ->locale($subscriber->locale)
                    ->send(new NewsletterConfirmationMail(
                        $subscriber,
                        (string) $subscriber->confirmation_token
                    ));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $subscriber;
    }

    public function confirm(
        NewsletterSubscriber $subscriber,
        string $token
    ): bool {
        if ($subscriber->isActive()) {
            return true;
        }

        $stored = (string) $subscriber->confirmation_token;

        if ($stored === '' || ! hash_equals($stored, $token)) {
            return false;
        }

        $subscriber->forceFill([
            'status' => NewsletterSubscriberStatus::Active,
            'confirmation_token' => null,
            'confirmed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return true;
    }

    public function unsubscribe(
        NewsletterSubscriber $subscriber,
        string $token
    ): bool {
        $stored = (string) $subscriber->unsubscribe_token;

        if ($stored === '' || ! hash_equals($stored, $token)) {
            return false;
        }

        $subscriber->forceFill([
            'status' => NewsletterSubscriberStatus::Unsubscribed,
            'confirmation_token' => null,
            'unsubscribed_at' => now(),
        ])->save();

        return true;
    }
}
