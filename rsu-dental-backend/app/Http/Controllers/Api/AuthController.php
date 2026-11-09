<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // log in with username and password (starts a session cookie)
    public function login(LoginRequest $request): JsonResponse
    {
        $username = Str::lower(trim($request->input('username')));

        // 5 wrong attempts per username and IP address, then wait one minute
        $key = 'login:'.$username.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'TOO_MANY_ATTEMPTS'], 429);
        }

        $user = User::where('username', $username)->first();

        // same answer for a wrong username and a wrong password
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json(['message' => 'INVALID_CREDENTIALS'], 422);
        }

        // checked only after the password is right, so nobody can probe which usernames exist
        if (! $user->isActive()) {
            return response()->json(['message' => 'INACTIVE'], 403);
        }

        RateLimiter::clear($key);
        Auth::guard('web')->login($user);
        $request->session()->regenerate(); // new session id after login

        return (new UserResource($user))->response();
    }

    // end the session
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    // the logged-in user (the frontend asks this when the page loads)
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}