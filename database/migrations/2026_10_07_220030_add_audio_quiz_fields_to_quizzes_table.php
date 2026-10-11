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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->text('video_embed')->nullable()->after('description');
            $table->string('reference_audio_path')->nullable()->after('video_embed');
            $table->text('guide_text')->nullable()->after('reference_audio_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['video_embed', 'reference_audio_path', 'guide_text']);
        });
    }
};
