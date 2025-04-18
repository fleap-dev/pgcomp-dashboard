<?php

namespace App\Models;

use App\Enums\UserType;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * App\Models\Area
 *
 * @property int $id
 * @property string $area
 * @property string $subarea
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Area newModelQuery()
 * @method static Builder|Area newQuery()
 * @method static Builder|Area query()
 * @method static Builder|Area whereAreaName($value)
 * @method static Builder|Area whereCreatedAt($value)
 * @method static Builder|Area whereId($value)
 * @method static Builder|Area whereProgramId($value)
 * @method static Builder|Area whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Area extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'area',
        'subarea',
    ];

    /**
     * @return array creation rules to validate attributes.
     */
    public static function creationRules(): array
    {
        return [
            'area' => 'required|string|max:255',
            'subarea' => 'required|string|max:255',
        ];
    }

    /**
     * @return array update rules to validate attributes.
     */
    public function updateRules(): array
    {
        return self::creationRules();
    }

    /**
     * @return HasMany all users that belong to this area
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'area_id', 'id');
    }

    /**
     * @return HasMany all students that belong to this area
     */
    public function students(): HasMany
    {
        return $this->users()
            ->where('type', UserType::STUDENT);
    }

}
