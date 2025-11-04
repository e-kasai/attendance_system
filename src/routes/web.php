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
use App\Http\Controllers\Admin\AdminRequestListController;
use App\Http\Controllers\Admin\AdminAttendanceDetailController;
use App\Http\Controllers\Admin\AdminRequestApprovalController;
use App\Http\Controllers\Admin\AdminStaffAttendanceController;


//申請一覧画面（スタッフ、管理者で分岐）
Route::get('/stamp_correction_request/list', function () {
    $user = auth()->user();

    if (! $user) {
        abort(403, 'ログインが必要です。');
    }

    if ($user->role === 'admin') {
        return app(AdminRequestListController::class)->showRequests(request());
    }

    if ($user->role === 'staff') {
        return app(StaffRequestListController::class)->showRequests(request());
    }

    abort(403, 'アクセス権限がありません。');
})->name('requests.index')->middleware(['auth', 'verified']);



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

//勤怠詳細画面
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/attendance')
    ->group(function () {
        Route::get('{id}', [AdminAttendanceDetailController::class, 'showAttendanceDetail'])
            ->name('admin.attendance.detail');
        Route::patch('{id}', [AdminAttendanceDetailController::class, 'updateAttendanceStatus'])
            ->name('admin.attendance.update');
    });

//スタッフ一覧,スタッフ別月次勤怠一覧,CSV出力
Route::middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/admin/staff/list', [AdminStaffAttendanceController::class, 'showStaffList'])->name('admin.staff.index');
        Route::get('/admin/attendance/staff/{id}', [AdminStaffAttendanceController::class, 'showMonthlyAttendances'])
            ->name('admin.staff.attendance.index');
        Route::get('/admin/attendance/staff/{id}/export', [AdminStaffAttendanceController::class, 'exportCsv'])
            ->name('admin.staff.attendance.export');
    });



//修正申請承認画面
Route::middleware(['auth', 'role:admin'])
    ->prefix('stamp_correction_request/approve')
    ->group(function () {
        Route::get('{attendance_correct_request_id}', [AdminRequestApprovalController::class, 'showApprovalPage'])
            ->name('admin.request.approve.show');

        Route::patch('{attendance_correct_request_id}', [AdminRequestApprovalController::class, 'approveUpdatedRequest'])
            ->name('admin.request.approve.update');
    });
