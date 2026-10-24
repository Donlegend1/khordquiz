<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Find the Key #2's reference note per question position — shuffled the
            // same way question_sequence is, fresh on every attempt restart. Null
            // for every other quiz (they have no reference-note concept).
            $table->json('reference_note_sequence')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('reference_note_sequence');
        });
    }
};
