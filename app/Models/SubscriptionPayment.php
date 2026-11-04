<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = ['user_id', 'stripe_invoice_id', 'amount', 'plan', 'status', 'paid_on'];
}
