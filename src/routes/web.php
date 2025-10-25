<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Staff\StaffAttendanceController;
use App\Http\Controllers\Staff\StaffAttendanceDetailController;
use App\Http\Controllers\Staff\StaffAttendanceListController;
use App\Http\Controllers\Staff\StaffRequestListController;
use App\Http\Controllers\Admin\AdminAttendanceListController;




//スタッフのルート

//ユーザー登録
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register.show');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

//メール認証案内ページ
Route::get('/email/verify', function () {
    return view('auth.verify_email');
})->middleware('auth')->name('verification.notice');

//メール認証リンクアクセス
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance.create');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

//認証メール再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');


//勤怠登録ページ
Route::prefix('attendance')
    ->middleware(['auth', 'verified', 'role:staff'])
    ->group(function () {
        Route::get('/', [StaffAttendanceController::class, 'showAttendanceStatus'])->name('attendance.create');
        Route::post('/', [StaffAttendanceController::class, 'storeAttendanceStatus'])->name('attendance.store');
        Route::get('/list', [StaffAttendanceListController::class, 'showAttendances'])->name('attendances.index');
        Route::get('/detail/{id}', [StaffAttendanceDetailController::class, 'showAttendanceDetail'])->name('attendance.detail');
        Route::patch('/detail/{id}', [StaffAttendanceDetailController::class, 'updateAttendanceStatus'])->name('attendance.update');
    });


//申請一覧画面
Route::middleware(['auth', 'verified', 'role:staff'])
    ->group(function () {
        Route::get('/stamp_correction_request/list', [StaffRequestListController::class, 'showRequests'])->name('requests.index');
    });




//管理者のルート

//ログイン
Route::get('/admin/login', function () {
    return view('auth.admin.login', ['role' => 'admin']);
})->middleware('guest')->name('admin.login');



//勤怠一覧表示
Route::middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/admin/attendance/list', [AdminAttendanceListController::class, 'showDailyAttendances'])->name('admin.attendances.index');
    });

//申請一覧画面
// Route::middleware(['auth', 'role:admin'])
//     ->group(function () {
//         Route::get('/stamp_correction_request/list', [AdminRequestListController::class, 'showRequests'])->name('requests.index');
//     });
