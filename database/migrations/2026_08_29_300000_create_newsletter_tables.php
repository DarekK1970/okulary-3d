<?php

use App\Enums\NewsletterCampaignStatus;
use App\Enums\NewsletterDeliveryStatus;
use App\Enums\NewsletterSubscriberStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('newsletter_subscribers')) {
            Schema::create('newsletter_subscribers', function (Blueprint $table) {
                $table->id();
                $table->string('email', 255)->unique();
                $table->string('locale', 5)->default('pl')->index();
                $table->string('status', 30)
                    ->default(NewsletterSubscriberStatus::Pending->value)
                    ->index();
                $table->string('source', 40)->default('footer')->index();
                $table->text('confirmation_token')->nullable();
                $table->text('unsubscribe_token');
                $table->dateTime('consent_requested_at')->nullable();
                $table->dateTime('confirmed_at')->nullable()->index();
                $table->dateTime('unsubscribed_at')->nullable();
                $table->dateTime('last_sent_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'locale']);
            });
        }

        if (! Schema::hasTable('newsletter_campaigns')) {
            Schema::create('newsletter_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('locale', 5)->index();
                $table->string('subject', 255);
                $table->string('preheader', 500)->nullable();
                $table->longText('body_html');
                $table->string('status', 30)
                    ->default(NewsletterCampaignStatus::Draft->value)
                    ->index();
                $table->dateTime('scheduled_at')->nullable()->index();
                $table->dateTime('sent_at')->nullable();
                $table->unsignedInteger('recipient_count')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('newsletter_deliveries')) {
            Schema::create('newsletter_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('newsletter_campaign_id')
                    ->constrained('newsletter_campaigns')
                    ->cascadeOnDelete();
                $table->foreignId('newsletter_subscriber_id')
                    ->nullable()
                    ->constrained('newsletter_subscribers')
                    ->nullOnDelete();
                $table->string('email_snapshot', 255);
                $table->string('status', 30)
                    ->default(NewsletterDeliveryStatus::Pending->value)
                    ->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->dateTime('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->unique(
                    ['newsletter_campaign_id', 'newsletter_subscriber_id'],
                    'newsletter_campaign_subscriber_unique'
                );
                $table->index(['newsletter_campaign_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('newsletter_deliveries')) {
            Schema::drop('newsletter_deliveries');
        }

        if (Schema::hasTable('newsletter_campaigns')) {
            Schema::drop('newsletter_campaigns');
        }

        if (Schema::hasTable('newsletter_subscribers')) {
            Schema::drop('newsletter_subscribers');
        }
    }
};
