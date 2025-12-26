<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\Admin\AdminAttendanceController;
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

/*
|--------------------------------------------------------------------------
| 一般ユーザー用ルート (要ログイン)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録画面 (打刻) - FN018, FN019, FN020, FN021, FN022
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/rest-start', [AttendanceController::class, 'restStart'])->name('attendance.rest-start');
    Route::post('/attendance/rest-end', [AttendanceController::class, 'restEnd'])->name('attendance.rest-end');

    // 勤怠一覧画面 (月次) - FN023, FN024, FN025
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // 勤怠詳細画面 / 修正申請 - FN026, FN027, FN028, FN029, FN030
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceCorrectionController::class, 'store'])->name('attendance.correction.store');

    // 申請一覧画面 (自分の申請) - FN031, FN032, FN033
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionController::class, 'myRequestList'])->name('correction.request.list');
});

/*
|--------------------------------------------------------------------------
| 管理者ユーザー用ルート (要管理者権限)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'can:admin'])->group(function () {

    // 勤怠一覧画面 (日次) - FN034, FN035, FN036
    Route::get('/attendance/list', [AdminAttendanceController::class, 'dailyList'])->name('admin.attendance.list');

    // 勤怠詳細画面 (管理者用・直接修正) - FN037, FN038, FN039, FN040
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'detail'])->name('admin.attendance.detail');
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    // スタッフ一覧画面 - FN041, FN042
    Route::get('/staff/list', [StaffController::class, 'index'])->name('admin.staff.list');

    // スタッフ別勤怠一覧画面 (月次) - FN043, FN044, FN045, FN046
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffMonthlyList'])->name('admin.attendance.staff.list');
    Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportCsv'])->name('admin.attendance.staff.csv');

    // 申請一覧画面 (全スタッフの申請) - FN047, FN048, FN049
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionController::class, 'adminRequestList'])->name('admin.correction.list');

    // 修正申請承認画面 / 承認処理 - FN050, FN051
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceCorrectionController::class, 'showApprove'])->name('admin.correction.show_approve');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceCorrectionController::class, 'approve'])->name('admin.correction.approve');
});