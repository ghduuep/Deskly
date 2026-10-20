<?php

use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(["auth:sanctum", "throttle:api"])->group(function () {
    Route::get("/me", MeController::class);
    Route::apiResource("tickets", TicketController::class)->except(["destroy"]);
});
