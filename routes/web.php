<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/admin/invoice/pdf/{invoice}', 'PdfController@invoice')->name('pdf.invoice');
Route::get('/admin/expense/pdf/{expense}', 'PdfController@expense')->name('pdf.expense');
Route::get('/admin/invoices/pdf', 'PdfController@invoices')->name('pdf.invoices');
Route::view('admin', 'admin.app');
Route::get('admin/{any}', function () {
	return view('admin.app');
})->where('any', '.*');
