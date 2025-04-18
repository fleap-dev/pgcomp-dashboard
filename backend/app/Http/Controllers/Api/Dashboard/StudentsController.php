<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StudentsController extends Controller
{

    public function studentCountPerSubArea(Request $request)
    {
        $data = $request->validate([
            'selectedFilter' => 'nullable|string|in:mestrando,doutorando,completed',
        ]);

        $filter = $data['selectedFilter'] ?? null;

        $data = User::userCountPerSubArea($filter);

        return [
            'subfields' => array_keys($data),
            'data'   => array_values($data),
        ];
    }

    public function studentCountPerArea(Request $request)
    {
        //return ['fields' => ['CG', 'Análise de Dados', 'I.A',],
        //        'data' => [12, 3, 5,]];

        $data = $request->validate([
            'selectedFilter' => 'nullable|string|in:mestrando,doutorando,completed',
        ]);

        $filter = $data['selectedFilter'] ?? null;

        $data = User::userCountPerArea($filter);

        return [
            'fields' => array_keys($data),
            'data'   => array_values($data),
        ];
    }

}
