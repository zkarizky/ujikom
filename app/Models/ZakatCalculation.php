<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZakatCalculation extends Model
{
protected $fillable = [
    'user_id',
    'type',
    'salary_type',
    'period',
    'income',
    'nisab',
    'zakat_amount',
    'is_eligible',
];
public function user()
{
    return $this->belongsTo(User::class);   
}
}