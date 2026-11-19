<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->string('notation')->default('Solfège')->after('default_instrument');
            $table->string('notify_by')->default('Push')->after('notation');
            // IANA name (e.g. "Africa/Lagos"), so reminders go out at the member's local time.
            $table->string('timezone')->nullable()->after('notify_by');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn(['notation', 'notify_by', 'timezone']);
        });
    }
};
