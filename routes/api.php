<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceStateController;
use App\Http\Controllers\Api\InvoicePositionController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});

/**
 * Api routes
 */

Route::middleware('auth:sanctum')->group(function() {

  /**
   * Dashboard routes
   */

  Route::get('dashboard/get', [DashboardController::class, 'get']);

  /**
   * Client routes
   */

  Route::get('clients/get', [ClientController::class, 'get']);
  Route::post('client/create', [ClientController::class, 'store']);
  Route::get('client/edit/{client}', [ClientController::class, 'edit']);
  Route::post('client/update/{client}', [ClientController::class, 'update']);
  Route::get('client/duplicate/{client}', [ClientController::class, 'duplicate']);
  Route::get('client/status/{client}', [ClientController::class, 'status']);
  Route::delete('client/destroy/{client}', [ClientController::class, 'destroy']);
  Route::get('client/unique/acronym/{string}', [ClientController::class, 'uniqueAcronym']);

  /**
   * Contact routes
   */

  Route::get('contacts/get/{id}', [ContactController::class, 'get']);
  Route::post('contact/create', [ContactController::class, 'store']);
  Route::get('contact/edit/{contact}', [ContactController::class, 'edit']);
  Route::post('contact/update/{contact}', [ContactController::class, 'update']);
  Route::delete('contact/destroy/{contact}', [ContactController::class, 'destroy']);

  /**
   * Project routes
   */

  Route::get('projects/get', [ProjectController::class, 'get']);
  Route::post('project/create', [ProjectController::class, 'store']);
  Route::get('project/edit/{project}', [ProjectController::class, 'edit']);
  Route::post('project/update/{project}', [ProjectController::class, 'update']);
  Route::get('project/duplicate/{project}', [ProjectController::class, 'duplicate']);
  Route::get('project/status/{project}', [ProjectController::class, 'status']);
  Route::delete('project/destroy/{project}', [ProjectController::class, 'destroy']);

  /**
   * Rates
   */

  Route::get('rates/get', [RateController::class, 'get']);

  /**
   * Invoice routes
   */

  Route::get('invoices/get', [InvoiceController::class, 'get']);
  Route::post('invoice/create', [InvoiceController::class, 'store']);
  Route::get('invoice/edit/{invoice}', [InvoiceController::class, 'edit']);
  Route::post('invoice/update/{invoice}', [InvoiceController::class, 'update']);
  Route::post('invoice/update/state/{invoice}', [InvoiceStateController::class, 'update']);
  Route::get('invoice/duplicate/{invoice}', [InvoiceController::class, 'duplicate']);
  Route::delete('invoice/destroy/{invoice}', [InvoiceController::class, 'destroy']);
  Route::delete('invoice/position/destroy/{invoicePosition}', [InvoicePositionController::class, 'destroy']);
  Route::get('invoice/states', [InvoiceStateController::class, 'index']);

  /**
   * Expense routes
   */

  Route::get('expenses/get', [ExpenseController::class, 'get']);
  Route::post('expense/create', [ExpenseController::class, 'store']);
  Route::get('expense/edit/{expense}', [ExpenseController::class, 'edit']);
  Route::post('expense/update/{expense}', [ExpenseController::class, 'update']);
  Route::delete('expense/destroy/{expense}', [ExpenseController::class, 'destroy']);

  /**
   * Upload routes
   */

  Route::post('upload/temp', [UploadController::class, 'upload']);
  Route::delete('upload/revert', [UploadController::class, 'revert']);

});

/**
 * Fallback if no route is defined
 */

Route::fallback(function(){
  return response()->json(
    ['message' => 'Page Not Found. If error persists, contact m@marceli.to'],
    404
  );
});
