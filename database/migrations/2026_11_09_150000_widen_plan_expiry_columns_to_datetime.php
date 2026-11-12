<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL TIMESTAMP columns stop at 2038-01-19, but plan expiries can be later
 * (e.g. Kingsleykhordpiano manual grants that run to 2050). DATETIME goes to 9999.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dateTime('current_period_end')->nullable()->change();
            $table->dateTime('trial_ends_at')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('plan_started_at')->nullable()->change();
            $table->dateTime('plan_expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('current_period_end')->nullable()->change();
            $table->timestamp('trial_ends_at')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('plan_started_at')->nullable()->change();
            $table->timestamp('plan_expires_at')->nullable()->change();
        });
    }
};
