<?php
/**
 * @category    Config File
 * @package     Config
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Config 
    |--------------------------------------------------------------------------
    |
    | This value gives, spire api url in return
    |
    */
    'Spire_Api_Url' => env('Spire_Api_Url'),
    'Spire_Customer_Api_Url'=> env('Spire_Customer_Api_Url'),
    'Spire_Address_Api_Url'=> env('Spire_Address_Api_Url'),
	 
    /*
    |--------------------------------------------------------------------------
    | Config 
    |--------------------------------------------------------------------------
    |
    | This value gives, big commerce api url in return
    |
    */
    'BigCommerce_Api_Url' => env('BigCommerce_Api_Url'),
    /*
    |--------------------------------------------------------------------------
    | Config 
    |--------------------------------------------------------------------------
    |
    | This value gives, spire api auth in return
    |
    */
    'Spire_Api_Auth' => env('Spire_Api_Auth'),
    /*
    |--------------------------------------------------------------------------
    | Config 
    |--------------------------------------------------------------------------
    |
    | This value gives, big commerce api auth in return
    |
    */
    'BigCommerce_Api_Auth' => env('BigCommerce_Api_Auth'),
    'BigCommerce_Api_Url2' => env('BigCommerce_Api_Urlv2'),
    'Base_Url' => env('Base_Url'),
    'Auth_User' => env('Auth_User'),
    'Auth_Pass' => env('Auth_Pass'),
    'Default_Image_Url' => env('Default_Image_Url'),
    'Postman_Token' => env('Postman_Token'),
    'APP_URL' => env('APP_URL'),
    'BASE_URL' => env('http://localhost:8000/'),
    'constants' => [
        'start' => '2164',
        'limit' => '100',
        'count' => '2186',
    ]

];
