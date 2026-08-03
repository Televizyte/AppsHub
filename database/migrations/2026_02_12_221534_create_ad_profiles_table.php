<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')
                ->unique()
                ->constrained('apps')
                ->cascadeOnDelete();

            $table->boolean('ads_enabled')->default(true);

            $table->string('banner_unit_id')->nullable();
            $table->string('native_unit_id')->nullable();
            $table->string('interstitial_unit_id')->nullable();

            $table->json('meta_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_profiles');
    }
};
