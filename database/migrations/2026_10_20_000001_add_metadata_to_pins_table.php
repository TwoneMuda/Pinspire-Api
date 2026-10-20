<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pins', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
            $table->string('category')->default('Interior')->after('description');
            $table->string('vibe')->default('Calm')->after('category');
            $table->json('tags')->nullable()->after('vibe');
            $table->json('palette')->nullable()->after('tags');
            $table->string('image_url', 2048)->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pins', function (Blueprint $table) {
            $table->dropColumn(['category', 'vibe', 'tags', 'palette', 'image_url']);
        });
    }
};
