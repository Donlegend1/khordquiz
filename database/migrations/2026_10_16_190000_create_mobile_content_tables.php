<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('appearance')->default('System');
            $table->boolean('haptics')->default(true);
            $table->string('language')->default('English');
            $table->boolean('push_notifications')->default(true);
            $table->string('reminder_time')->default('7:00 PM');
            $table->boolean('reminders')->default(true);
            $table->boolean('sound_effects')->default(true);
            $table->boolean('streak_alerts')->default(true);
            $table->string('default_instrument')->default('Piano');
            $table->timestamps();
        });

        Schema::create('quiz_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->timestamps();
            $table->unique(['user_id', 'slug']);
        });

        Schema::create('quiz_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->timestamp('completed_at');
            $table->unique(['user_id', 'slug']);
        });

        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->unsignedSmallInteger('correct');
            $table->unsignedSmallInteger('total');
            $table->timestamp('played_at');
            $table->index(['user_id', 'played_at']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('answer');
            $table->string('picked')->nullable();
            $table->boolean('correct');
            $table->string('kind')->default('single');
            $table->timestamp('answered_at');
            $table->index(['user_id', 'answered_at']);
        });

        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author');
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->unsignedInteger('votes')->default(0);
            $table->timestamps();
        });

        Schema::create('suggestion_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suggestion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['suggestion_id', 'user_id']);
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('mine')->default(true);
            $table->string('slug');
            $table->unsignedSmallInteger('total')->default(25);
            $table->string('from_name');
            $table->string('from_avatar')->nullable();
            $table->unsignedSmallInteger('from_score');
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::create('challenge_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->unsignedSmallInteger('score');
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sender');
            $table->text('text');
            $table->timestamp('sent_at');
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('access')->default('none');
            $table->string('plan_id')->nullable();
            $table->string('status')->default('active');
            $table->string('provider')->nullable();
            $table->string('renews')->nullable();
            $table->string('since')->nullable();
            $table->string('next_plan')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('trial_cancelled')->default(false);
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('amount');
            $table->string('plan');
            $table->string('status')->default('Paid');
            $table->string('paid_on');
            $table->timestamps();
        });

        $now = now();
        $suggestions = [
            ['Philip O.', 'Video explanations for failed questions', 'After getting a question wrong, show a short video clip explaining the concept in detail.', 'pending', null, 13],
            ['Matthew L.', 'Dark/Light Theme Toggle', 'Let me switch the whole app between a dark and a light look.', 'pending', null, 11],
            ['Ada N.', 'More instrument sounds', 'Play each chord on guitar or strings as well as piano so I can train my ear on other instruments.', 'pending', null, 9],
            ['Samuel K.', 'Daily practice reminders', 'Send a reminder at a time I choose so I keep my daily streak going.', 'approved', null, 21],
            ['Grace T.', 'Slower playback option', 'Slow down the playback speed for melodic dictation on the harder levels.', 'approved', null, 17],
            ['Daniel B.', 'Live quiz battles with friends', 'Compete live against a friend on the same quiz in real time.', 'declined', "Real-time multiplayer requires significant infrastructure investment beyond our current scope. We'll revisit in a future version.", 8],
            ['Ruth A.', 'Upload my own audio', 'Let me upload my own recordings and quiz myself on them.', 'declined', "We can't check custom recordings for correct answers yet, so results wouldn't be reliable.", 5],
            ['Philip O.', 'Most-confused answers on Progress', 'Show which answers I mix up most so I know what to practise.', 'done', null, 24],
            ['Matthew L.', 'Review my mistakes', 'Replay the questions I got wrong in one short review session.', 'done', null, 19],
        ];

        foreach (array_reverse($suggestions) as $suggestion) {
            DB::table('suggestions')->insert([
                'user_id' => null,
                'author' => $suggestion[0],
                'title' => $suggestion[1],
                'description' => $suggestion[2],
                'status' => $suggestion[3],
                'reason' => $suggestion[4],
                'votes' => $suggestion[5],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('challenge_results');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('suggestion_votes');
        Schema::dropIfExists('suggestions');
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_sessions');
        Schema::dropIfExists('quiz_completions');
        Schema::dropIfExists('quiz_bookmarks');
        Schema::dropIfExists('user_preferences');
    }
};
