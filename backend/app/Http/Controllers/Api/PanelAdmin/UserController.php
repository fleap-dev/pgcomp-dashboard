<?php

namespace App\Http\Controllers\Api\PanelAdmin;

use App\Http\Controllers\Api\BaseApiResourceController;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseApiResourceController
{
    protected function modelClass(): string|BaseModel
    {
        return User::class;
    }

    public function store(Request $request)
    {
        $user = parent::store($request);
        $subareas = $request->input("subareas");
        $this->saveSubareas($user, $subareas);
        return $user;
    }

    public function update(Request $request, int $id)
    {
        $user = parent::update($request, $id);
        $subareas = $request->input("subareas");
        $this->saveSubareas($user, $subareas);
        return $user;
    }

    public function show(int $id){
        return (new \App\Models\User)->findUserSubareas($id);
    }

    protected function saveSubareas($user, $subareas){
            die("NOT IMPLEMENTED");
    }

}
