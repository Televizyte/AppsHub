<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')
                ->constrained('apps')
                ->cascadeOnDelete();

            $table->string('scope_type'); // tab | route | bucket_list
            $table->string('scope_key');

            $table->boolean('is_enabled')->default(true);

            $table->boolean('banner_enabled')->default(true);
            $table->boolean('native_enabled')->default(true);
            $table->boolean('interstitial_enabled')->default(false);

            $table->integer('interstitial_cooldown_seconds')->default(120);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_rules');
    }
};
