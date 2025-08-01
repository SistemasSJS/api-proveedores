<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $fields = User::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'name');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = User::with(array_merge(User::eagerLodable(), ['role']))
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);


        $users = UserResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($users)));
    }

    public function store(UserStoreRequest $request)
    {
        $user = User::create($request->validate());
        return $this->success([
            'user' => new UserResource($user->load(['role']))
        ], 201);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            throw new ResourceNotFoundException("Usuario no encontrado.");
        }
        return $this->success(new UserResource($user->load(['role'])));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'string', Password::min(8)],
        ]);

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return $this->success(new UserResource($user->load(['role'])));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return $this->success(null, 204);
    }
}
