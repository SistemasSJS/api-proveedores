<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(Role::getFilters());
        $originalPaginator = Role::filter($filters)->paginate(10);
        $data = RoleResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles,name']);

        $role = Role::create($request->all());

        return response()->json($role, 201);
    }
}
