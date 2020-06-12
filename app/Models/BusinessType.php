<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * App\Models\BusinessType
 *
 * @property int $id
 * @property string $key
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $organizations
 * @property-read int|null $organizations_count
 * @property-read \App\Models\BusinessTypeTranslation $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BusinessTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType listsTranslations($translationField)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType notTranslatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType orWhereTranslation($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType orWhereTranslationLike($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType orderByTranslation($translationField, $sortMethod = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType translated()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType translatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType whereTranslation($translationField, $value, $locale = null, $method = 'whereHas', $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType whereTranslationLike($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BusinessType withTranslation()
 * @mixin \Eloquent
 */
class BusinessType extends Model
{
    use Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'parent_id'
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organizations(): HasMany {
       return $this->hasMany(Organization::class);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search(Request $request) {
        $query = self::query();

        if ($request->input('used', false)) {
            $query->whereHas('organizations.supplied_funds_approved', static function(
                Builder $builder
            ) {
                $builder->whereIn(
                    'funds.id',
                    Implementation::activeFunds()->pluck('id')->toArray()
                );
            });
        }

        return $query;
    }
}
