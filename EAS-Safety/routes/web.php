<?php
use Illuminate\Support\Facades\Route;
/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//$router->get('/', function () use ($router) {
    $router->group(['prefix' => 'api'], function () use ($router) {
        $router->get('testData',  ['uses' => 'ExampleController@testData']);
        $router->get('callCurl',  ['uses' => 'MiddlewareController@call_curl']);
        $router->get('getToken',  ['uses' => 'ZohoController@getToken']);
        $router->get('getAllRecords',  ['uses' => 'ZohoController@getAllRecords']);
        $router->get('getRecords',  ['uses' => 'ZohoController@getRecords']);
        $router->get('getZohoRequest',  ['uses' => 'ZohoController@getZohoRequest']);
        $router->get('searchRecords',  ['uses' => 'ZohoController@searchUsersByCriteria']);
        $router->get('getRequestIAS',  ['uses' => 'MiddlewareController@getCurlRequest']);
        $router->get('uploadImageWebdev',  ['uses' => 'MiddlewareController@uploadImageWebdev']);
        $router->get('saveImage',  ['uses' => 'MiddlewareController@saveImage']);
});
