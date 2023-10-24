<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class Transaction extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id', 'category', 'channel_id', 'amount',
        'reference', 'status'
    ];
}