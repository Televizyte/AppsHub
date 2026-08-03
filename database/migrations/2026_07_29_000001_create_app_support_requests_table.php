<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_support_requests')) {
            return;
        }

        Schema::create('app_support_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 80)->unique();
            $table->string('category', 80)->default('general');
            $table->string('status', 40)->default('new');
            $table->string('verification_status', 40)->default('not_required');
            $table->string('email', 190);
            $table->string('display_name', 160)->nullable();
            $table->string('subject', 220)->nullable();
            $table->text('message')->nullable();
            $table->string('source', 40)->default('web');
            $table->string('app_version', 80)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('submitted_route', 300)->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('internal_note')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['app_id', 'status']);
            $table->index(['app_id', 'category']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_support_requests');
    }
};
