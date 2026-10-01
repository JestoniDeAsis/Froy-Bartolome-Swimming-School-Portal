<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });


// ----------------------------------------------------------------
// ----------------------------------------------------------------
use App\Http\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// ----------------------------------------------------------------
// ----------------------------------------------------------------
use App\Http\Controllers\Auth\RegisterController;

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.store');
Route::post('/register/send-code', [RegisterController::class, 'sendCode'])->name('register.send-code');
Route::post('/register/verify-code', [RegisterController::class, 'verifyCode'])->name('register.verify-code');


// ----------------------------------------------------------------
// ----------------------------------------------------------------
use App\Http\Controllers\Auth\ForgotPasswordController;

Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('forgot-password');
Route::post('/forgot-password', [ForgotPasswordController::class, 'updatePassword'])->name('forgot-password.update');
Route::post('/forgot-password/send-code', [ForgotPasswordController::class, 'sendCode'])->name('forgot-password.send-code');
Route::post('/forgot-password/verify-code', [ForgotPasswordController::class, 'verifyCode'])->name('forgot-password.verify-code');


// ----------------------------------------------------------------
// ----------------------------------------------------------------
use App\Http\Controllers\HomeController;

// Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/', [HomeController::class, 'index'])
    ->name('home')
    ->middleware('auth');

Route::get('/navigate/{page}', [HomeController::class, 'navigate'])
    ->name('home.navigate')
    ->middleware('auth');


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChangePasswordController;

Route::post('/profile/update', [ProfileController::class, 'update'])
    ->middleware('auth');
Route::post('/profile/password/verify', [ChangePasswordController::class, 'verifyCurrent'])
    ->middleware('auth');
Route::post('/profile/password/update', [ChangePasswordController::class, 'update'])
    ->middleware('auth');

use App\Http\Controllers\CourseController;

Route::get('/courses/{course_id}', [CourseController::class, 'show'])->middleware('auth');
Route::post('/courses', [CourseController::class, 'store'])->middleware('auth');
Route::put('/courses/{course_id}', [CourseController::class, 'update'])->middleware('auth');

use App\Http\Controllers\SalesInvoiceController;

Route::get('/sales/data', [SalesInvoiceController::class, 'data'])->name('sales.data');
Route::put('/sales/payments/{payment}', [SalesInvoiceController::class, 'updatePayment'])->name('sales.payments.update');

use App\Http\Controllers\AnnouncementController;
 
Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('auth');
Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('auth');
Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->middleware('auth');

Route::get('/announcements/feed', [AnnouncementController::class, 'feed'])->middleware('auth');

use App\Http\Controllers\RegistersController;

Route::post('/registers/enroll', [RegistersController::class, 'enroll'])->middleware('auth');
Route::put('/registers/students/{enrollment}', [RegistersController::class, 'updateCourse'])->middleware('auth');
Route::post('/registers/students/{enrollment}/payments', [RegistersController::class, 'addPayment'])->middleware('auth');