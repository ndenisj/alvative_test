<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaystackChargeAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'authorization_code',
        'bin',
        'last4',
        'exp_month',
        'exp_year',
        'channel',
        'card_type',
        'bank',
        'country_code',
        'brand',
        'reusable',
        'signature',
        'account_name',
    ];
}
