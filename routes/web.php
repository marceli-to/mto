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

// Protected routes
Route::middleware('auth')->group(function () {
    // PDF routes
    Route::get('/invoice/qr', 'PdfController@qr')->name('pdf.qr');
    Route::get('/invoice/pdf/{invoice}', 'PdfController@invoice')->name('pdf.invoice');
    Route::get('/invoices/pdf', 'PdfController@invoices')->name('pdf.invoices');
    Route::get('/expense/pdf/{expense}', 'PdfController@expense')->name('pdf.expense');
    Route::get('/expenses/pdf', 'PdfController@expenses')->name('pdf.expenses');
    Route::get('/quote/pdf/{quote}', 'PdfController@quote')->name('pdf.quote');

    // SPA catch-all
    Route::view('/', 'spa.app');
    Route::get('/{any}', function () {
        return view('spa.app');
    })->where('any', '.*');
});
