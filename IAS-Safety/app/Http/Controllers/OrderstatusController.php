<?php

namespace App\Http\Controllers;

use App\Order;
use App\Orderaddress;
use App\Orderitem;
use App\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Log;

class OrderstatusController extends Controller
{

	//save order data bigcommerce to database//
	public function getorderstatus(Request $request,$id)
	{
          
		$bigcOrderId = $id;
		$authorization = config('config.BigCommerce_Api_Auth');
		$url = config('config.BigCommerce_Api_Url2');
        
		$response = Http::withHeaders([
			'accept' => 'application/json',
			'content-type' => 'application/json',
			'x-auth-token' => $authorization
		])->get($url . "/orders/{$bigcOrderId}");

       
		$orderData = $response->json();
		
		if (isset($orderData['id']))	
		{	
	   
		if($orderData['status']=='Awaiting Fulfillment')
		{
			$phasedata='FULFILL';
		}
     
	   if($orderData['status']=='Awaiting Shipment')
		{
			$phasedata='SHIP';
		}
       
        if($orderData['status']=='Awaiting Pickup')
		{
			$phasedata='PICKUP';
		}
		
      if($orderData['status']=='Shipped')
		{
			$phasedata='SHIPPED';
		}		
		 
          
      $response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		 ])->get(config('config.Spire_Api_Url') .'sales/orders/?filter={"udf.bigcommerce_order_id":"' . $orderData['id'] . '"}');
     
		$customerData = $response->json();
		
		print_r($customerData);
		die;

		
	
	}
	
	else{
			return "0";
	
    }
	}
	//save data billing and shipping address bigcommerce to database//
	

}	
