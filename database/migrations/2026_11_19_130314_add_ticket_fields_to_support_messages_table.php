<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->string('subject', 150)->nullable()->after('topic');
            // "web" (Support → Email Support) or "app" (the mobile app's contact screen).
            $table->string('source', 10)->default('app')->after('message');
            $table->string('attachment_path')->nullable()->after('source');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            // open = waiting on the team, answered = waiting on the member, resolved = closed.
            $table->string('status', 20)->default('open')->after('attachment_name');
            $table->timestamp('last_activity_at')->nullable()->after('status');
            $table->timestamp('resolved_at')->nullable()->after('last_activity_at');
            $table->index(['status', 'last_activity_at']);
        });

        // Requests sent before tickets existed sort by when they were sent.
        DB::table('support_messages')->whereNull('last_activity_at')->update(['last_activity_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['status', 'last_activity_at']);
            $table->dropColumn(['subject', 'source', 'attachment_path', 'attachment_name', 'status', 'last_activity_at', 'resolved_at']);
        });
    }
};
