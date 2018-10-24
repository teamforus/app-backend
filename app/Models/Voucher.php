<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Voucher
 * @property mixed $id
 * @property integer $fund_id
 * @property integer|null $product_id
 * @property integer|null $parent_id
 * @property integer $identity_address
 * @property string $amount
 * @property string $address
 * @property string $type
 * @property float $amount_available
 * @property Fund $fund
 * @property Product|null $product
 * @property Voucher|null $parent
 * @property Collection $transactions
 * @property Collection $product_vouchers
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Voucher extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'identity_address', 'amount', 'address', 'product_id',
        'parent_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent() {
        return $this->belongsTo(Voucher::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(
            Product::class, 'product_id', 'id'
        )->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions() {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_vouchers() {
        return $this->hasMany(Voucher::class, 'parent_id');
    }

    /**
     * @return string
     */
    public function getTypeAttribute() {
        return $this->product_id ? 'product' : 'regular';
    }

    public function getAmountAvailableAttribute() {
        return $this->amount -
            $this->transactions->sum('amount') -
            $this->product_vouchers()->sum('amount');
    }
}
