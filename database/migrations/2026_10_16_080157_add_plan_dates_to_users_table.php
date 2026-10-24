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
        Schema::table('users', function (Blueprint $table) {
            // Still no subscriptions/payments table — these are admin-managed dates
            // (manual grants, comps, support overrides), not billing-confirmed ones.
            // A null plan_expires_at means "doesn't expire" (e.g. a lifetime plan, or
            // a plan with no date set yet); plan_started_at is set the first time an
            // admin assigns a plan, so "member since" has something to show.
            $table->timestamp('plan_started_at')->nullable()->after('payment_method');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan_started_at', 'plan_expires_at']);
        });
    }
};
