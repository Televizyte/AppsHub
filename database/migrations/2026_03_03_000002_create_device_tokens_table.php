<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_tokens')) {
            return;
        }

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('app_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('platform', 30)->nullable(); // android/ios/web
            $table->string('token', 500);
            $table->boolean('is_active')->default(true)->index();

            $table->dateTime('last_seen_at')->nullable()->index();
            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->unique(['app_id', 'token']);
        });
    }

    public function down(): void
    {
        // Non-destructive rollback for production safety.
    }
};
