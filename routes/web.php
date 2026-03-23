<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\StoreController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/punti-vendita', [StoreController::class, 'index'])->name('stores.index');
Route::get('/api/stores', [StoreController::class, 'search'])->name('stores.search');
// PDF regolamento
Route::get('/regolamento', fn () => redirect(asset('pdf/regolamento.pdf')))->name('regolamento');
// Legal documents (LegalBlink)
Route::get('/privacy', fn () => redirect()->away('https://app.legalblink.it/api/documents/620230752e565d002878b88b/privacy-policy-per-siti-web-o-e-commerce-it'))->name('privacy');
Route::get('/cookie-policy', fn () => redirect()->away('https://app.legalblink.it/api/documents/620230752e565d002878b88b/cookie-policy-it'))->name('cookie.policy');

// Guest only
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/registrati', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registrati', [AuthController::class, 'register'])->middleware('throttle:auth');

    // Password reset
    Route::get('/password/recupera', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/recupera', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reimposta/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/password/reimposta', [PasswordResetController::class, 'reset'])->name('password.update');
});

// Email verification (no auth required — user clicks link from email)
Route::get('/email/verifica/{id}/{hash}', function (int $id, string $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Link di verifica non valido.');
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect()->route('login')->with('success', 'Email verificata! Ora puoi accedere e giocare.');
})->middleware('signed')->name('verification.verify');

// Auth required (no email verification needed)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Resend verification
    Route::get('/email/verifica', [AuthController::class, 'verificationNotice'])->name('verification.notice');
    Route::post('/email/reinvia', [AuthController::class, 'verificationResend'])->name('verification.resend')->middleware('throttle:6,1');
});

// Auth + verified email required
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/gioca-ora', [GameController::class, 'show'])->name('game.show');
    Route::post('/gioca-ora', [GameController::class, 'play'])->name('game.play')->middleware('throttle:play');
    Route::get('/loading', [GameController::class, 'loading'])->name('game.loading');
    Route::get('/hai-vinto', [GameController::class, 'won'])->name('game.won');
    Route::get('/non-hai-vinto', [GameController::class, 'lost'])->name('game.lost');
});

// Admin-only: serve private receipt images
Route::get('/admin/receipts/{path}', function (string $path) {
    abort_unless(Storage::exists("receipts/{$path}"), 404);

    return Storage::response("receipts/{$path}");
})->where('path', '.*')->middleware('auth:admin')->name('admin.receipt');
