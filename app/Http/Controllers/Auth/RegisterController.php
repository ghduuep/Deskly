<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                "name" => $data["name"],
                "email" => $data["email"],
                "password" => $data["password"],
                "role" => UserRole::Customer,
            ]);

            $user->contact()->create([
                "name" => $data["name"],
                "email" => $data["email"],
            ]);

            return $user;
        });

        Auth::guard("web")->login($user);

        $request->session()->regenerate();

        return response()
            ->json([
                "message" => "Registered successfully.",
                "user" => new UserResource($user),
            ])
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
