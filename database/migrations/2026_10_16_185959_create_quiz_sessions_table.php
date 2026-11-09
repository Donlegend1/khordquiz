<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quiz_sessions')) {
            return;
        }

        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->unsignedSmallInteger('correct');
            $table->unsignedSmallInteger('total');
            $table->timestamp('played_at');
            $table->index(['user_id', 'played_at']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
