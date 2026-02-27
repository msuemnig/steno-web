<?php

use App\Http\Controllers\Auth\ExtensionAuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public
Route::get('/', fn () => Inertia::render('Welcome'));
Route::get('/pricing', fn () => Inertia::render('Pricing'));

// Google OAuth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// Extension auth (requires authenticated user)
Route::get('/auth/extension-login', [ExtensionAuthController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('extension.login');
Route::post('/auth/extension-token', [ExtensionAuthController::class, 'generateToken'])
    ->middleware(['auth', 'verified']);

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');

    // Teams
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::put('/teams/{team}/switch', [TeamController::class, 'switchTeam'])->name('teams.switch');

    // Team members
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('team-members.destroy');
    Route::put('/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('team-members.update');

    // Team invitations
    Route::post('/teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('team-invitations.store');
    Route::delete('/team-invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('team-invitations.destroy');

    // Billing
    Route::get('/billing', [BillingController::class, 'show'])->name('billing');
    Route::post('/billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::post('/billing/change-plan', [BillingController::class, 'changePlan'])->name('billing.change-plan');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

// Team invitation acceptance (can be unauthenticated)
Route::get('/team-invitations/{token}/accept', [TeamInvitationController::class, 'accept'])
    ->name('team-invitations.accept');
