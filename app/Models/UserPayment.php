<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'authorization_url',
        'access_code',
        'access_code',
        'reference',
        'amount',
        'status'
    ];
}
