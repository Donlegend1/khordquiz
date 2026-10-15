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
            // What the member picked on the pricing page at signup. There's no
            // subscriptions/payments table yet (no Stripe/PayPal wiring), so
            // this only records intent — it isn't a confirmation of billing.
            $table->string('plan_choice')->nullable()->after('terms_accepted_at');
            $table->string('payment_method')->nullable()->after('plan_choice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan_choice', 'payment_method']);
        });
    }
};
