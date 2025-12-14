<?php

use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth routes
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// PDF routes
Route::get('/admin/invoice/qr', 'PdfController@qr')->name('pdf.qr');
Route::get('/admin/invoice/pdf/{invoice}', 'PdfController@invoice')->name('pdf.invoice');
Route::get('/admin/invoices/pdf', 'PdfController@invoices')->name('pdf.invoices');
Route::get('/admin/expense/pdf/{expense}', 'PdfController@expense')->name('pdf.expense');
Route::get('/admin/expenses/pdf', 'PdfController@expenses')->name('pdf.expenses');

// Admin SPA (protected)
Route::middleware('auth')->group(function () {
    Route::view('admin', 'admin.app');
    Route::get('admin/{any}', function () {
        return view('admin.app');
    })->where('any', '.*');
});
