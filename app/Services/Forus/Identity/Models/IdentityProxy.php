<?php

namespace App\Services\Forus\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Services\Forus\Identity\Models\IdentityProxy
 *
 * @property int $id
 * @property string $type
 * @property string|null $identity_address
 * @property string|null $access_token
 * @property string $exchange_token
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $expires_in
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $exchange_time_expired
 * @property-read \App\Services\Forus\Identity\Models\Identity|null $identity
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Identity\Models\IdentityProxy onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereExchangeToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereExpiresIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityProxy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Identity\Models\IdentityProxy withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Identity\Models\IdentityProxy withoutTrashed()
 * @mixin \Eloquent
 */
class IdentityProxy extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'access_token', 'exchange_token', 'state', 'type',
        'expires_in', 'deleted_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity() {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * Activation time expired
     *
     * @return bool
     */
    public function getExchangeTimeExpiredAttribute() {
        return $this->created_at->addSeconds($this->expires_in)->isPast();
    }

    /**
     * @param string $access_token
     * @return IdentityProxy|null
     */
    public static function findByAccessToken($access_token) {
        if (empty($access_token)) {
            return null;
        }
        
        return self::whereAccessToken($access_token)->first();
    }
}
