<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The specific instrument (e.g. "EP", not just "Piano" the category)
            // a member wants pre-selected whenever they open a quiz's sound picker.
            // Null means no preference set yet — the quiz-preview page falls back
            // to that quiz's own first sound, same as before this existed.
            $table->string('default_instrument')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_instrument');
        });
    }
};
