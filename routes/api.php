<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\PrestataireController;
use App\Models\Category;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ReportAdminController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\ReportController;

// ─── Public ───────────────────────────────────
Route::get('/categories', fn() => response()->json(Category::all()));

// Public storage files (message attachments) — bypasses the storage symlink
// so this works with `php artisan serve` as well as Docker/Apache.
Route::get('/files/{path}', function (string $path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $path = ltrim($path, '/');
    if (!$disk->exists($path)) {
        abort(404);
    }
    return response()->file($disk->path($path), ['Cache-Control' => 'public, max-age=3600']);
})->where('path', '.*');

// ─── Auth (public) ────────────────────────────
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Password reset (public)
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('/reset-password',  [PasswordResetController::class, 'reset']);
});

// Google OAuth (browser redirect flow)
Route::get('/auth/google',          [SocialAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'callback']);

// ─── Routes protégées (token requis) ──────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',             [AuthController::class, 'logout']);
    Route::get('/me',                  [AuthController::class, 'me']);

    // User profile
    Route::put('/user/profile',        [UserController::class, 'updateProfile']);
    Route::put('/user/password',       [UserController::class, 'updatePassword']);
    Route::delete('/user',             [UserController::class, 'destroy']);

    // Notifications
    Route::get('/notifications',                 [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count',     [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read',            [NotificationController::class, 'markRead']);

    // Demandes
    Route::get('/demandes',           [DemandeController::class, 'index']);
    Route::post('/demandes',          [DemandeController::class, 'store']);
    Route::get('/demandes/recues',    [DemandeController::class, 'recues']); // before {id}
    Route::get('/demandes/{id}',      [DemandeController::class, 'show']);
    Route::put('/demandes/{id}',      [DemandeController::class, 'update']);
    Route::delete('/demandes/{id}',   [DemandeController::class, 'destroy']);

    // Offres
    Route::post('/offres',                        [OffreController::class, 'store']);
    Route::put('/offres/{id}/statut',             [OffreController::class, 'updateStatut']);
    Route::put('/offres/{id}/negocier',           [OffreController::class, 'negocier']);
    Route::put('/offres/{id}',                    [OffreController::class, 'update']);
    Route::delete('/offres/{id}',                 [OffreController::class, 'destroy']);
    Route::get('/offres/mes-offres',              [OffreController::class, 'myOffres']);
    Route::get('/demandes/{id}/offres',           [OffreController::class, 'offresByDemande']);

    // Avis
    Route::post('/avis',                          [AvisController::class, 'store']);
    Route::get('/prestataires/{id}/avis',         [AvisController::class, 'avisByPrestataire']);

    // Prestataires
    Route::get('/prestataires',                   [PrestataireController::class, 'index']);
    Route::get('/prestataires/{id}/stats',        [PrestataireController::class, 'stats']);
    Route::get('/prestataires/{id}',              [PrestataireController::class, 'show']);
    Route::match(['post', 'put'], '/prestataires/profile', [PrestataireController::class, 'updateProfile']);

    // ─── Messagerie (tous les utilisateurs) ───────
    Route::get('/users/search',                 [UserController::class, 'search']);
    Route::post('/reports',                      [ReportController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/conversations',                [ConversationController::class, 'index']);
    Route::post('/conversations',               [ConversationController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/conversations/unread-count',   [ConversationController::class, 'unreadCount']); // before {id}
    Route::get('/conversations/{id}',           [ConversationController::class, 'show']);
    Route::get('/conversations/{id}/messages',  [ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store'])->middleware('throttle:40,1');

});

// ─── Admin (token + admin role required) ──────
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    Route::get('/stats', [AdminDashboardController::class, 'stats']);

    // Users
    Route::get('/users',                 [UserAdminController::class, 'index']);
    Route::get('/users/{id}',            [UserAdminController::class, 'show']);
    Route::put('/users/{id}',            [UserAdminController::class, 'update']);
    Route::put('/users/{id}/role',       [UserAdminController::class, 'setRole']);
    Route::put('/users/{id}/suspend',    [UserAdminController::class, 'suspend']);
    Route::put('/users/{id}/reactivate', [UserAdminController::class, 'reactivate']);
    Route::put('/users/{id}/verify',     [UserAdminController::class, 'verifyPrestataire']);
    Route::delete('/users/{id}',         [UserAdminController::class, 'destroy']);

    // Content moderation
    Route::get('/demandes',              [ModerationController::class, 'demandes']);
    Route::put('/demandes/{id}/statut',  [ModerationController::class, 'setDemandeStatut']);
    Route::delete('/demandes/{id}',      [ModerationController::class, 'destroyDemande']);
    Route::get('/offres',                [ModerationController::class, 'offres']);
    Route::delete('/offres/{id}',        [ModerationController::class, 'destroyOffre']);
    Route::get('/avis',                  [ModerationController::class, 'avis']);
    Route::delete('/avis/{id}',          [ModerationController::class, 'destroyAvis']);

    // Categories
    Route::get('/categories',            [CategoryAdminController::class, 'index']);
    Route::post('/categories',           [CategoryAdminController::class, 'store']);
    Route::put('/categories/{id}',       [CategoryAdminController::class, 'update']);
    Route::delete('/categories/{id}',    [CategoryAdminController::class, 'destroy']);

    // Announcements
    Route::get('/announcements',         [AnnouncementController::class, 'index']);
    Route::post('/announcements',        [AnnouncementController::class, 'store']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    // Reports + audit log
    Route::get('/reports',               [ReportAdminController::class, 'index']);
    Route::put('/reports/{id}',          [ReportAdminController::class, 'update']);
    Route::get('/audit-logs',            [AuditLogController::class, 'index']);
});