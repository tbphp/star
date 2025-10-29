<?php

use App\Http\Controllers\ChildController;
use App\Http\Controllers\StarController;
use App\Http\Controllers\RewardController;
use Illuminate\Support\Facades\Route;

// Children routes
Route::apiResource('children', ChildController::class);

// Star operations
Route::post('children/{child}/stars/add', [StarController::class, 'add']);
Route::post('children/{child}/stars/subtract', [StarController::class, 'subtract']);

// Rewards routes
Route::apiResource('rewards', RewardController::class)->except(['update']);
Route::post('rewards/{reward}/redeem', [RewardController::class, 'redeem']);
