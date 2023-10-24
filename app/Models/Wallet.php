<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class Wallet extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'balance', 'revenue_share_group'
    ];

    public function revenueHistories()
    {
        return $this->hasMany(RevenueHistory::class);
    }
}
