<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class WalletAsset
 * @property mixed $id
 * @property integer $wallet_id
 * @property Wallet $wallet
 * @property String $address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class WalletAsset extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id', 'address'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }
}
