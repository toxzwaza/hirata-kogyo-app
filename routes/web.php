<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkRecordController;
use App\Http\Controllers\StaffInvoiceController;
use App\Http\Controllers\ClientInvoiceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DrawingController;
use App\Http\Controllers\WorkMethodController;
use App\Http\Controllers\WorkRateController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\DefectTypeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/__debug', function () {
    return response()->json([
        'scheme' => request()->getScheme(),
        'secure' => request()->isSecure(),
        'xfp'    => request()->header('x-forwarded-proto'),
        'host'   => request()->getHost(),
        'url'    => request()->fullUrl(),
    ]);
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// スタッフ用スマホ画面（admin_flgに関係なくログイン可能）
// staff_idパラメータがある場合は自動ログインするため、authミドルウェアは適用しない
// 注意: より具体的なルートを先に定義する必要がある（Route::resource('staff')より前に）
Route::prefix('staff')->name('mobile.')->group(function () {
    // 作業実績登録（スマホ用）
    Route::get('/work-records/create', [WorkRecordController::class, 'createForMobile'])->name('work-records.create');
    Route::post('/work-records', [WorkRecordController::class, 'storeForMobile'])->middleware('auth')->name('work-records.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 作業実績管理
    Route::resource('work-records', WorkRecordController::class);

    // スタッフ請求書管理
    Route::resource('staff-invoices', StaffInvoiceController::class)->except(['edit', 'update']);
    Route::post('/staff-invoices/{staff_invoice}/fix', [StaffInvoiceController::class, 'fix'])->name('staff-invoices.fix');
    Route::post('/staff-invoices/{staff_invoice}/unfix', [StaffInvoiceController::class, 'unfix'])->name('staff-invoices.unfix');
    Route::get('/staff-invoices/{staff_invoice}/pdf', [StaffInvoiceController::class, 'pdf'])->name('staff-invoices.pdf');

    // 客先請求書管理
    Route::resource('client-invoices', ClientInvoiceController::class)->except(['edit', 'update']);
    Route::post('/client-invoices/{client_invoice}/update-adjustment', [ClientInvoiceController::class, 'updateAdjustment'])->name('client-invoices.update-adjustment');
    Route::post('/client-invoices/{client_invoice}/fix', [ClientInvoiceController::class, 'fix'])->name('client-invoices.fix');
    Route::get('/client-invoices/{client_invoice}/pdf', [ClientInvoiceController::class, 'pdf'])->name('client-invoices.pdf');

    // マスタ管理
    Route::resource('clients', ClientController::class);
    Route::resource('drawings', DrawingController::class);
    Route::resource('work-methods', WorkMethodController::class);
    Route::post('work-rates/relink-work-records', [WorkRateController::class, 'relinkWorkRecords'])->name('work-rates.relink-work-records');
    Route::resource('work-rates', WorkRateController::class);
    Route::resource('staff', StaffController::class);
    Route::resource('defect-types', DefectTypeController::class);
});

require __DIR__.'/auth.php';
