<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('app_business_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('status', 30)->default('new')->index();
            $table->string('name', 160);
            $table->string('organization', 190)->nullable();
            $table->string('email', 190);
            $table->string('country', 100)->nullable();
            $table->string('project_type', 120)->nullable();
            $table->text('message');
            $table->string('preferred_response', 40)->default('email');
            $table->string('phone', 60)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->index(['app_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('app_business_enquiries'); }
};
