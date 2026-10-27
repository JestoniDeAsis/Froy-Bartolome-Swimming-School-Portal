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

// New-device verification (shown after a correct password from an unrecognized browser)
Route::get('/login/verify-device', [LoginController::class, 'showVerifyDevice'])->name('login.verify-device');
Route::post('/login/verify-device', [LoginController::class, 'verifyDevice']);
Route::post('/login/verify-device/resend', [LoginController::class, 'resendDeviceCode']);


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
use App\Http\Controllers\Auth\ForgotUsernameController;

Route::get('/forgot-username', [ForgotUsernameController::class, 'show'])->name('forgot-username.show');
Route::post('/forgot-username/send', [ForgotUsernameController::class, 'send'])->name('forgot-username.send');


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
Route::post('/profile/password/verify', [ProfileController::class, 'verifyCurrent'])
    ->middleware('auth');


use App\Http\Controllers\CourseController;

Route::get('/courses/{course_id}', [CourseController::class, 'show'])->middleware('auth');
Route::post('/courses', [CourseController::class, 'store'])->middleware('auth');
Route::put('/courses/{course_id}', [CourseController::class, 'update'])->middleware('auth');
Route::get('/courses/{course_id}/stats', [CourseController::class, 'stats']);

use App\Http\Controllers\CoachSkillsAssessmentController;

Route::get('/coach/skills-assessment/data', [CoachSkillsAssessmentController::class, 'data'])->middleware('auth');
Route::put('/coach/skills-assessment/{enrollment}', [CoachSkillsAssessmentController::class, 'save'])->middleware('auth')->whereNumber('enrollment');
Route::post('/coach/skills-assessment/absent',[CoachSkillsAssessmentController::class, 'markAbsent'])->middleware('auth');
Route::post('/coach/skills-assessment/remove-mark', [CoachSkillsAssessmentController::class, 'removeMark']);


use App\Http\Controllers\AthleteRecordController;

Route::get('/coach/athlete-records/data', [AthleteRecordController::class, 'data'])->middleware('auth');
Route::get('/coach/athlete-records/leaderboard', [AthleteRecordController::class, 'leaderboard'])->middleware('auth');
Route::get('/coach/athlete-records/athletes/{user}', [AthleteRecordController::class, 'athlete'])->middleware('auth')->whereNumber('user');
Route::post('/coach/athlete-records', [AthleteRecordController::class, 'store'])->middleware('auth');
Route::delete('/coach/athlete-records/{record}', [AthleteRecordController::class, 'destroy'])->middleware('auth')->whereNumber('record');

use App\Http\Controllers\AthleteDashboardController;

Route::get('/athlete/records/data', [AthleteDashboardController::class, 'mine'])->middleware('auth');
Route::get('/athlete/records/leaderboard', [AthleteDashboardController::class, 'leaderboard'])->middleware('auth');

use App\Http\Controllers\CoachController;
 
Route::get('/coaches/data', [CoachController::class, 'data'])->middleware('auth');
Route::post('/coaches/account-preview', [CoachController::class, 'accountPreview'])->middleware('auth');
Route::post('/coaches', [CoachController::class, 'store'])->middleware('auth');
Route::put('/coaches/{id}', [CoachController::class, 'update'])->middleware('auth')->whereNumber('id');
Route::get('/coaches/{id}/profile', [CoachController::class, 'show'])->whereNumber('id');

use App\Http\Controllers\AdminAttendanceController;
 
Route::get('/admin/attendance/data', [AdminAttendanceController::class, 'data'])->middleware('auth');
Route::get('/admin/attendance/student-logs', [AdminAttendanceController::class, 'studentLogs'])->middleware('auth');
Route::post('/admin/attendance/coaches/time-in', [AdminAttendanceController::class, 'coachTimeIn'])->middleware('auth');
Route::post('/admin/attendance/coaches/time-out', [AdminAttendanceController::class, 'coachTimeOut'])->middleware('auth');
Route::post('/admin/attendance/coaches/absent', [AdminAttendanceController::class, 'coachAbsent'])->middleware('auth');
Route::get('/admin/attendance/athlete-logs', [AdminAttendanceController::class, 'athleteLogs'])->middleware('auth');
Route::get('/admin/attendance/coach-logs', [AdminAttendanceController::class, 'coachLogs'])->middleware('auth');
Route::post('/admin/attendance/records', [AdminAttendanceController::class, 'record'])->middleware('auth');
Route::get('/admin/attendance/handling-coaches', [AdminAttendanceController::class, 'handlingCoaches'])->middleware('auth');

use App\Http\Controllers\CoachAttendanceController;

Route::middleware('auth')->prefix('coach/attendance')->group(function () {
    Route::get('/data', [CoachAttendanceController::class, 'data']);
    Route::get('/student-logs', [CoachAttendanceController::class, 'studentLogs']);
    Route::get('/my-logs', [CoachAttendanceController::class, 'myLogs']);

    Route::post('/students/time-in', [CoachAttendanceController::class, 'studentTimeIn']);
    Route::post('/students/time-out', [CoachAttendanceController::class, 'studentTimeOut']);
    Route::post('/students/expecting', [CoachAttendanceController::class, 'studentExpecting']);
    Route::post('/students/time-in-late', [CoachAttendanceController::class, 'studentTimeInLate']);
    Route::post('/students/absent', [CoachAttendanceController::class, 'studentAbsent']);

    Route::post('/self/time-in', [CoachAttendanceController::class, 'selfTimeIn']);
    Route::post('/self/time-out', [CoachAttendanceController::class, 'selfTimeOut']);
    Route::post('/self/cancel-time-in', [CoachAttendanceController::class, 'selfCancelTimeIn']);
    Route::post('/self/cancel-time-out', [CoachAttendanceController::class, 'selfCancelTimeOut']);
});

use App\Http\Controllers\AdminAthleteController;

Route::post('/athletes', [AdminAthleteController::class, 'store'])->middleware('auth');
Route::post('/athletes/{user}/revoke', [AdminAthleteController::class, 'revoke'])->middleware('auth')->whereNumber('user');
Route::get('/athletes/leaderboard', [AdminAthleteController::class, 'leaderboard'])->middleware('auth');
Route::post('/athletes/{user}/retire', [AdminAthleteController::class, 'retire']);
Route::post('/athletes/{user}/restore', [AdminAthleteController::class, 'restore']);

use App\Http\Controllers\StudentAttendanceController;
 
Route::get('/student/attendance/data', [StudentAttendanceController::class, 'data'])->middleware('auth');

use App\Http\Controllers\ApplicantDashboardController;

Route::get('/applicant/courses', [ApplicantDashboardController::class, 'courses'])
    ->middleware('auth')
    ->name('applicant.courses');

use App\Http\Controllers\PaymentHistoryController;

Route::get('/payments/data', [PaymentHistoryController::class, 'data'])->middleware('auth');

use App\Http\Controllers\SalesInvoiceController;

Route::get('/sales/data', [SalesInvoiceController::class, 'data'])->middleware('auth')->name('sales.data');
Route::post('/sales/enrollments/{enrollment}/payments', [SalesInvoiceController::class, 'recordPayment'])->middleware('auth')->whereNumber('enrollment')->name('sales.payments.store');
Route::put('/sales/payments/{payment}', [SalesInvoiceController::class, 'updatePayment'])->middleware('auth')->name('sales.payments.update');
Route::post('/sales/athlete-payments', [SalesInvoiceController::class, 'recordAthletePayment'])->middleware('auth');
Route::put('/sales/athlete-payments/{payment}', [SalesInvoiceController::class, 'updateAthletePayment'])->middleware('auth')->whereNumber('payment');
Route::post('/sales/athlete-fees', [SalesInvoiceController::class, 'storeAthleteFee'])->middleware('auth');
Route::put('/sales/athlete-fees/{fee}', [SalesInvoiceController::class, 'updateAthleteFee'])->middleware('auth')->whereNumber('fee');
Route::delete('/sales/athlete-fees/{fee}', [SalesInvoiceController::class, 'deleteAthleteFee'])->middleware('auth')->whereNumber('fee');
Route::post('/sales/payments/{payment}/archive', [SalesInvoiceController::class, 'archivePayment'])->middleware('auth')->whereNumber('payment')->name('sales.payments.archive');
Route::post('/sales/payments/{payment}/restore', [SalesInvoiceController::class, 'restorePayment'])->middleware('auth')->whereNumber('payment')->name('sales.payments.restore');
Route::post('/sales/athlete-payments/{payment}/archive', [SalesInvoiceController::class, 'archiveAthletePayment'])->middleware('auth')->whereNumber('payment');
Route::post('/sales/athlete-payments/{payment}/restore', [SalesInvoiceController::class, 'restoreAthletePayment'])->middleware('auth')->whereNumber('payment');

use App\Http\Controllers\AdminGraduationController;

Route::post('/graduation/{enrollment}/graduating', [AdminGraduationController::class, 'markGraduating'])->middleware('auth')->whereNumber('enrollment');
Route::post('/graduation/{enrollment}/certificate', [AdminGraduationController::class, 'uploadCertificate'])->middleware('auth')->whereNumber('enrollment');
Route::post('/graduation/{enrollment}/graduate', [AdminGraduationController::class, 'graduate'])->middleware('auth')->whereNumber('enrollment');
Route::post('/graduation/{enrollment}/ready', [AdminGraduationController::class, 'moveBackToReady'])->middleware('auth')->whereNumber('enrollment');
Route::get('/graduation/data', [AdminGraduationController::class, 'data'])->middleware('auth');
Route::post('/graduation/{enrollment}/mark-ready', [AdminGraduationController::class, 'markReady']);

use App\Http\Controllers\CertificateController;
Route::get('/certificates/{enrollment}', [CertificateController::class, 'show'])->middleware('auth')->whereNumber('enrollment');

use App\Http\Controllers\AnnouncementController;
 
Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('auth');
Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('auth');
Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->middleware('auth');

Route::get('/announcements/feed', [AnnouncementController::class, 'feed'])->middleware('auth');

use App\Http\Controllers\RegistersController;

Route::post('/registers/enroll', [RegistersController::class, 'enroll'])->middleware('auth');
Route::put('/registers/students/{enrollment}', [RegistersController::class, 'updateCourse'])->middleware('auth');
// Route::post('/registers/students/{enrollment}/payments', [RegistersController::class, 'addPayment'])->middleware('auth');
Route::post('/registers/athletes', [RegistersController::class, 'addAthletes'])->middleware('auth');
Route::post('/registers/athletes/{user}/revoke', [RegistersController::class, 'revokeAthlete'])->middleware('auth')->whereNumber('user');
Route::delete('/registers/students/{enrollment}', [RegistersController::class, 'destroy']);
Route::delete('/registers/applicants/{user}', [RegistersController::class, 'destroyApplicant']);
Route::post('/registers/students/{enrollment}/drop', [RegistersController::class, 'drop'])->middleware('auth')->whereNumber('enrollment');
Route::post('/registers/students/{enrollment}/reinstate', [RegistersController::class, 'reinstate'])->middleware('auth')->whereNumber('enrollment');




Route::get('/tmpcheck', fn () => [
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'sys_temp_dir'   => sys_get_temp_dir(),
    'writable'       => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()),
    'file_uploads'   => ini_get('file_uploads'),
    'php_ini'        => php_ini_loaded_file(),
]);