<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real web billing (Stripe) and the Kingsleykhordpiano membership sync.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->index()->after('provider');
            $table->string('stripe_subscription_id')->nullable()->index()->after('stripe_customer_id');
            // When paid access runs out unless renewed: the Stripe period end, or
            // the expiry Kingsleykhordpiano reports. Null = no expiry (lifetime).
            $table->timestamp('current_period_end')->nullable()->after('stripe_subscription_id');
            // Whether this member has ever had a free trial (one per member).
            $table->boolean('trial_used')->default(false)->after('trial_cancelled');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('stripe_invoice_id')->nullable()->unique()->after('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('kkp_member_id')->nullable()->index()->after('plan_expires_at');
            $table->timestamp('kkp_synced_at')->nullable()->after('kkp_member_id');
            $table->timestamp('kkp_invited_at')->nullable()->after('kkp_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kkp_member_id', 'kkp_synced_at', 'kkp_invited_at']);
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropUnique(['stripe_invoice_id']);
            $table->dropColumn('stripe_invoice_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id', 'current_period_end', 'trial_used']);
        });
    }
};
