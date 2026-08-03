<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('push_notifications', 'media_asset_id')) {
                $table->unsignedBigInteger('media_asset_id')->nullable()->after('image_url')->index();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive rollback for production safety.
    }
};
