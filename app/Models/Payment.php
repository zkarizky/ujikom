<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'zakat_id',
        'order_id',
        'amount',
        'payment_type',
        'transaction_status',
        'snap_token'
    ];
    public function zakat()
    {
        return $this->belongsTo(ZakatCalculation::class, 'zakat_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
