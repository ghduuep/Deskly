<?php

use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->get("/me", MeController::class);

Route::middleware("auth:sanctum")->group(function () {
    Route::apiResource("tickets", TicketController::class)->except(["destroy"]);
});
