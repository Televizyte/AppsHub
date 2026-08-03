<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_routes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')
                ->constrained('apps')
                ->cascadeOnDelete();

            $table->string('key');
            $table->string('title');

            $table->string('route')->nullable();
            $table->string('tab_key')->nullable();

            $table->boolean('is_enabled')->default(true);

            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->unique(['app_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_routes');
    }
};
