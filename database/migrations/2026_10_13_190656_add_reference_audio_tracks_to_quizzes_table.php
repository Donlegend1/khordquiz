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
            // A quiz can have several labeled reference-audio clips (e.g. one per
            // chord/sound it covers), unlike the single reference_audio_path
            // column. Each entry: {id, label, path}.
            $table->json('reference_audio_tracks')->nullable()->after('reference_audio_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('reference_audio_tracks');
        });
    }
};
