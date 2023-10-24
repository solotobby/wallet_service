<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class RevenueHistory extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id', 'category', 'channel_id', 'amount',
        'campaign_id', 'activity_id'
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}