<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('push_notifications', 'app_id')) {
                $table->unsignedBigInteger('app_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('push_notifications', 'title')) {
                $table->string('title', 120)->nullable()->after('app_id');
            }
            if (!Schema::hasColumn('push_notifications', 'body')) {
                $table->string('body', 600)->nullable()->after('title');
            }
            if (!Schema::hasColumn('push_notifications', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('body');
            }

            if (!Schema::hasColumn('push_notifications', 'deep_link_url')) {
                $table->string('deep_link_url', 600)->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('push_notifications', 'click_action')) {
                $table->string('click_action', 120)->nullable()->after('deep_link_url');
            }

            if (!Schema::hasColumn('push_notifications', 'target_type')) {
                $table->string('target_type', 30)->default('topic')->after('click_action')->index();
            }
            if (!Schema::hasColumn('push_notifications', 'target_value')) {
                $table->string('target_value', 255)->nullable()->after('target_type');
            }

            if (!Schema::hasColumn('push_notifications', 'timezone')) {
                $table->string('timezone', 60)->nullable()->after('target_value')->index();
            }
            if (!Schema::hasColumn('push_notifications', 'scheduled_for')) {
                $table->dateTime('scheduled_for')->nullable()->after('timezone')->index();
            }

            if (!Schema::hasColumn('push_notifications', 'recurrence_type')) {
                $table->string('recurrence_type', 20)->default('none')->after('scheduled_for')->index();
            }
            if (!Schema::hasColumn('push_notifications', 'recurrence_weekdays')) {
                $table->json('recurrence_weekdays')->nullable()->after('recurrence_type');
            }
            if (!Schema::hasColumn('push_notifications', 'recurrence_month_day')) {
                $table->unsignedTinyInteger('recurrence_month_day')->nullable()->after('recurrence_weekdays');
            }
            if (!Schema::hasColumn('push_notifications', 'recurrence_hour')) {
                $table->unsignedTinyInteger('recurrence_hour')->nullable()->after('recurrence_month_day');
            }
            if (!Schema::hasColumn('push_notifications', 'recurrence_minute')) {
                $table->unsignedTinyInteger('recurrence_minute')->nullable()->after('recurrence_hour');
            }
            if (!Schema::hasColumn('push_notifications', 'ends_at')) {
                $table->dateTime('ends_at')->nullable()->after('recurrence_minute')->index();
            }
            if (!Schema::hasColumn('push_notifications', 'max_runs')) {
                $table->unsignedInteger('max_runs')->nullable()->after('ends_at');
            }
            if (!Schema::hasColumn('push_notifications', 'runs_count')) {
                $table->unsignedInteger('runs_count')->default(0)->after('max_runs');
            }

            if (!Schema::hasColumn('push_notifications', 'status')) {
                $table->string('status', 20)->default('draft')->after('runs_count')->index();
            }
            if (!Schema::hasColumn('push_notifications', 'meta_json')) {
                $table->json('meta_json')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive rollback for production safety.
    }
};
