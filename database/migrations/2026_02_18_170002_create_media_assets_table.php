<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('app_id')->nullable()->index(); // null = shared/global
            $table->string('type', 40)->default('image');              // image, video, file
            $table->string('label', 180)->nullable();
            $table->string('bucket', 80)->nullable();                  // banners, logos, covers, misc
            $table->string('disk', 40)->default('public');
            $table->string('path', 255);                               // storage path
            $table->string('url', 255)->nullable();                    // computed or stored
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->json('tags_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
