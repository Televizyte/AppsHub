<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_id')
                ->constrained('app_sections')
                ->cascadeOnDelete();

            $table->string('type');
            $table->string('title');
            $table->string('subtitle')->nullable();

            $table->string('icon')->nullable();
            $table->string('image_url')->nullable();

            $table->string('route')->nullable();
            $table->string('url')->nullable();

            $table->json('payload_json')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_items');
    }
};
