<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * App\Models\Subarea
 *
 * @property int $id
 * @property string $subarea_name
 * @property int $area_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Area $area
 * @method static Builder|Subarea newModelQuery()
 * @method static Builder|Subarea newQuery()
 * @method static Builder|Subarea query()
 * @method static Builder|Subarea whereAreaId($value)
 * @method static Builder|Subarea whereCreatedAt($value)
 * @method static Builder|Subarea whereId($value)
 * @method static Builder|Subarea whereSubareaName($value)
 * @method static Builder|Subarea whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Subarea extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'subarea_name',
        'area_id',
    ];

    public static function creationRules(): array
    {
        return [
            'subarea_name' => 'required|string|max:255',
            'area_id' => [
                'nullable',
                'int',
                Rule::exists(Area::class, 'id'),
                'required',
            ],
        ];
    }

    public static function createOrUpdateSubarea(array $data): Subarea
    {
        return Subarea::updateOrCreate(
            Arr::only($data, ['subarea_name']),
            $data
        );
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }


    public function updateRules(): array
    {
        return [
            'subarea_name' => 'required|string|max:255',
            'area_id' => [
                'nullable',
                'int',
                Rule::exists(Area::class, 'id'),
                'required',
            ],
        ];
    }

    public function findSubarea($id, $attributes = ['id', 'subarea_name']){
        return Subarea::where('id', $id)->firstOrFail($attributes);
    }

    public function findAllSubarea($attributes = ['id', 'subarea_name']): \Illuminate\Database\Eloquent\Collection
    {
        return Subarea::all($attributes);
    }

    public function findSubareaByName($name, $attributes = ['id', 'subarea_name']): self
    {
        return self::where('subarea_name', $name)->firstOrFail($attributes);
    }

    public function deleteSubareaByName($name)
    {
        $subarea = Subarea::where('subarea_name', $name)->firstOrFail();
        return $subarea->delete();
    }
}
