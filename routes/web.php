<?php

use App\Http\Controllers\FeeSyncController;
use Illuminate\Support\Facades\Route;

// 党费系统对接（外部系统推送，令牌鉴权）。
Route::post('/fee/sync', [FeeSyncController::class, 'sync'])
    ->name('fee.sync');
