<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users (employees).
     *
     * GET /api/users
     */
    public function index(Request $request)
    {
        // Only master can access
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $users = User::where('role', 'normal')
            ->with('company')
            ->latest()
            ->get();

        return response()->json($users);
    }

    /**
     * Store a newly created user (employee).
     *
     * POST /api/users
     */
    public function store(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'company_id' => 'nullable|exists:companies,id',
            'permissions' => 'nullable|array',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'normal',
            'company_id' => $request->company_id,
            'permissions' => $request->permissions ?? [],
        ]);

        return response()->json($user->load('company'), 201);
    }

    /**
     * Update the specified user.
     *
     * PUT /api/users/{id}
     */
    public function update(Request $request, User $user)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        // Only update normal users
        if ($user->isMaster()) {
            abort(403, 'Cannot update master user');
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'company_id' => 'nullable|exists:companies,id',
            'permissions' => 'nullable|array',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('company_id')) {
            $user->company_id = $request->company_id;
        }
        if ($request->has('permissions')) {
            $user->permissions = $request->permissions;
        }

        $user->save();

        return response()->json($user->load('company'));
    }

    /**
     * Remove the specified user.
     *
     * DELETE /api/users/{id}
     */
    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        // Cannot delete master
        if ($user->isMaster()) {
            abort(403, 'Cannot delete master user');
        }

        $user->delete();

        return response()->json(['message' => 'Usuário excluído']);
    }
}

