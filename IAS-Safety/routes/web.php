<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
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


$router->group(['prefix' => 'api'], function () use ($router) {
  $router->get('getproduct',  ['uses' => 'ProductController@showAllProduct']);
  $router->get('createProduct',  ['uses' => 'ProductController@createProduct']);
  //$router->post('getProductPriceTable',  ['uses' => 'ProductController@getProductPriceTable']);
  $router->get('saveImage',  ['uses' => 'ProductController@saveImage']);
  $router->get('importProduct',  ['uses' => 'ProductController@importProduct']);
  $router->get('importSpireProduct',  ['uses' => 'ProductController@importSpireProduct']);
  $router->get('createProductBigC',  ['uses' => 'ProductController@createProductBigC']);
  $router->get('getUpdatedProduct',  ['uses' => 'ProductController@getUpdatedProduct']);
  $router->get('checkUpdatedCustomValue',  ['uses' => 'ProductController@checkUpdatedCustomValue']);
  Route::get('/orderstatus/{id}', ['uses' => 'OrderstatusController@getorderstatus']);
  Route::get('getZohoData', ['uses' => 'ZohoController@getZohoData']);
});
$router->post('SaveCustomer', 'ProductController@SaveCustomer');
//Route::post('orderwebhook', [OrderController::class, 'saveOrderWebhook']);
Route::get('/', [OrderController::class, 'getOrders']);
Route::get('/order/{id}', [OrderController::class, 'getOrderDetails']);
Route::get ( '/saveBillingandshippingaddress', [OrderController::class, 'saveBillingandshippingaddress']);
Route::get('/orderAddress', [OrderController::class, 'getOrderBillingAddress']);
Route::get('/orderSippingAddress', [OrderController::class, 'getOrderShippingAddress']);
Route::get('/ordercustomer', [OrderController::class, 'getOrderCustomer']);
Route::post ( '/search', [OrderController::class, 'customerBillingAddressSearch']);
Route::post ( '/shippingsearch', [OrderController::class, 'customerShippingAddressSearch']);
Route::post ( '/orderaligninspire', 'OrderController@orderAligninspire');
Route::put ( '/address_update', 'OrderController@address_update');
Route::put ( '/updateShippingAddress', 'OrderController@updateShippingAddress');
Route::POST ( '/updateshippingaddressonspire', [OrderController::class, 'updateshippingAddressInSpire']);
Route::POST ( '/updatebillingAddressInSpire', [OrderController::class, 'updatebillingAddressInSpire']);
Route::POST( '/address_create_spire', [OrderController::class, 'createAddressInSpire']);
Route::POST( '/create_customer_no', [OrderController::class, 'createCustomerNumberOnSpire']);
Route::get( '/update_customer_no_BC', [OrderController::class, 'updateCustomerNumberOnBC']);
Route::POST ( '/checkcustomerno', [OrderController::class, 'checkcustnospire']);
Route::POST ( '/updatewithoutcustomernumber', [OrderController::class, 'customerupdatewithoutcustomernumber']);





