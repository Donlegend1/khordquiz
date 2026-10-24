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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Which sound (Piano, EP, Guitar...) the member picked on the quiz preview
            // before starting — resolved against quiz_question_audio at play time so
            // each question plays in the chosen sound. Set once when the attempt starts
            // fresh (see question_sequence's migration comment, same "fresh on restart"
            // lifecycle); null for quizzes with no generated audio pipeline yet.
            $table->string('sound')->nullable()->after('question_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('sound');
        });
    }
};
