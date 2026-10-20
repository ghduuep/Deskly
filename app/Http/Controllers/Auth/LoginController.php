<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::guard("web")->attempt($credentials)) {
            throw ValidationException::withMessages([
                "email" => ["The provided credentials are wrong."],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            "message" => "Authenticated successfully.",
        ]);
    }
}
