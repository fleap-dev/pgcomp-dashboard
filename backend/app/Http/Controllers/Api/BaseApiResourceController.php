<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters;
use App\Models\BaseModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        (new Filters())->applyFilters($query, $request->input('filters', []));

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

    /**
     * Return class name.
     */
    abstract protected function modelClass(): string|BaseModel;
}
