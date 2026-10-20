<?php

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

Route::post("/login", LoginController::class)
    ->middleware("guest")
    ->name("login");

Route::post("/logout", LogoutController::class)->middleware("auth:web");

Route::post("/register", RegisterController::class)->middleware(["guest:web"]);
