<?php

namespace App\Http\Controllers;

use App\Order;
use App\Orderaddress;
use App\Orderitem;
use App\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Log;
use App\Http\Controllers\ZohoController as ZohoController;
use App\User;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
	


	public function createBcApiAuthPayload() {
		return $apiPaylaodBC = array(			
			"accessTokenBC"=> config('config.BigCommerce_Api_Auth')
		);
	}

	public function getBcOrdersData()
	{
		Log::info("Order data syncronization process!");
		$currentDateTimeISOFormated = date('c');
		$fiveMinLessDateTimeISOFormated  = date('c', strtotime('-5 minutes'));

		$startDateTime = $this->manageDateTimeForAPI($currentDateTimeISOFormated);
		//$endDateTime = $this->manageDateTimeForAPI($fiveMinLessDateTimeISOFormated);
		$endDateTime = '2021-04-01T20:36:22';
		
		$ApiAuthorizationBC = $this->createBcApiAuthPayload();		
		$queryFlters = "?max_date_created=$startDateTime&min_date_created=$endDateTime&status_id=7";
		$limit = "&limit=250";
		$apiUrlBC = "https://api.bigcommerce.com/stores/z84xkjcnbz/v2/orders/".$queryFlters.$limit;
		
		$apiResponse = $this->getCurlRequest($ApiAuthorizationBC['accessTokenBC'], $apiUrlBC, $method = 'GET');
		//dd($apiResponse);
		if($apiResponse['responseData']['status'] == 200) {		
			$orderData	= $apiResponse['responseData'];
			//echo count($orderData['responseData']);		
			if(count($orderData) > 0) {			
				try {
					foreach($orderData['responseData'] as $order) {						
						$customerIdBC = $order['customer_id'];
						$orderIdBC = $order['id'];
						// define big commerce order status id for awaiting fullfilmenent
						$orderStatusId = 11;
						$orderPayloadBC = array(
							"status_id" => $orderStatusId
						);

						$orderStatusResponseBC = $this->updateOrderStatusOnBC($orderIdBC, $orderPayloadBC);
						if($orderStatusResponseBC) {
							$orderPayloadDB = array(
								"status" => "Awaiting Fulfillment",
								"status_id" => $orderStatusId
							);
							$orderStatusResponseDB = Order::updateOrInsert(['bigc_orderid' => $orderIdBC], $orderPayloadDB);
							Log::info("Order status Update status on DB", array($orderStatusResponseDB));
						} else {
							Log::error("order status not updated on Big Commerce", array($orderStatusResponseBC));
						}


						$customerApiUrlBC = "https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers?include=formfields&id:in=$customerIdBC";
						$customerApiResponse = $this->getCurlRequest($ApiAuthorizationBC['accessTokenBC'], $customerApiUrlBC, $method = 'GET');
						
						$customerDataBC = $customerApiResponse['responseData']['responseData']['data'][0];
						$customerPayloadDB = $this->createCustomerPAyloadForDB($customerDataBC);
						//dd($customerPayloadDB);									
						$customerResponse = Customer::updateOrInsert(['bigc_customer_id' => $order['customer_id']], $customerPayloadDB);
						//dd($customerResponse);	

						$orderPayloadDB = $this->createOrderPayloadForDB($order);
						$shippingPayloadDB = $this->createShippingAddressPayloadForDB($order, $ApiAuthorizationBC['accessTokenBC']);
						$billingPayloadDB = $this->createBillingAddressPayloadForDB($order);
					
						$orderDataResponse = Order::updateOrInsert(["bigc_orderid" => $orderPayloadDB['bigc_orderid']], $orderPayloadDB);
						if($orderDataResponse) {
							$orderItemsResponse = $this->saveOrderItemsPayloadDB($order, $ApiAuthorizationBC['accessTokenBC']);							
							$orderBillingAddressResponse = Orderaddress::updateOrInsert(["bigc_order_id" => $billingPayloadDB['bigc_order_id'], "type"=>$billingPayloadDB['type']], $billingPayloadDB);
							$orderShippingAddressResponse = Orderaddress::updateOrInsert(["bigc_order_id" => $shippingPayloadDB['bigc_order_id'], "type"=>$shippingPayloadDB['type']], $shippingPayloadDB);
						}
					}
				} catch(exception $e){
					echo $e->getMessage();
				}				
			}
			//print_r($apiResponse['responseData']);
		} else if ($apiResponse['responseData']['status'] ==  204) {
			echo "No Content for orders!";
		} else {
			//Log::error($apiResponse['responseData']);
			echo $apiResponse['responseData']['responseData'];
		}
	}

	// create order payload for database to store
	public function createOrderPayloadForDB($orderData = null) {
		//dd($orderData);
		$isCustomerOnSpire = false;
		$isShippingOnSpire = false;
		$isBillingOnSpire = false;
		$creditcard = false;
		return $orderDataPayload = array(
			"base_handling_cost" => $orderData['base_handling_cost'],
			"bigc_orderid" => $orderData['id'],
			"base_shipping_cost" => $orderData['base_shipping_cost'],
			"base_wrapping_cost" => $orderData['base_wrapping_cost'],
			"cart_id" => $orderData['cart_id'],
			"channel_id" => $orderData['channel_id'],
			"coupon_discount" => $orderData['coupon_discount'],
			"currency_code" => $orderData['currency_code'],
			"currency_id" => $orderData['currency_id'],
			"custom_status" => $orderData['custom_status'],
			"customer_id" => $orderData['customer_id'],
			"customer_locale" => $orderData['customer_locale'],
			"customer_message" => $orderData['customer_message'],
			"date_modified" => $orderData['date_created'],
			"date_shipped" => $orderData['date_shipped'],
			"default_currency_code" => $orderData['default_currency_code'],
			"default_currency_id" => $orderData['default_currency_id'],
			"discount_amount" => $orderData['discount_amount'],
			"ebay_order_id" => $orderData['ebay_order_id'],
			"geoip_country" => $orderData['geoip_country'],
			"geoip_country_iso2" => $orderData['geoip_country_iso2'],
			"gift_certificate_amount" => $orderData['gift_certificate_amount'],
			"handling_cost_inc_tax" => $orderData['handling_cost_inc_tax'],
			"handling_cost_tax_class_id" => $orderData['handling_cost_tax_class_id'],
			"payment_method" => $orderData['payment_method'],
			"payment_provider_id" => $orderData['payment_provider_id'],
			"payment_status" => $orderData['payment_status'],
			"shipping_cost_ex_tax" => $orderData['shipping_cost_ex_tax'],
			"shipping_cost_inc_tax" => $orderData['shipping_cost_inc_tax'],
			"shipping_cost_tax" => $orderData['shipping_cost_tax'],
			"shipping_cost_tax_class_id" => $orderData['shipping_cost_tax_class_id'],
			"staff_notes" => $orderData['staff_notes'],
			"status" => $orderData['status'],
			"status_id" => $orderData['status_id'],
			"store_default_currency_code" => $orderData['store_default_currency_code'],
			"store_default_to_transactional_exchange_rate" => $orderData['store_default_to_transactional_exchange_rate'],
			"subtotal_ex_tax" => $orderData['subtotal_ex_tax'],
			"subtotal_inc_tax" => $orderData['subtotal_inc_tax'],
			"subtotal_tax" => $orderData['subtotal_tax'],
			"tax_provider_id" => $orderData['tax_provider_id'],
			"total_ex_tax" => $orderData['total_ex_tax'],
			"total_inc_tax" => $orderData['total_inc_tax'],
			"total_tax" => $orderData['total_tax'],
			"wrapping_cost_ex_tax" => $orderData['wrapping_cost_ex_tax'],
			"wrapping_cost_inc_tax" => $orderData['wrapping_cost_inc_tax'],
			"wrapping_cost_tax" => $orderData['wrapping_cost_tax'],
			"direct_align_with_spire" => ($creditcard || ($isCustomerOnSpire && $isShippingOnSpire && $isBillingOnSpire)) ? true : false,
			"creditcard" => $orderData['payment_method'] == 'On Account' ? true: false
		);
		// dd($orderDataPayload);
	}

	// Get order shipping address from Big Commerce and create payload for database
	public function createShippingAddressPayloadForDB($orderData = null, $ApiAuthorizationBC) {
		$shipingAddressURL = $orderData['shipping_addresses']['url'];
		$shiipingAddressData = $this->getCurlRequest($ApiAuthorizationBC, $shipingAddressURL, $method = 'GET');
		if($shiipingAddressData['status'] == 200 && !empty($shiipingAddressData['responseData'])) {
			$orderShipping = $shiipingAddressData['responseData']['responseData'][0];
			//dd($orderShipping);
			return $orderShippingAddressData = array(
				'bigc_order_id' => $orderData['id'],
				'city' => $orderShipping['city'],
				'company' => $orderShipping['company'],
				'country' => $orderShipping['country'],
				'country_iso2' => $orderShipping['country_iso2'],
				'email' => $orderShipping['email'],
				'first_name' => $orderShipping['first_name'],
				'last_name' => $orderShipping['last_name'],
				'phone' => $orderShipping['phone'],
				'state' => $orderShipping['state'],
				'street_1' => $orderShipping['street_1'],
				'street_2' => $orderShipping['street_2'],
				'zip' => $orderShipping['zip'],
				'type' => 'S',
			);
		}
	}

	// Create order billing address payload for database
	public function createBillingAddressPayloadForDB($orderData = null) {
		return $orderBillingAddressData = array(
			'bigc_order_id' => $orderData['id'],
			'city' => $orderData['billing_address']['city'],
			'company' => $orderData['billing_address']['company'],
			'country' => $orderData['billing_address']['country'],
			'country_iso2' => $orderData['billing_address']['country_iso2'],
			'email' => $orderData['billing_address']['email'],
			'first_name' => $orderData['billing_address']['first_name'],
			'last_name' => $orderData['billing_address']['last_name'],
			'phone' => $orderData['billing_address']['phone'],
			'state' => $orderData['billing_address']['state'],
			'street_1' => $orderData['billing_address']['street_1'],
			'street_2' => $orderData['billing_address']['street_2'],
			'zip' => $orderData['billing_address']['zip'],
			'type' => 'B',
		);
	}

	//Get order items from Big Commerce and save into the database
	public function saveOrderItemsPayloadDB($orderData = null, $ApiAuthorizationBC = null)
	{
		$orderItemsApiURL = $orderData['products']['url'];
		$orderItemsData = $this->getCurlRequest($ApiAuthorizationBC, $orderItemsApiURL, $method = 'GET');
		
		$orderItemsData = $orderItemsData['responseData']['responseData'];
		//dd($orderItemsData);
		//$orderItems = $response->json();

		foreach ($orderItemsData as $orderItem) {
			$item = array(
				'bigc_itemid' => $orderItem['id'],
				'order_id' => $orderItem['order_id'],
				'product_id' => $orderItem['product_id'],
				'variant_id' => $orderItem['variant_id'],
				'order_address_id' => $orderItem['order_address_id'],
				'name' => $orderItem['name'],
				'name_customer' => $orderItem['name_customer'],
				'name_merchant' => $orderItem['name_merchant'],
				'sku' => $orderItem['sku'],
				'upc' => $orderItem['upc'],
				'type' => $orderItem['type'],
				'base_price' => $orderItem['base_price'],
				'price_ex_tax' => $orderItem['price_ex_tax'],
				'price_inc_tax' => $orderItem['price_inc_tax'],
				'price_tax' => $orderItem['price_tax'],
				'total_ex_tax' => $orderItem['total_ex_tax'],
				'total_inc_tax' => $orderItem['total_inc_tax'],
				'total_tax' => $orderItem['total_tax'],
				'weight' => $orderItem['weight'],
				'width' => $orderItem['width'],
				'height' => $orderItem['height'],
				'depth' => $orderItem['depth'],
				'quantity' => $orderItem['quantity'],
				'base_cost_price' => $orderItem['base_cost_price'],
				'cost_price_inc_tax' => $orderItem['cost_price_inc_tax'],
				'cost_price_ex_tax' => $orderItem['cost_price_ex_tax'],
				'cost_price_tax' => $orderItem['cost_price_tax'],
				'is_refunded' => $orderItem['is_refunded'],
				'quantity_refunded' => $orderItem['quantity_refunded'],
				'refund_amount' => $orderItem['refund_amount'],
				'wrapping_name' => $orderItem['wrapping_name'],
				'base_wrapping_cost' => $orderItem['base_wrapping_cost'],
				'wrapping_cost_ex_tax' => $orderItem['wrapping_cost_ex_tax'],
				'wrapping_cost_inc_tax' => $orderItem['wrapping_cost_inc_tax'],
				'wrapping_cost_tax' => $orderItem['wrapping_cost_tax'],
				'wrapping_message' => $orderItem['wrapping_message'],
				'quantity_shipped' => $orderItem['quantity_shipped'],
				'event_name' => $orderItem['event_name'],
				'event_date' => $orderItem['event_date'],
				'fixed_shipping_cost' => $orderItem['fixed_shipping_cost'],
				'ebay_item_id' => $orderItem['ebay_item_id'],
				'ebay_transaction_id' => $orderItem['ebay_transaction_id'],
				'option_set_id' => $orderItem['option_set_id'],
				'parent_order_product_id' => $orderItem['parent_order_product_id'],
				'is_bundled_product' => $orderItem['is_bundled_product'],
				'bin_picking_number' => $orderItem['bin_picking_number'],
				'external_id' => $orderItem['external_id'],
				'fulfillment_source' => $orderItem['fulfillment_source'],
				'applied_discounts' => json_encode($orderItem['applied_discounts']),
				'product_options' => json_encode($orderItem['product_options']),
				'configurable_fields' => json_encode($orderItem['configurable_fields']),
			);
			
			Orderitem::updateOrInsert(['bigc_itemid' => $orderItem['id']], $item);
		}		
	}	

	// create big commerce customer information paylaod for database
	public function createCustomerPAyloadForDB($bigCommerceCustomerData = null) {
		$payment_terms_type = null;
		$title = null;
		$zoho_customer_no = null;
		foreach($bigCommerceCustomerData['form_fields'] as $formFields) {			
			if($formFields['name'] == 'paymentTermType') {
				$payment_terms_type = $formFields['value'];
			}
			if($formFields['name'] == 'Title') {
				$title = $formFields['value'];
			}
			if($formFields['name'] == 'zoho_fixed_customer_no') {
				$zoho_customer_no = $formFields['value'];
			}
		}
		return $customerPayload = array(
			'bigc_customer_id' => $bigCommerceCustomerData['id'] ? $bigCommerceCustomerData['id'] : null,
			'company_name' => $bigCommerceCustomerData['company'] ? $bigCommerceCustomerData['company'] : null,
			'first_name' =>   $bigCommerceCustomerData['first_name'] ? $bigCommerceCustomerData['first_name'] : null,
			'last_name' =>  $bigCommerceCustomerData['last_name'] ? $bigCommerceCustomerData['last_name'] : null,
			'email' =>  $bigCommerceCustomerData['email'] ? $bigCommerceCustomerData['email'] : null,
			'title' =>  $title ? $title : null,
			'phone' => $bigCommerceCustomerData['phone'] ? $bigCommerceCustomerData['phone'] : null,
			'zoho_customer_no' => $zoho_customer_no ? $zoho_customer_no : null,
			'payment_terms_type' => $payment_terms_type ? $payment_terms_type : null
		);		
	}



	// create date time format for BC Api(ISO format)
	public function manageDateTimeForAPI($dateTime = null) 
	{
		if (strpos($dateTime, '+') !== false) {			
			$selector = '+';
		} else if(strpos($dateTime, '-') !== false) {
			$selector = '-';
		} else {
			return "Date format not correct!";
		}
		$explodedDateTimeArr = explode($selector, date('c'));
		return $explodedDateTimeArr[0];
	}

	//create customer Number On spire//
	public function createCustomerNumberOnSpire(Request $request)
	{		
		//dd($request);
		$orderAddressId = $request->input('oaddressid');
	    $orderAddressDetail = Orderaddress::find($orderAddressId);		  
		
		$phnumber = preg_replace("/[^0-9]/", "", $request->input('phoneinfo'));
		$state=$request->input('stateinfo');
		 
        $customerNo = $request->input('customerNo');
		$customerid = $request->input('customeridinfo');
		$company    = $request->input('companyinfo');
		

		$spirePayloadData = array(
			"customerNo" => $request->input('customerNo'),
			"name" => $company,			
			"address" =>array(
				"line1" => $request->input('addressno'),
				"line2" => $request->input('addresssecond'),
				"line3"=>"",
				"line4"=>"",
				"city" => $request->input('cityinfo'),
				"provState" => substr($state, 0, 2),
				"postalCode" => $request->input('postal'),
				"email"=>$request->input('email')
			),			
			"phone"=> array(
				"number" => $phnumber,
				"format" => 1,
	    	)
		);
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "http://209.151.135.27:10880/api/v2/companies/smi/customers/",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($spirePayloadData),
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"authorization: Basic QmlnYzpDaGV0dUAxMjM="
			),
		));

		$response = curl_exec($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$err = curl_error($curl);

		curl_close($curl);		
		
		if ($err) {
			return array("error"=> $err);
		} else {
			//return array("statusCode"=> $statusCode, "response"=>$response);
			if ($statusCode == 400) {
				return json_encode("Customer Number is already exists!");
			} else if ($statusCode == 201) {				
				$customerFormFieldsApiUpdateResponseBC = $this->updateCustomerNumberOnBC($customerid, $customerNo, $company);
				//dd($customerFormFieldsApiUpdateResponseBC);
				
				if(	$customerFormFieldsApiUpdateResponseBC ) {
					Customer::updateOrInsert(['bigc_customer_id' => $customerid], array('zoho_customer_no' => $customerNo));
					return json_encode("Customer created successfuly");
				} else {					
					return json_encode($customerFormFieldsApiUpdateResponseBC);
				}
			}					  	
		}		
	}


	//update customer number on bigcommerce/
	public function updateCustomerNumberOnBC($customerId = NULL, $customerNo = NULL, $company = NULL)
	{
		$formFieldsPayloadBC = array(
			array(
				'name' => 'zoho_fixed_customer_no',
				'value' => $customerNo,
				'customer_id' => (int)$customerId
			)
		);
					
		
		$apiUrlBC = "https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers/form-field-values";
		$ApiAuthorizationBC = $this->createBcApiAuthPayload();
		$ApiAuthorizationBC = $ApiAuthorizationBC['accessTokenBC'];
		$updateCustomerApiResponse = $this->getCurlRequest($ApiAuthorizationBC, $apiUrlBC, $method = 'PUT', $formFieldsPayloadBC);
		//dd($updateCustomerApiResponse);
		if($updateCustomerApiResponse['responseData']['status'] == 200) {
			return true;
		} else {
			return array("response" => $updateCustomerApiResponse['responseData']['responseData']);
		}			
	}

	//update order status on Big Commerce
	public function updateOrderStatusOnBC($orderId = null, $orderPayload = null) {
		$ApiAuthorizationBC = $this->createBcApiAuthPayload();
		$orderApiUrlBC = "https://api.bigcommerce.com/stores/z84xkjcnbz/v2/orders/$orderId";
		$orderApiResponse = $this->getCurlRequest($ApiAuthorizationBC['accessTokenBC'], $orderApiUrlBC, $method = 'PUT', $orderPayload);
		if ($orderApiResponse['status'] == 200) {
			if($orderApiResponse['responseData']['status'] == 200) {
				return true;
			} else {
				return null;
			}
		} else {
			return "somthing went worng!";
		}
	}



	// curl request functionaly for send request to the EAS server
	public function getCurlRequest($authorization = null, $URL = null, $method = null, $payload = null) {
		$token  = $this->getApiAuthToken();		
		if($token) { 
			$targetApiUrl = "http://192.168.6.2:8000/api/getRequestIAS";
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $targetApiUrl,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array (
					"accept: application/json",
					"content-type: application/json",
					"x-auth-token: $authorization",
					"url: $URL", // send big commerce API url to EAS
					"method: $method", // send method to EAS eg. GET, POST and PUT
					"token: $token" // send IAS generated authenctication token to EAS
				),
			));
	
			$response = curl_exec($curl);
			$err = curl_error($curl);
			
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$response = json_decode($response,true);
			
			curl_close($curl);
			if ($err) {
				return array(
					"status"=> $statusCode,
					"responseData" => $err
				);
			} else {
				if(!empty($response)){
					return array(
						"status"=> $statusCode,
						"responseData" => $response
					);
				} else {
					return array(
						"status"=> $statusCode,
						"responseData" => $response
					);
				}            
			}
		} else {
			echo "Token Not Created!";
			return false;
		}
	}

	public function getApiAuthToken(){
		$auth_user =  config('config.Auth_User'); // Authentication user name
		$auth_pass =  config('config.Auth_Pass'); // Authentication user passoword
		
		if (Auth::attempt(['email' => $auth_user, 'password' => $auth_pass])) {
			$user = Auth::user();
			$token = openssl_random_pseudo_bytes(16);

			//Convert the binary data into hexadecimal representation.
			$token = bin2hex($token);

			$update = User::where('id', $user['id']) // query to save the token into database table
				->update([
					'usertoken' => $token
				]);
			return $token;	
		} else {
			return array(
				"token" => 'Unauthorized call' // return unauthorized call
			);
		}
	}



	///////////////////////////////////////////////////




	//save order data bigcommerce to database//
	public function saveOrderWebhook(Request $request)
	{
		$webhookData = $request->all();
		Log::info("order webhook", $webhookData);
		$isCustomerOnSpire = false;
		$isShippingOnSpire = false;
		$isBillingOnSpire = false;
		$isPayterm = false;
		$iscreditcart = 1;
		$creditcard = 0;

		$bigcOrderId = $webhookData['data']['id'];
		//$bigcOrderId = $webhookData['id'];
		$authorization = config('config.BigCommerce_Api_Auth');
		$url = config('config.BigCommerce_Api_Url2');

		$response = Http::withHeaders([
			'accept' => 'application/json',
			'content-type' => 'application/json',
			'x-auth-token' => $authorization
		])->get($url . "/orders/{$bigcOrderId}");

		$orderData = $response->json();
		// echo "<pre>";
		// print_r($orderData);
		// die;
		Log::info($orderData);

		if ($orderData['payment_method'] == 'On Account') {
			$creditcard = 1;
		} else {

			$orderShippingAddress = $this->saveBillingAndShippingAddress($orderData, true);
			$isShippingOnSpire = ($orderShippingAddress) ? true : false;

			$orderBillingAddress = $this->checkAddressOnSpire($orderData['billing_address']['form_fields']);
			$isBillingOnSpire = ($orderBillingAddress) ? true : false;
		}

		if (!empty($orderData)) {
			$customerNumber = $this->getCustomerNumber($orderData['customer_id']);
			if ($customerNumber) {
				$customerOnSpireData = $this->checkCustomerOnSpire($customerNumber);
				$isCustomerOnSpire = ($customerOnSpireData['records']) ? true : false;
			}
			$payload = array(
				"base_handling_cost" => $orderData['base_handling_cost'],
				"bigc_orderid" => $orderData['id'],
				"base_shipping_cost" => $orderData['base_shipping_cost'],
				"base_wrapping_cost" => $orderData['base_wrapping_cost'],
				"cart_id" => $orderData['cart_id'],
				"channel_id" => $orderData['channel_id'],
				"coupon_discount" => $orderData['coupon_discount'],
				"currency_code" => $orderData['currency_code'],
				"currency_id" => $orderData['currency_id'],
				"custom_status" => $orderData['custom_status'],
				"customer_id" => $orderData['customer_id'],
				"customer_locale" => $orderData['customer_locale'],
				"customer_message" => $orderData['customer_message'],
				"date_modified" => $orderData['date_created'],
				"date_shipped" => $orderData['date_shipped'],
				"default_currency_code" => $orderData['default_currency_code'],
				"default_currency_id" => $orderData['default_currency_id'],
				"discount_amount" => $orderData['discount_amount'],
				"ebay_order_id" => $orderData['ebay_order_id'],
				"geoip_country" => $orderData['geoip_country'],
				"geoip_country_iso2" => $orderData['geoip_country_iso2'],
				"gift_certificate_amount" => $orderData['gift_certificate_amount'],
				"handling_cost_inc_tax" => $orderData['handling_cost_inc_tax'],
				"handling_cost_tax_class_id" => $orderData['handling_cost_tax_class_id'],
				"payment_method" => $orderData['payment_method'],
				"payment_provider_id" => $orderData['payment_provider_id'],
				"payment_status" => $orderData['payment_status'],
				"shipping_cost_ex_tax" => $orderData['shipping_cost_ex_tax'],
				"shipping_cost_inc_tax" => $orderData['shipping_cost_inc_tax'],
				"shipping_cost_tax" => $orderData['shipping_cost_tax'],
				"shipping_cost_tax_class_id" => $orderData['shipping_cost_tax_class_id'],
				"staff_notes" => $orderData['staff_notes'],
				"status" => $orderData['status'],
				"status_id" => $orderData['status_id'],
				"store_default_currency_code" => $orderData['store_default_currency_code'],
				"store_default_to_transactional_exchange_rate" => $orderData['store_default_to_transactional_exchange_rate'],
				"subtotal_ex_tax" => $orderData['subtotal_ex_tax'],
				"subtotal_inc_tax" => $orderData['subtotal_inc_tax'],
				"subtotal_tax" => $orderData['subtotal_tax'],
				"tax_provider_id" => $orderData['tax_provider_id'],
				"total_ex_tax" => $orderData['total_ex_tax'],
				"total_inc_tax" => $orderData['total_inc_tax'],
				"total_tax" => $orderData['total_tax'],
				"wrapping_cost_ex_tax" => $orderData['wrapping_cost_ex_tax'],
				"wrapping_cost_inc_tax" => $orderData['wrapping_cost_inc_tax'],
				"wrapping_cost_tax" => $orderData['wrapping_cost_tax'],
				"direct_align_with_spire" => ($creditcard || ($isCustomerOnSpire && $isShippingOnSpire && $isBillingOnSpire)) ? true : false,
				"creditcard" => $creditcard
			);


			$urlData = Order::insert($payload);
			$this->saveBillingAndShippingAddress($orderData);
			$this->saveOrderItem($orderData);
		}
	}

	//save data billing and shipping address bigcommerce to database//
	public function saveBillingAndShippingAddress($orderData, $checkAddressOnSpire = false)
	{
		$authorization = config('config.BigCommerce_Api_Auth');

		$response = Http::withHeaders([
			'accept' => 'application/json',
			'content-type' => 'application/json',
			'x-auth-token' => $authorization
		])->get($orderData['shipping_addresses']['url']);

		$orderShipping = $response->json()[0];

		if (!$checkAddressOnSpire) {

			$orderBillingAddressData = array(
				'bigc_order_id' => $orderData['id'],
				'city' => $orderData['billing_address']['city'],
				'company' => $orderData['billing_address']['company'],
				'country' => $orderData['billing_address']['country'],
				'country_iso2' => $orderData['billing_address']['country_iso2'],
				'email' => $orderData['billing_address']['email'],
				'first_name' => $orderData['billing_address']['first_name'],
				'last_name' => $orderData['billing_address']['last_name'],
				'phone' => $orderData['billing_address']['phone'],
				'state' => $orderData['billing_address']['state'],
				'street_1' => $orderData['billing_address']['street_1'],
				'street_2' => $orderData['billing_address']['street_2'],
				'zip' => $orderData['billing_address']['zip'],
				'type' => 'B',
			);

			$orderShippingAddressData = array(
				'bigc_order_id' => $orderData['id'],
				'city' => $orderShipping['city'],
				'company' => $orderShipping['company'],
				'country' => $orderShipping['country'],
				'country_iso2' => $orderShipping['country_iso2'],
				'email' => $orderShipping['email'],
				'first_name' => $orderShipping['first_name'],
				'last_name' => $orderShipping['last_name'],
				'phone' => $orderShipping['phone'],
				'state' => $orderShipping['state'],
				'street_1' => $orderShipping['street_1'],
				'street_2' => $orderShipping['street_2'],
				'zip' => $orderShipping['zip'],
				'type' => 'S',
			);


			$urlData = Orderaddress::insert([$orderBillingAddressData, $orderShippingAddressData]);
		} else {
			return $this->checkAddressOnSpire($orderShipping['form_fields']);
		}
	}

	//save order item data bigcommerce to database//
	public function saveOrderItem($orderData)
	{

		$authorization = config('config.BigCommerce_Api_Auth');
		$response = Http::withHeaders([
			'accept' => 'application/json',
			'content-type' => 'application/json',
			'x-auth-token' => $authorization
		])->get($orderData['products']['url']);

		$orderItems = $response->json();

		foreach ($orderItems as $orderItem) {
			$item[] = array(
				'bigc_itemid' => $orderItem['id'],
				'order_id' => $orderItem['order_id'],
				'product_id' => $orderItem['product_id'],
				'variant_id' => $orderItem['variant_id'],
				'order_address_id' => $orderItem['order_address_id'],
				'name' => $orderItem['name'],
				'name_customer' => $orderItem['name_customer'],
				'name_merchant' => $orderItem['name_merchant'],
				'sku' => $orderItem['sku'],
				'upc' => $orderItem['upc'],
				'type' => $orderItem['type'],
				'base_price' => $orderItem['base_price'],
				'price_ex_tax' => $orderItem['price_ex_tax'],
				'price_inc_tax' => $orderItem['price_inc_tax'],
				'price_tax' => $orderItem['price_tax'],
				'total_ex_tax' => $orderItem['total_ex_tax'],
				'total_inc_tax' => $orderItem['total_inc_tax'],
				'total_tax' => $orderItem['total_tax'],
				'weight' => $orderItem['weight'],
				'width' => $orderItem['width'],
				'height' => $orderItem['height'],
				'depth' => $orderItem['depth'],
				'quantity' => $orderItem['quantity'],
				'base_cost_price' => $orderItem['base_cost_price'],
				'cost_price_inc_tax' => $orderItem['cost_price_inc_tax'],
				'cost_price_ex_tax' => $orderItem['cost_price_ex_tax'],
				'cost_price_tax' => $orderItem['cost_price_tax'],
				'is_refunded' => $orderItem['is_refunded'],
				'quantity_refunded' => $orderItem['quantity_refunded'],
				'refund_amount' => $orderItem['refund_amount'],
				'wrapping_name' => $orderItem['wrapping_name'],
				'base_wrapping_cost' => $orderItem['base_wrapping_cost'],
				'wrapping_cost_ex_tax' => $orderItem['wrapping_cost_ex_tax'],
				'wrapping_cost_inc_tax' => $orderItem['wrapping_cost_inc_tax'],
				'wrapping_cost_tax' => $orderItem['wrapping_cost_tax'],
				'wrapping_message' => $orderItem['wrapping_message'],
				'quantity_shipped' => $orderItem['quantity_shipped'],
				'event_name' => $orderItem['event_name'],
				'event_date' => $orderItem['event_date'],
				'fixed_shipping_cost' => $orderItem['fixed_shipping_cost'],
				'ebay_item_id' => $orderItem['ebay_item_id'],
				'ebay_transaction_id' => $orderItem['ebay_transaction_id'],
				'option_set_id' => $orderItem['option_set_id'],
				'parent_order_product_id' => $orderItem['parent_order_product_id'],
				'is_bundled_product' => $orderItem['is_bundled_product'],
				'bin_picking_number' => $orderItem['bin_picking_number'],
				'external_id' => $orderItem['external_id'],
				'fulfillment_source' => $orderItem['fulfillment_source'],
				'applied_discounts' => json_encode($orderItem['applied_discounts']),
				'product_options' => json_encode($orderItem['product_options']),
				'configurable_fields' => json_encode($orderItem['configurable_fields']),

			);
		}
		$urlData = Orderitem::insert($item);
	}

	public function getCustomerNumber($customerId)
	{
		$customer = Customer::where('bigc_customer_id', $customerId)->first();

		if ($customer) {
			return $customer->zoho_customer_no;
		} else
			return null;
	}

	//check customer no on spire//
	public function checkCustomerOnSpire($customerNumber = null)
	{		
		//dd(config('config.Spire_Api_Url'). 'customers/?filter={"customerNo":"'.$customerNumber.'"}');
		$response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])->get(config('config.Spire_Api_Url') . 'customers/?filter={"customerNo":"' . $customerNumber . '"}');
		$customerData = $response->json();
		//dd($customerData);

		//echo "<pre>";print_r(config('config.Spire_Api_Url') . 'customers/?filter={"customerNo":"' . $customerNumber . '"}');die;
		if ($customerData){
			return $customerData;
		} else {
			return "0";
		}
		
	}



	//Get All order based on customer is using bigcommerc order api//
	public function getOrders()
	{
		$getOrderData = Order::where('status_id', 11)->where('order_send _to_spire', 0)->orderBy('id', 'desc')->get();
		//dd($getOrderData);
		return view('order')->with('orderdata', $getOrderData);
	}

	//Get Order Details using bigcommerce order api//
	public function getOrderDetails($orderId)
	{		
		$orderDetails = Order::find($orderId);
		$isCustomerOnSpire = false;

		$customerNumber = $this->getCustomerNumber($orderDetails['customer_id']);
		
		if ($customerNumber) {
			$customerOnSpireData = $this->checkCustomerOnSpire($customerNumber);
			//dd($customerOnSpireData);
			// print_r($customerOnSpireData);die;
			$isCustomerOnSpire = ($customerOnSpireData['records']) ? true : false;
		}

        $bigcommerceid = $orderDetails['customer_id'];
		$users = Customer::where('bigc_customer_id', $bigcommerceid)->first();
	
		return view('order_details')->with('orderDetails', $orderDetails)->with('users', $users)->with('isCustomerOnSpire', $isCustomerOnSpire);
	}

	//Get OrderBillingAddress using bigcommerce address api//
	public function getOrderBillingAddress(Request $request)
	{
		
		$orderAddressId=$request->id;
		$orderAddressDetail = Orderaddress::find($orderAddressId);
		$isCustomerOnSpire = false;
		$orderAddressDetails =	$orderAddressDetail->orderData->orderBillingAddress;
		
	    $companyaddress=$request->company;
		$orderid=$request->orderid;

		 
	
		
       // print_r($orderAddressDetails);die;
		$customerno = $orderAddressDetails->orderData->orderCustomer['zoho_customer_no'];
		if ($customerno) {
			$customerOnSpireData = $this->checkCustomerOnSpire($customerno);
			$isCustomerOnSpire = ($customerOnSpireData['records']) ? true : false;
		}
    
		 $cresponse = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . 'customers/?filter={"customerNo":"' . $customerno . '"}');

		$customernodata = $cresponse->json();
		
		
		$response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . 'addresses/?filter={"type":"B","linkNo":"' . $customerno . '"}');

		$customeraddressspire = $response->json();

		return view('order_Billingaddress_align')->with('orderAddressDetails', $orderAddressDetails)->with('orderid', $orderid)->with('isCustomerOnSpire', $isCustomerOnSpire)->with('companyaddress',  $companyaddress)->with('customernodata', $customernodata)->with('customeraddressspire', $customeraddressspire);
	}
	
	public function getOrderCustomer(Request $request)
	{
		
		$orderAddressId=$request->id;
		$orderAddressDetail = Orderaddress::find($orderAddressId);
		$isCustomerOnSpire = false;
		$orderAddressDetails =	$orderAddressDetail->orderData->orderBillingAddress;
		
	    $companyaddress=$request->company;
		$orderid=$request->orderid;

		 
	
		
       // print_r($orderAddressDetails);die;
		$customerno = $orderAddressDetails->orderData->orderCustomer['zoho_customer_no'];
		if ($customerno) {
			$customerOnSpireData = $this->checkCustomerOnSpire($customerno);
			$isCustomerOnSpire = ($customerOnSpireData['records']) ? true : false;
		}
    
		 $cresponse = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . 'customers/?filter={"customerNo":"' . $customerno . '"}');

		$customernodata = $cresponse->json();
		
		
		$response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . 'addresses/?filter={"type":"B","linkNo":"' . $customerno . '"}');

		$customeraddressspire = $response->json();

		return view('order_customer_align')->with('orderAddressDetails', $orderAddressDetails)->with('orderid', $orderid)->with('isCustomerOnSpire', $isCustomerOnSpire)->with('companyaddress',  $companyaddress)->with('customernodata', $customernodata)->with('customeraddressspire', $customeraddressspire);
	}

	//Get OrderShippingAddress using bigcommerce address api//
	public function getOrderShippingAddress(Request $request)
	{
		$orderAddressId=$request->id;
		$orderAddressDetail = Orderaddress::find($orderAddressId);
		$isCustomerOnSpire = false;
		$orderAddressDetails =	$orderAddressDetail->orderData->orderShippingAddress;
        $companyaddress=$request->company;
		$orderid=$request->orderid;

		$customerno = $orderAddressDetails->orderData->orderCustomer['zoho_customer_no'];
		if ($customerno) {
			$customerOnSpireData = $this->checkCustomerOnSpire($customerno);
			$isCustomerOnSpire = ($customerOnSpireData['records']) ? true : false;
		}

		$response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . 'addresses/?filter={"type":"S","linkNo":"' . $customerno . '"}');

		$customeraddressspire = $response->json();
		return view('order_shippingaddress_align')->with('isCustomerOnSpire', $isCustomerOnSpire)->with('orderid', $orderid)->with('companyaddress',  $companyaddress)->with('orderAddressDetails', $orderAddressDetails)->with('customeraddressspire', $customeraddressspire);
	}

	// public function orderData()
	// {

	// 	$abc = Order::find(21);
	// 	$d = $abc->orderData;
	// 	$customer_no = $abc->orderData['zoho_customer_no'];
	// 	print_r($customer_no);
	// }


	// //check order address on spire//
	public function checkOrderAddress($customerNumber)
	{

		$customerNumber = Customer::where('zoho_customer_no')->first();

		$response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . 'addresses/?filter={"linkNo":"' . $customerNumber . '"}');

		$customeraddresonsspire = $response->json();

		if ($customeraddresonsspire)
			return $customeraddresonsspire;
		else
			return "0";
	}









	//order align big commerce to spire//
	public function orderAligninspire(Request $request)
	{
		//print_r( $request->input());
		//die;
		$id = $request->input('bigcommerce_order_id');

		$orderDetails = Order::where(["bigc_orderid" => $id])->first();
		//$customerno = $orderDetails['zoho_customer_no'];
		
		$items = $orderDetails->orderItems;
		//print_r($items);
		//die;

		$customerno = $request->input('customerNo');

		$customerData = $this->checkCustomerOnSpire($customerno);
		$bamboraResponse = json_decode($orderDetails['customer_message'], true);

		if ($orderDetails['payment_method'] == 'Credit Terms') {
			$authcode = $bamboraResponse['auth_code'];
			$gatewaytxno = $bamboraResponse['id'];
		} else {
			$authcode = '';
			$gatewaytxno = '';
		}
		//echo "<pre>";print_r($bamboraResponse);die;
		if (!empty($customerData['records'])) {


			$data = array(
				"location" => "Noida Sector 63",
				"customer" => array(
					'id' => $customerData['records'][0]['id'],
					"code" => $customerData['records'][0]['name'],
					"name" => $customerData['records'][0]['name'],
					"customerNo" => $orderDetails->orderCustomer['zoho_customer_no'],
				),
				"currency" => array(
					"code" => $orderDetails['currency_code'],
					"description" => "Canadian dollars",
					"country" => $orderDetails['geoip_country'],
					"units" => "Dollars",
					"fraction" => "Cents",
					"symbol" => "$",
					"decimalPlaces" => 2,
					"symbolPosition" => "P",
					"rate" => "1",
					"rateMethod" => "/",
					"thousandsSeparator" => ","
				),
				"address" => array(
					"type" => "B",
					"name" => $orderDetails->orderBillingAddress['company'],
					"line1" => $orderDetails->orderBillingAddress['street_1'],
					"line2" => $orderDetails->orderBillingAddress['street_2'],
					"line3" => "",
					"line4" => "",
					"city" => $orderDetails->orderBillingAddress['city'],
					"postalCode" => $orderDetails->orderBillingAddress['zip'],
					"provState" => "ON",
					"country" => $orderDetails->orderBillingAddress['country_iso2'],
					"phone" => array(
						"number" => $orderDetails->orderBillingAddress['phone'],
						"format" => 1
					),
					"email" => $orderDetails->orderBillingAddress['email'],
				),
				"shippingAddress" => array(
					"type" => "S",
					"name" => $orderDetails->orderShippingAddress['company'],
					"line1" => $orderDetails->orderShippingAddress['street_1'],
					"line2" => $orderDetails->orderShippingAddress['street_2'],
					"line3" => "",
					"line4" => "",
					"city" => $orderDetails->orderShippingAddress['city'],
					"postalCode" => $orderDetails->orderShippingAddress['zip'],
					"provState" => "ON",
					"country" => $orderDetails->orderShippingAddress['country_iso2'],
					"phone" => array(
						"number" => $orderDetails->orderShippingAddress['phone'],
						"format" => 1
					),
					"email" => $orderDetails->orderShippingAddress['email'],
				),
				"salesperson" => array(
					"code" => 80,
					"name" => "Web Order"
				),
				"contact" => array(
					'name' => $orderDetails->orderCustomer['first_name'] . ' ' . $orderDetails->orderCustomer['last_name'],
					"email" => $orderDetails->orderCustomer['email'],
					"phone" => array(
						"number" => $orderDetails->orderCustomer['phone'],
						"format" => 1
					),
				),
				"customerPO" => null,
				"fob" => "Salesperson",
				"referenceNo" =>  $orderDetails->orderCustomer['first_name'] . ' ' . $orderDetails->orderCustomer['last_name'],
				"termsCode" => "02",
				"termsText" => "COD - Payment Required",
				// "taxes" => array(
				// 	array(
				// 		"code" => 3,
				// 		"name" => "HST (13%)",
				// 		"shortName" => "HST (13%)",
				// 		"rate" => "13",
				// 		"exemptNo" => "",
				// 		"total" => number_format($orderDetails['total_tax'], 2, '.', '')
				// 	)
				// ),
				"subtotal" => number_format($orderDetails['subtotal_ex_tax'], 2, '.', ''),
				"subtotalOrdered" => number_format($orderDetails['subtotal_ex_tax'], 2, '.', ''),
				"total" => number_format($orderDetails['total_ex_tax'], 2, '.', ''),
				"totalOrdered" => number_format($orderDetails['total_ex_tax'], 2, '.', ''),
				"grossProfit" => '',
				"payments" => array(),
				"udf" => array(
					"zohocrmcontactno" => $orderDetails->orderCustomer['zoho_customer_no'],
					"bigcommerce_customerno" => $orderDetails->orderCustomer['bigc_customer_id'],
					"crm_deal_entry_id" => "",
					"authcode" => $authcode,
					"gatewaytxno" => $gatewaytxno,
					"bigcommerce_order_id" => $orderDetails['bigc_orderid'],
					"sendtoorderingcontact" => ""
				)
			);

			$itemList = array();

			foreach ($items as $item) {
				$itemList[] =
					array(
						"whse" => "00",
						"partNo" => $item['sku'],
						"description" => $item['name'],
						"orderQty" => $item['quantity'],
						"committedQty" => "0",
						"backorderQty" => $item['quantity'],
						"retailPrice" => number_format($item['total_ex_tax'], 2, '.', ''),
						"unitPrice" => number_format($item['total_ex_tax'], 2, '.', ''),
						"discountable" => true,
						"discountPct" => "0",
						"discountAmt" => "0",
						"sellMeasure" => "EA"
					);
			}

			$data['items'] = $itemList;
			$createspiredata = json_encode($data);
			// echo "<pre>";
			// print_r($data);
			// die;

			// echo "<pre>"; print_r($createspiredata); dd();


			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => "http://209.151.135.27:10880/api/v2/companies/smi/sales/orders/",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $createspiredata,
				CURLOPT_HTTPHEADER => array(
					"accept: application/json",
					"content-type: application/json",
					"authorization: Basic QmlnYzpDaGV0dUAxMjM="
				),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
		// echo $response;
		// die;
			if ($err) {
				return response()->json(array('comment' => "Something went wrong while sending order on SPIRE"));
			} else {
				$this->updateCustomerNumberOnBC($orderDetails['customer_id'], $customerno);
				// if ($orderDetails['bigc_orderid']) {
					// $bigCommerceOrderUrl = config('config.Spire_Api_Url') . 'sales/orders/?filter={"udf.bigcommerce_order_id":"' . $orderDetails['bigc_orderid'] . '"}';
					// $spireAPiAuth = config('config.Spire_Api_Auth');
					// $getOrderData = call_curl($bigCommerceOrderUrl, $method = 'GET', $payload = array(), $spireAPiAuth);
					// $orderData = json_decode($getOrderData['response'], true);
					//// if ($orderData['records']) {
					//echo "pre";print_r($orderData);
					// $spireOrderId = $orderData['records'][0]['id'];
					// if ($spireOrderId) {
						// $orderNoresUrl = config('config.Spire_Api_Url') . 'sales/orders/'.$spireOrderId.'/notes/';
						// $bamboraPayload = array(
							// "linkNo" => $orderDetails->orderCustomer['zoho_customer_no'],
							// "subject" => 'Credit Card Payload Response',
							// "body" => $bamboraResponse,
						// );
						// $curl = curl_init();
						// curl_setopt_array($curl, array(
							// CURLOPT_URL => $orderNoresUrl,
							// CURLOPT_RETURNTRANSFER => true,
							// CURLOPT_ENCODING => "",
							// CURLOPT_MAXREDIRS => 10,
							// CURLOPT_TIMEOUT => 30,
							// CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							// CURLOPT_CUSTOMREQUEST => "POST",
							// CURLOPT_POSTFIELDS => json_encode($bamboraPayload),
							// CURLOPT_HTTPHEADER => array(
								// "accept: application/json",
								// "content-type: application/json",
								// "authorization: Basic QmlnYzpDaGV0dUAxMjM="
							// ),
						// ));

						// $notesResponse = curl_exec($curl);
						// $err = curl_error($curl);
						// curl_close($curl);
						// echo $notesResponse;
					// }
				// }
				//}
				$data = array(
					'order_send _to_spire' => 1
				);
				$urlData = Order::updateOrInsert(['bigc_orderid' => $id], $data);
				return true;
			}
		} else {
			return response()->json(array('comment' => "Customer Number not available on SPIRE"));
		}
	}


	//search customer billing address based on customer no. using  customer address api in spire//
	public function customerBillingAddressSearch(Request $request)
	{
		$company = $request->input('company');
		$postalCode = $request->input('postalCode');
		$address = $request->input('line1');
		$phone = $request->input('phone');
		$email = $request->input('email');
		$customerNumber = $request->input('customerNumber');


		$query = '';
		if ($company) {
			$query .= '"name"' . ":{" . '"$like"' . ':' . '"%25' . $company . '%25"' . "},";
		}

		if ($postalCode) {
			$query .= '"postalCode"' . ":{" . '"$like"' . ':' . '"%25' . $postalCode . '%25"' . "},";
		}

		if ($address) {
			$query .= '"line1"' . ":{" . '"$like"' . ':' . '"%25' . $address . '%25"' . "},";
		}

		if ($phone) {
			$query .= '"phone.number"' . ":{" . '"$like"' . ':' . '"%25' . $phone . '%25"' . "},";
		}
		if ($email) {
			$query .= '"email"' . ":{" . '"$like"' . ':' . '"%25' . $email . '%25"' . "},";
		}
		$query = substr($query, 0, -1);
		

		// if ($query) {
		// 	$query = '"linkNo":"' . $customerNumber . '",' . $query;
		// } else {
		// 	$query = '"linkNo":"' . $customerNumber . '"';
		// }

		$response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . '/addresses/?start=0&&limit=100&filter={"type":"B",' . $query . '}');

		$customeraddressspire = $response->json();
		
		
		if ($customeraddressspire)
			return $customeraddressspire;
		else
			return "0";
	}


	//search customer Shipping address based on customer no. using  customer billing address api in spire//
	public function customerShippingAddressSearch(Request $request)
	{
		$company = $request->input('company');
		$postalCode = $request->input('postalCode');
		$address = $request->input('line1');
		$phone = $request->input('phone');
		$email = $request->input('email');
		$customerNumber = $request->input('customerNumber');


		$filterquery = '';
		if ($company) {
			$filterquery .= '"name"' . ":{" . '"$like"' . ':' . '"%' . $company . '%"' . "},";
		}

		if ($postalCode) {
			$filterquery .= '"postalCode"' . ":{" . '"$like"' . ':' . '"%' . $postalCode . '%"' . "},";
		}

		if ($address) {
			$filterquery .= '"line1"' . ":{" . '"$like"' . ':' . '"%' . $address . '%"' . "},";
		}

		if ($phone) {
			$filterquery .= '"phone.number"' . ":{" . '"$like"' . ':' . '"%' . $phone . '%"' . "},";
		}

		if ($email) {
			$filterquery .= '"email"' . ":{" . '"$like"' . ':' . '"%' . $email . '%"' . "},";
		}
		$filterquery = substr($filterquery, 0, -1);

		if ($filterquery) {
			$filterquery = '"linkNo":"' . $customerNumber . '",' . $filterquery;
		} else {
			$filterquery = '"linkNo":"' . $customerNumber . '"';
		}

		if ($filterquery) {
			$response = Http::withHeaders([
				'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
			])
				->get(config('config.Spire_Api_Url') . '/addresses/?filter={"type":"S",' . $filterquery . '}');

			$customershippingaddressspire = $response->json();

			if ($customershippingaddressspire)
				return $customershippingaddressspire;
			else
				return "0";
		} else {
			return "0";
		}
	}


	public function address_update(Request $request)
	{


		$orderId = $request->input('orderAddressId');
		$customer_id = $request->input('customer_id');
		$linkno = $request->input('linkno');


		$data = array(
			"billing_address" => array(
				'city' => $request->input('city'),
				'company' => $request->input('company'),
				'street_1' => $request->input('street_1'),
				'street_2' => $request->input('street_2'),
				'state' => $request->input('state'),
				'zip' => $request->input('zip'),
				'phone' => $request->input('phone'),
			),

		);
		$dataBigC = array(
			array(
				'city' => $request->input('city'),
				'company' => $request->input('company'),
				'address1' => $request->input('street_1'),
				'address2' => $request->input('street_2'),
				'state_or_province' => $request->input('state'),
				'postal_code' => $request->input('zip'),
				'phone' => $request->input('phone'),
				'customer_id' => $customer_id,
				'id' => $orderId,
			),

		);

		$updatePayload = json_encode($data);
		//echo $updatePayload;die;
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => config('config.BigCommerce_Api_Url2') . "/orders/$orderId",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS => $updatePayload,
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"x-auth-token: tqhvp7fmyqr438pewjwtcwi1vggxpky"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {

			$data = array(
				'city' => $request->input('city'),
				'company' => $request->input('company'),
				'street_1' => $request->input('street_1'),
				'street_2' => $request->input('street_2'),
				'state' => $request->input('state'),
				'zip' => $request->input('zip'),
				'phone' => $request->input('phone'),

			);
			$urlData = Orderaddress::updateOrInsert(['bigc_order_id' => $orderId, 'type' => 'B'], $data);

			$dataBigc = array(
				'zoho_customer_no' => $request->input('linkno'),
			);
			$urlData = Customer::updateOrInsert(['bigc_customer_id' => $customer_id], $dataBigc);

			//echo $response;
			echo "1";
		}
	}

	public function updateShippingAddress(Request $request)
	{


		$orderId = $request->input('orderAddressId');
		$customer_id = $request->input('customer_id');



		$authorization = config('config.BigCommerce_Api_Auth');
		$url = config('config.BigCommerce_Api_Url2');

		$response = Http::withHeaders([
			'accept' => 'application/json',
			'content-type' => 'application/json',
			'x-auth-token' => $authorization
		])->get($url . "/orders/$orderId/shipping_addresses");

		$orderData = $response->json();


		$addressId = $orderData[0]['id'];


		$data = array(
			'city' =>  $orderData[0]['city'],
			'company' =>  $orderData[0]['company'],
			'street_1' =>  $orderData[0]['street_1'],
			'street_2' =>  $orderData[0]['street_2'],
			'state' =>  $orderData[0]['state'],
			'zip' =>  $orderData[0]['zip'],
			'phone' =>  $orderData[0]['phone'],
		);
		//print_r($data);


		$dataBigC = array(
			array(
				'city' => $request->input('city'),
				'company' => $request->input('company'),
				'address1' => $request->input('street_1'),
				'address2' => $request->input('street_2'),
				'state_or_province' => $request->input('state'),
				'postal_code' => $request->input('zip'),
				'phone' => $request->input('phone'),
				'customer_id' => $customer_id,
				'id' => $addressId,
			),

		);

		$updatePayload = json_encode($data);
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url . "/orders/$orderId/shipping_addresses/$addressId",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS => $updatePayload,
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"x-auth-token: tqhvp7fmyqr438pewjwtcwi1vggxpky"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			$data = array(
				'city' => $request->input('city'),
				'company' => $request->input('company'),
				'street_1' => $request->input('street_1'),
				'street_2' => $request->input('street_2'),
				'state' => $request->input('state'),
				'zip' => $request->input('zip'),
				'phone' => $request->input('phone'),

			);
			$urlData = Orderaddress::updateOrInsert(['bigc_order_id' => $orderId, 'type' => 'S'], $data);

			echo $response;
		}
	}

	public function addressUpdateBigc($dataBigc = NULL)
	{

		$updatePayload = json_encode($dataBigc);
		$curl = curl_init();
		$BigCurl = config('config.BigCommerce_Api_Url') . 'customers/addresses';
		curl_setopt_array($curl, array(
			CURLOPT_URL => $BigCurl,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS => $updatePayload,
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"x-auth-token: tqhvp7fmyqr438pewjwtcwi1vggxpky"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {

			echo $response;
		}
	}

	public function createAddressInSpire(Request $request)
	{

		$customerNo = $request->input('name');


		$response = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])->get(config('config.Spire_Api_Url') . 'customers/?filter={"customerNo":"' . $customerNo . '"}');

		$customerData = $response->json();
		$customerId = $customerData['records'][0]['id'];
         $state=$request->input('provState');

		$data = array(
			"type" => "S",
			"linkNo" => $customerNo,
			"linkTable" => "CUST",
			"linkType" => "CUST",
			"name" => $request->input('companyinfo'),
			"line1" => $request->input('line1'),
			"line2" => $request->input('line2'),
			"city" => $request->input('city'),
			"provState" => substr($state, 0, 2),
			"postalCode" => $request->input('postalCode'),
			"phone" => array(
				'number' => $request->input('phone'),
			),
			"shipId" => $request->input('line1'),
			"email" => $request->input('emailaddress'),
		);
		// echo json_encode($data);
		$createspiredata = json_encode($data);
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "http://209.151.135.27:10880/api/v2/companies/smi/customers/$customerId/addresses/",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $createspiredata,
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"authorization: Basic QmlnYzpDaGV0dUAxMjM="
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			// echo $statusCode;
		curl_close($curl);

		if ($err) {
			// echo "cURL Error #:" . $err;
			// return $err;
		} else {
			echo $statusCode;
		}
	}
	

	
	//update Shipping address on spire//
	public function updateshippingAddressInSpire(Request $request)
	{
		$orderId = $request->input('orderAddressId');

            $email=$request->input('email'); 
			$city=$request->input('city');
           $linkno=$request->input('linkno');
		   $companyinfo=$request->input('companyinfo');
		   $line1=$request->input('line1');
		   $line2=$request->input('line2');
		   $postalcode=$request->input('postalCode');
		   

		$id = $request->input('spireaddress');
		$addressId = $request->input('addressId');
        $customerNo = $request->input('name');
		 
		
		$phnumber = preg_replace("/[^0-9]/", "", $request->input('phone'));

		$customerData = $this->checkCustomerOnSpire($customerNo);
		$state=$request->input('provState');

		$customer_id = $customerData['records'][0]['id'];
      

		$data = array(
			"type" => "S",
			"linkno" =>($linkno) ? $linkno : '',
			"linkTable" => "CUST",
			"linkType" => "CUST",
			"name" => ($companyinfo) ? $companyinfo : '',
			"line1" => ($line1) ? $line1 : '',
			"line2" => ($line2) ? $line2 : '',
			"city" => ($city) ? $city : '',
			"provState" =>substr($state, 0, 2),
			"postalCode" => ($postalcode) ? $postalcode : '',
			"phone" => array(
				'number' => ($phnumber) ? $phnumber : '',
			),
			//"shipId" => "900",
			"email" => ($email) ? $email : '',

		);
		
		$updatespiredata = json_encode($data);
		// echo config('config.Spire_Api_Url')."customers/$customer_id/addresses/$addressId";
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => config('config.Spire_Api_Url') . "customers/$customer_id/addresses/$addressId",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS =>$updatespiredata,
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"authorization: Basic QmlnYzpDaGV0dUAxMjM="
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {

			$data = array(
				'spireAddressId' => $addressId
			);


			$urlData = Orderaddress::updateOrInsert(['bigc_order_id' => $orderId, 'type' => 'S'], $data);
			echo $response;
			//echo "1";
		}
	}

	//update billing address on spire//
	public function updatebillingAddressInSpire(Request $request)
	{
		
		$orderId = $request->input('orderAddressId');
		$id = $request->input('spireaddress');
		$addressId = $request->input('addressId');
		$customerNo = $request->input('cutomerNo');
		$state    =   $request->input('provState');
		$line1=$request->input('line1');
		$city=$request->input('city');
		$line2=$request->input('line2');
		$postalcode=$request->input('postalCode');
		$phone=$request->input('phone');
		$email=$request->input('email');
		$customerData = $this->checkCustomerOnSpire($customerNo);		
		//print_r($customerNo);die;
		$customer_id = $customerData['records'][0]['id'];
 
		
		$data = array(
			"type" => "B",
			"linkno" => $request->input('linkno'),
			"linkTable" => "CUST",
			"linkType" => "CUST",
			"name" => $request->input('name'),
			"line1" => ($line1) ? $line1 : '',
			"line2" => ($line2) ? $line2 : '',
			"city" => ($city) ? $city : '',
			"provState" =>substr($state, 0, 2),
			"postalCode" => ($postalcode) ? $postalcode : '',
			"phone" => array(
				'number' => ($phone) ? $phone : '',
			),
			//"shipId" => "900",
			"email" => ($email) ? $email : '',
		);
		// echo"address". $addressId;
		// echo"customer". $customer_id;die;
		// echo json_encode($data);
		 //echo "<pre>";print_r($data);die;
		$updatespiredata = json_encode($data);
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => config('config.Spire_Api_Url') . "customers/$customer_id/addresses/$addressId",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS => $updatespiredata,
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"authorization: Basic QmlnYzpDaGV0dUAxMjM="
			),
		));

		$response = curl_exec($curl);
  		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  //echo $statusCode;
		$err = curl_error($curl);
 
		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			$data = array(
				'spireAddressId' => $addressId
			);
			$urlData = Orderaddress::updateOrInsert(['bigc_order_id' => $orderId, 'type' => 'B'], $data);
			//echo $response;
			//  die;
			// echo "1";
		}
	}


	// Check address on sprte
	public function checkAddressOnSpire($orderAddress)
	{
		$isSpireAddress = false;
		if (!empty($orderAddress)) {
			foreach ($orderAddress as $value) {
				if ($value['name'] == 'isSpireAddress' && $value['value'] == 'yes') {

					$isSpireAddress = true;
				}
			}
		}
		return $isSpireAddress;
	}
	
	public function checkcustnospire(Request $request)
	{
		$customerno = $request->input('customerNo');
		
		 $cresponse = Http::withHeaders([
			'authorization' => 'Basic QmlnYzpDaGV0dUAxMjM='
		])
			->get(config('config.Spire_Api_Url') . 'customers/?filter={"customerNo":"' . $customerno . '"}');

		$customernodata = $cresponse->json();
		return $customernodata;
	}	
	
	
	public function customerupdatewithoutcustomernumber(Request $request)
	{
		$addressid = $request->input('addressid');
		$customerNo = $request->input('spirecustomername');
		$company = $request->input('companyname');
		$cutomerid= $request->input('customerid');
		$orderId= $request->input('addressinfoid');
		$address1= $request->input('address1');
		$address2= $request->input('address2');
	    $city= $request->input('city');
		$provstate= $request->input('provstate');
		$postalCode= $request->input('postalCode');
		$phone= $request->input('phone');
		$email= $request->input('email');
	
	
		$info=$this->updateCustomerNumberOnBC($cutomerid, $customerNo,$company);   
		$dataBigc = array(
				'zoho_customer_no' => $customerNo,
			);
			$urlData = Customer::updateOrInsert(['bigc_customer_id' => $cutomerid], $dataBigc);		

		$data = array(
			"billing_address" => array(
				'city' => ($city) ? $city : '',
				'company' => ($company) ? $company : '',
				'street_1' => ($address1) ? $address1 : '',
				'street_2' => ($address2) ? $address2 : '',
				'state' => ($provstate) ? $provstate : '',
				'zip' => ($postalCode) ? $postalCode : '',
				'phone' => ($phone) ? $phone : '',
			),

		);
		$urlBigc = config('config.BigCommerce_Api_Url2') . "/orders/$orderId";
		//echo "<pre>";print_r($urlBigc);die;

		$updatepayload = json_encode($data);
		$authorization = config('config.BigCommerce_Api_Auth');
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $urlBigc,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS => $updatepayload,
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json",
				"x-auth-token: $authorization"
			),
		));

		$response = curl_exec($curl);
	
		$statusCode = CURL_GETINFO($curl, CURLINFO_HTTP_CODE);
		$err = curl_error($curl);
		curl_close($curl);
		if(!$err){
			return array(
				"response"=> $response,
				"status" => $statusCode
			);
		} else {
			return array(
				"response"=> $response,
				"status" => $statusCode
			);
		}		
	}	
	
	
}
