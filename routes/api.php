<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\TicketCommentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Middleware\EnsureBuildingContext;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);

    Route::get('buildings', [BuildingController::class, 'index']);
    Route::post('buildings', [BuildingController::class, 'store']);
    Route::get('buildings/{building}', [BuildingController::class, 'show']);
    Route::put('buildings/{building}', [BuildingController::class, 'update']);

    Route::middleware(EnsureBuildingContext::class)->group(function (): void {
        Route::get('apartments', [ApartmentController::class, 'index']);
        Route::post('apartments', [ApartmentController::class, 'store']);
        Route::get('apartments/{apartment}', [ApartmentController::class, 'show']);
        Route::put('apartments/{apartment}', [ApartmentController::class, 'update']);

        Route::get('tickets', [TicketController::class, 'index']);
        Route::post('tickets', [TicketController::class, 'store']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show']);
        Route::put('tickets/{ticket}', [TicketController::class, 'update']);
        Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store']);

        Route::get('announcements', [AnnouncementController::class, 'index']);
        Route::post('announcements', [AnnouncementController::class, 'store']);
        Route::get('announcements/{announcement}', [AnnouncementController::class, 'show']);
        Route::put('announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::post('announcements/{announcement}/read', [AnnouncementController::class, 'markAsRead']);
    });
});
