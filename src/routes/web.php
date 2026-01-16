<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminAttendanceCorrectionController;
use App\Http\Controllers\Admin\StaffController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| 公開ルート (ゲスト)
|--------------------------------------------------------------------------
*/

// Fortifyが提供する標準ルート (/register, /login, /logout) を利用します。
// 管理者ログイン画面のみ、要件に合わせてエイリアスを設定。
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->middleware('guest')->name('admin.login');

Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

/*
|--------------------------------------------------------------------------
| 一般ユーザー用ルート (要ログイン)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録 (打刻)
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/rest-start', [AttendanceController::class, 'restStart'])->name('attendance.rest-start');
    Route::post('/attendance/rest-end', [AttendanceController::class, 'restEnd'])->name('attendance.rest-end');

    // 勤怠一覧 (月次)
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // 勤怠詳細 / 修正申請
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceCorrectionController::class, 'store'])->name('attendance.update');

    // 申請一覧 (自分の申請)
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionController::class, 'index'])->name('attendance.application');
});

/*
|--------------------------------------------------------------------------
| 管理者ユーザー用ルート (要管理者権限)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {

    // 勤怠一覧 (日次)
    Route::get('/attendance/list', [AdminAttendanceController::class, 'dailyList'])->name('admin.attendance.list');

    // 勤怠詳細 (管理者用・直接修正)
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'detail'])->name('admin.attendance.detail');
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    // スタッフ一覧
    Route::get('/staff/list', [StaffController::class, 'index'])->name('admin.staff.list');

    // スタッフ別勤怠一覧 (月次) & CSV出力
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffMonthlyList'])->name('admin.attendance.staff.list');
    Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportCsv'])->name('admin.attendance.staff.csv');

    // 申請一覧 (全スタッフの申請)
    Route::get('/admin/stamp_correction_request/list', [AdminAttendanceCorrectionController::class, 'index'])
    ->name('admin.correction.list');

    // 修正申請承認画面 / 承認処理
    Route::get('/admin/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceCorrectionController::class, 'show'])
    ->name('admin.correction.show_approve');
    Route::post('/admin/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceCorrectionController::class, 'approve'])
    ->name('admin.correction.approve');
});