<?php

namespace App\Services\Forus\Record\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Record\Models\RecordTypeTranslation
 *
 * @property int $id
 * @property int $record_type_id
 * @property string $locale
 * @property string $name
 * @property-read \App\Services\Forus\Record\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeTranslation whereRecordTypeId($value)
 * @mixin \Eloquent
 */
class RecordTypeTranslation extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_type() {
        return $this->belongsTo(RecordType::class);
    }
}
