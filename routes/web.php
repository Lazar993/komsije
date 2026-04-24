<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\Auth\InviteRegistrationController;
use App\Http\Controllers\Web\SetSiteLocaleController;
use App\Http\Controllers\Web\Portal\AnnouncementController;
use App\Http\Controllers\Web\Portal\BuildingContextController;
use App\Http\Controllers\Web\Portal\DashboardController;
use App\Http\Controllers\Web\Portal\ProfileController;
use App\Http\Controllers\Web\Portal\TicketController;
use App\Http\Middleware\EnsurePortalBuildingContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return Auth::check()
		? redirect()->route('portal.dashboard')
		: redirect()->route('login');
});

Route::post('locale', SetSiteLocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function (): void {
	Route::get('invite/{token}', [InviteRegistrationController::class, 'show'])->name('invite.show');
	Route::post('invite/{token}', [InviteRegistrationController::class, 'store'])->name('invite.store');
	Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
	Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
	Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

	Route::prefix('portal')->name('portal.')->group(function (): void {
		Route::get('/', DashboardController::class)->name('dashboard');
		Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
		Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
		Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
		Route::post('buildings/{building}/switch', BuildingContextController::class)->name('buildings.switch');

		Route::middleware(EnsurePortalBuildingContext::class)->group(function (): void {
			Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
			Route::get('tickets/create', [TicketController::class, 'create'])->name('tickets.create');
			Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
			Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
			Route::get('tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
			Route::put('tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
			Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comments.store');

			Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
			Route::get('announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
			Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
			Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
			Route::get('announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
			Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
		});
	});
});
