<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a login request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Example Request:
     * POST /api/auth/login
     * {
     *   "email": "viviurka@contabil.com",
     *   "password": "password"
     * }
     *
     * Example Response:
     * {
     *   "token": "1|abc123...",
     *   "user": {
     *     "id": 1,
     *     "name": "Viviurka",
     *     "email": "viviurka@contabil.com",
     *     "role": "master"
     *   }
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
            ],
        ]);
    }

    /**
     * Handle a logout request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Example Request:
     * POST /api/auth/logout
     * Authorization: Bearer {token}
     *
     * Example Response:
     * {
     *   "message": "Logout realizado com sucesso"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Example Request:
     * GET /api/auth/me
     * Authorization: Bearer {token}
     *
     * Example Response:
     * {
     *   "id": 1,
     *   "name": "Viviurka",
     *   "email": "viviurka@contabil.com",
     *   "role": "master",
     *   "company_id": null
     * }
     */
    public function me(Request $request)
    {
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => $request->user()->role,
            'company_id' => $request->user()->company_id,
        ]);
    }

    /**
     * Handle a registration request (optional).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'normal', // Default role
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
            ],
        ], 201);
    }
}
