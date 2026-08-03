<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('icon_presets', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();     // e.g. icon_book, icon_play
            $table->string('label', 160);             // Human friendly
            $table->string('group', 120)->nullable(); // e.g. Navigation, Tools, Content
            $table->longText('svg');                  // raw SVG markup
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icon_presets');
    }
};
