<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

abstract class BaseApiResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'int|min:1',
            'per_page' => 'int|min:1|max:100',
            'order_by' => 'string',
            'dir' => 'string|in:asc,desc',
            'filters' => 'array',
            'filters.*.field' => 'string|required',
            'filters.*.value' => 'required',
            'filters.*.operator' => 'string|in:in,not in,like,not like,=,!=',
        ]);

        $query = $this->modelClass()::query();

        $model = $this->newModelInstance();

        $orderBy = $request->input('order_by');
        if ($orderBy && $model->canSortBy($orderBy)) {
            $query->orderBy($orderBy, $request->input('dir', 'asc'));
        }

        $this->applyFilters($query, $request->input('filters', []));

        return $query->paginate($request->input('per_page'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        return $this->modelClass()::create($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        return $this->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $model = $this->findOrFail($id);

        $model->update($request->all());

        return $model;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    protected function findOrFail(int $id, array $columns = ['*']): BaseModel
    {
        return $this->modelClass()::findOrFail($id, $columns);
    }

    protected function newModelInstance(): BaseModel
    {
        return $this->modelClass()::newModelInstance();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $model = $this->newModelInstance();
        foreach ($filters as $filter) {
            if (!$model->canFilterBy($filter['field'])) {
                continue;
            }

            $operator = $filter['operator'] ?? null;
            $type = $model->getCasts()[$filter['field']] ?? 'string';

            if (in_array($operator, ['in', 'not in'])) {
                $values = explode('|', $filter['value']);
                $query->whereIn($filter['field'], $values, not: $operator === 'not in');
            } elseif (in_array($operator, ['=', '!='])) {
                $query->where($filter['field'], $operator, $filter['value']);
            } elseif (in_array($operator, ['like', 'not like'])) {
                $query->where($filter['field'], $operator, "%{$filter['value']}%");
            } elseif ($model->isDateCast($filter['field'])) {
                $this->dateFilter($query, $filter);
            } elseif ($model->isNumberCast($filter['field'])) {
                $this->numberFilter($query, $filter);
            } elseif ($type === 'string') {
                $query->where($filter['field'], 'like', "%{$filter['value']}%");
            } else {
                throw new \Exception("Invalid filter {$filter['field']}");
            }
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param array{field: string, value: mixed} $filter
     * @return Builder
     */
    protected function dateFilter(Builder $query, array $filter): Builder
    {
        if (Str::of($filter['value'])->contains('|')) {
            $minMax = explode('|', $filter['value']);
            array_walk($minMax, 'trim');
            $min = $minMax[0];
            $max = $minMax[1] ?? '';
            if ($min !== '' && $max !== '') {
                $query->whereBetween($filter['field'], [$min, $max]);
            } elseif ($min !== '') {
                $query->whereDate($filter['field'], '>=', $min);
            } elseif ($max !== '') {
                $query->whereDate($filter['field'], '<=', $max);
            }
        } else {
            $query->whereDate($filter['field'], $filter['value']);
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array{field: string, value: mixed} $filter
     * @return Builder
     */
    protected function numberFilter(Builder $query, array $filter): Builder
    {
        if (Str::of($filter['value'])->contains('|')) {
            $minMax = explode('|', $filter['value']);
            array_walk($minMax, 'trim');
            $min = $minMax[0];
            $max = $minMax[1] ?? '';
            if ($min !== '' && $max !== '') {
                $query->whereBetween($filter['field'], [$min, $max]);
            } elseif ($min !== '') {
                $query->where($filter['field'], '>=', $min);
            } elseif ($max !== '') {
                $query->where($filter['field'], '<=', $max);
            }
        } else {
            $query->where($filter['field'], $filter['value']);
        }

        return $query;
    }

    /**
     * Return class name.
     */
    abstract protected function modelClass(): string|BaseModel;
}
