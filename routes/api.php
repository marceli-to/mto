<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Api routes
 */

Route::middleware('auth:api')->group(function() {
    
    /**
     * Client routes
     */

    Route::get('clients/get', 'Api\ClientController@get');
    Route::post('client/create', 'Api\ClientController@store');
    Route::get('client/edit/{client}', 'Api\ClientController@edit');
    Route::post('client/update/{client}', 'Api\ClientController@update');
    Route::get('client/clone/{client}', 'Api\ClientController@clone');
    Route::get('client/status/{client}', 'Api\ClientController@status');
    Route::delete('client/destroy/{client}', 'Api\ClientController@destroy');
    Route::get('client/unique/acronym/{string}', 'Api\ClientController@uniqueAcronym');

    /**
     * Contact routes
     */

    Route::get('contacts/get/{id}', 'Api\ContactController@get');
    Route::post('contact/create', 'Api\ContactController@store');
    Route::get('contact/edit/{contact}', 'Api\ContactController@edit');
    Route::post('contact/update/{contact}', 'Api\ContactController@update');
    Route::get('contact/clone/{contact}', 'Api\ContactController@clone');
    Route::get('contact/status/{contact}', 'Api\ContactController@status');
    Route::delete('contact/destroy/{contact}', 'Api\ContactController@destroy');

    /**
     * Project routes
     */

    Route::get('projects/get', 'Api\ProjectController@get');
    Route::post('project/create', 'Api\ProjectController@store');
    Route::get('project/edit/{project}', 'Api\ProjectController@edit');
    Route::post('project/update/{project}', 'Api\ProjectController@update');
    Route::get('project/clone/{project}', 'Api\ProjectController@clone');
    Route::get('project/status/{project}', 'Api\ProjectController@status');
    Route::delete('project/destroy/{project}', 'Api\ProjectController@destroy');

    /**
     * Media routes
     */

    Route::post('media/upload','MediaController@upload');
    Route::post('media/upload/document','MediaController@uploadDocument');
    Route::get('media/source/{file}', 'MediaController@source');
    Route::get('media/{file}/{size?}', 'MediaController@resize');

    /**
     * Rates
     */

    Route::get('rates/get', 'Api\RateController@get');

    /**
     * Time routes
     */

    Route::get('times/get', 'Api\TimerController@get');
    Route::get('times/get/byDay', 'Api\TimerController@getByDay');
    Route::post('time/create', 'Api\TimerController@store');
    Route::get('time/edit/{time}', 'Api\TimerController@edit');
    Route::post('time/update/{time}', 'Api\TimerController@update');
    Route::delete('time/destroy/{time}', 'Api\TimerController@destroy');

    /**
     * Invoice routes
     */

    Route::get('invoices/get', 'Api\InvoiceController@get');
    Route::get('invoices/get/states/{invoice}', 'Api\InvoiceController@getStates');
    Route::post('invoice/create', 'Api\InvoiceController@store');
    Route::get('invoice/edit/{invoice}', 'Api\InvoiceController@edit');
    Route::post('invoice/update/{invoice}', 'Api\InvoiceController@update');
    Route::post('invoice/update/state/{invoice}', 'Api\InvoiceStateController@update');
    Route::get('invoice/clone/{invoice}', 'Api\InvoiceController@clone');
    Route::delete('invoice/destroy/{invoice}', 'Api\InvoiceController@destroy');
    Route::delete('invoice/position/destroy/{invoicePosition}', 'Api\InvoicePositionController@destroy');
    Route::get('invoice/states', 'Api\InvoiceStateController@index');


    /**
     * Expense routes
     */

    Route::get('expenses/get', 'Api\ExpenseController@get');
    Route::post('expense/create', 'Api\ExpenseController@store');
    Route::get('expense/edit/{expense}', 'Api\ExpenseController@edit');
    Route::post('expense/update/{expense}', 'Api\ExpenseController@update');
    Route::delete('expense/destroy/{expense}', 'Api\ExpenseController@destroy');

});

/**
 * Auth routes
 */

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
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


