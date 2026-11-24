<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HomeController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
Route::view('/privacy', 'policy', ['title' => 'الخصوصية'])->name('privacy');
Route::view('/terms', 'policy', ['title' => 'الشروط والأحكام'])->name('terms');
Route::view('/cancellation-policy', 'policy', ['title' => 'سياسة الإلغاء'])->name('cancellation');
Route::get('/booking-verification/{reference}', [BookingController::class, 'verify'])->middleware('throttle:30,1')->name('bookings.verify');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', fn () => view('verify-email'))->middleware('throttle:10,1')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard')->with('success', 'تم تأكيد بريدك الإلكتروني.');
    })->middleware(['signed', 'throttle:10,1'])->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'لو البريد متاح، هيجيلك رابط تأكيد جديد.');
    })->middleware('throttle:3,1')->name('verification.send');
});

$authenticatedMiddleware = config('services.tebbi.require_verified_email') ? ['auth', 'verified'] : ['auth'];

Route::middleware($authenticatedMiddleware)->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/bookings/{booking}/card', [BookingController::class, 'card'])->name('bookings.card');
    Route::post('/bookings/preview', [BookingController::class, 'preview'])->middleware('role:patient')->name('bookings.preview');
    Route::post('/bookings', [BookingController::class, 'store'])->middleware('role:patient')->name('bookings.store');
    Route::post('/bookings/{booking}/decision', [BookingController::class, 'decide'])->middleware('role:doctor')->name('bookings.decide');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->middleware('role:patient')->name('bookings.cancel');
    Route::post('/bookings/{booking}/review', [BookingController::class, 'review'])->middleware('role:patient')->name('bookings.review');
    Route::post('/doctor/slots', [BookingController::class, 'addSlot'])->middleware('role:doctor')->name('slots.store');
    Route::get('/admin/accounts', [AdminController::class, 'accounts'])->middleware('role:admin')->name('admin.accounts');
    Route::get('/admin/bookings', [AdminController::class, 'bookings'])->middleware('role:admin')->name('admin.bookings');
    Route::post('/admin/doctors/{doctor}/review', [AdminController::class, 'review'])->middleware('role:admin')->name('admin.doctors.review');
    Route::get('/admin/doctors/{doctor}/document', [AdminController::class, 'document'])->middleware('role:admin')->name('admin.doctors.document');
    Route::post('/admin/accounts/{user}/suspension', [AdminController::class, 'toggleSuspension'])->middleware('role:admin')->name('admin.accounts.suspension');
    Route::post('/admin/reviews/{review}/moderation', [AdminController::class, 'moderateReview'])->middleware('role:admin')->name('admin.reviews.moderation');
});
