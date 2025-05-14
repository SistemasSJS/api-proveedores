<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return Role::all();
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles,name']);

        $role = Role::create($request->all());

        return response()->json($role, 201);
    }
}
