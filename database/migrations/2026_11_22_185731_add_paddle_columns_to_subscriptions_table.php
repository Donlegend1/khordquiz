<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Web billing through Paddle Billing. Payments are still recorded in
     * subscription_payments.stripe_invoice_id (a provider-neutral reference:
     * Stripe invoice ids, or Paddle transaction ids).
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('paddle_customer_id')->nullable()->index()->after('stripe_subscription_id');
            $table->string('paddle_subscription_id')->nullable()->index()->after('paddle_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['paddle_customer_id']);
            $table->dropIndex(['paddle_subscription_id']);
            $table->dropColumn(['paddle_customer_id', 'paddle_subscription_id']);
        });
    }
};
