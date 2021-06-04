<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// customer Routes
Route::post('createCustomer',  'CustomerController@createCustomer');
Route::get('testDatabase','CustomerController@testDatabase');
// Webhook routes
Route::post('webhook',  'WebhookController@getWebhook');
Route::get('data', 'WebhookController@data');
Route::get('test', 'WebhookController@test');
Route::post('getZohoWebhook', 'WebhookController@getZohoWebhook');
Route::post('orderwebhook', 'OrderController@saveOrderWebhook');

// Zoho routes
Route::get('getToken', 'ZohoController@getToken');
Route::get('getRecords', 'ZohoController@getRecords');
Route::get('getAllRecords', 'ZohoController@getAllRecords');
Route::post('insertZohoData', 'ZohoController@insertRecords');
Route::get('updateZohoData', 'ZohoController@updateRecords');

//Product controller routes
Route::post('getProductPriceTable',  'ProductController@getProductPriceTable');
Route::post('getProductAddons',  'ProductController@getProductAddons');

//Cart controller routes
Route::post('createCart',  'CartController@createCart');
Route::get('testConnection',  'CartController@testConnection');
Route::get('testRequest',  'CartController@testRequest');
Route::get('getCurrencyRate',  'ProductController@getCurrencyRate');
Route::get('getImagePath',  'ProductController@getImagePath');

//Checkout controller routes
Route::get('getPaymentTerms',  'CheckoutController@getPaymentTerms');
Route::get('getAddresses/{params?}', 'CheckoutController@getAddressesFromSpire')->where('params', '(.*)');
//Route::post('getAddresses', 'CheckoutController@getAddressesFromSpire');

// Order Controller Routes
Route::get('orderRequestToEas','OrderController@getBcOrdersData');

Route::middleware('auth:api')->get('/user', function (Request $request) {

    return $request->user();
});

//$router->get('webhook',  ['uses' => 'WebhookController@getWebhook']);
