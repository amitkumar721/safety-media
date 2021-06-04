<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Log;
use DB;

class CustomerController extends Controller
{
	public function createCustomer(Request $request)
    {		
		$data = $request->all();
		//return $data;
	
		$payload = array(
			array(
				"email" => $data['user_info_email'],
				"first_name" => $data['user_info_first_name'],
				"last_name" => $data['user_info_last_name'],
				"company" => $data['user_info_company_name'],
				"phone" => $data['user_info_phone'],
				"authentication" => array(
					"force_password_reset" => false,
					"new_password" => $data['password']
				),
				"addresses" => array(
					array(
						"first_name" => $data['billing_first_Name'],
						"last_name" => $data['billing_last_Name'],
						"address1" => $data['billing_address_line1'],
						"address2" => $data['billing_address_line2'],
						"city" => $data['billing_city'],
						"state_or_province" => $data['billing_states'],
						"postal_code" => $data['billing_zipcode'],
						"country_code" => $data['billing_country'],
						"phone" => $data['billing_phone'],
						"address_type" => 'residential'
					),
					array(
						"first_name" => $data['shipping_first_Name'],
						"last_name" => $data['shipping_last_Name'],
						"address1" => $data['shipping_address_line1'],
						"address2" => $data['shipping_address_line2'],
						"city" => $data['shipping_city'],
						"state_or_province" => $data['shipping_states'],
						"postal_code" => $data['shipping_zipcode'],
						"country_code" => $data['shipping_country'],
						"phone" => $data['shipping_phone'],
						"address_type" => 'commercial'
					)								
				)
			)
		);
		
		
		//return json_encode($payload); die;
		
		$result = $this->ApiCall($URL = "https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers", $payload, $method = "POST");
		//return $result; die;
		
		if($result['status'] === 200){
				$customerId = $result['responseData']['data'][0]['id'];				
				$addressIds = array();
				foreach($result['responseData']['data'][0]['addresses'] as $address){
					if($address['address_type'] == 'residential'){
						$addressIds['biilingAddressId'] = $address['id'];
					}
					if($address['address_type'] == 'commercial'){
						$addressIds['shippingAddressId'] = $address['id'];
					}
				}
				//return $addressIds;
				//die;
				
				
			$customeFieldsPayload = array(				
				array(
					"customer_id" => $customerId,
					"name" => "Address Line 1",
					"value" => $data['user_info_address_line1']
				),
				array(
					"customer_id" => $customerId,
					"name" => "Address Line 2",
					"value" => isset($data['user_info_address_line2']) ? $data['user_info_address_line2'] : ""
				),
				array(
					"customer_id" => $customerId,
					"name" => "Address Line 3",
					"value" => isset($data['user_info_address_line3'])? $data['user_info_address_line3'] : ""
				),
				array(
					"customer_id" => $customerId,
					"name" => "Address Line 4",
					"value" => isset($data['user_info_address_line4'])? $data['user_info_address_line4'] : ""
				),
				array(
					"customer_id" => $customerId,
					"name" => "City",
					"value" => $data['user_info_city']
				),
				array(
					"customer_id" => $customerId,
					"name" => "State",
					"value" => $data['user_info_states']
				),
				array(
					"customer_id" => $customerId,
					"name" => "Country",
					"value" => $data['user_info_country']
				),
				array(
					"customer_id" => $customerId,
					"name" => "Zip Code",
					"value" => $data['user_info_zipcode']
				),
				array(
					"customer_id" => $customerId,
					"name" => "Fax",
					"value" =>isset($data['user_info_fax'])? $data['user_info_fax'] : ""
				),
				array(
					"customer_id" => $customerId,
					"name" => "Website",
					"value" => isset($data['user_info_website'])? $data['user_info_website'] : ""
				),
				array(
					"customer_id" => $customerId,
					"name" => "Title",
					"value" => isset($data['user_info_title'])? $data['user_info_title'] : ""
				),				
				array(
					"address_id" => $addressIds['biilingAddressId'],
					"name" => "Address Line 3",
					"value" => isset($data['billing_address_line3'])? $data['billing_address_line3'] : ""
				),
				array(
					"address_id" => $addressIds['biilingAddressId'],
					"name" => "Address Line 4",
					"value" => isset($data['billing_address_line4'])? $data['billing_address_line4'] : ""
				),
				array(
					"address_id" => $addressIds['biilingAddressId'],
					"name" => "Fax",
					"value" => isset($data['billing_fax'])? $data['billing_fax'] : ""
				),
				array(
					"address_id" => $addressIds['biilingAddressId'],
					"name" => "Website",
					"value" => isset($data['billing_website'])? $data['billing_website'] : ""
				),
				array(
					"address_id" => $addressIds['shippingAddressId'],
					"name" => "Address Line 3",
					"value" => isset($data['shipping_address_line3'])? $data['shipping_address_line3'] : ""
				),
				array(
					"address_id" => $addressIds['shippingAddressId'],
					"name" => "Address Line 4",
					"value" => isset($data['shipping_address_line4'])? $data['shipping_address_line4'] : ""
				),
				array(
					"address_id" => $addressIds['shippingAddressId'],
					"name" => "Fax",
					"value" => isset($data['shipping_fax'])? $data['shipping_fax'] : ""
				),
				array(
					"address_id" => $addressIds['shippingAddressId'],
					"name" => "Website",
					"value" => isset($data['shipping_website'])? $data['shipping_website'] : ""
				)
			);
			Log::info("customeFieldsPayload",$customeFieldsPayload);
			$customFieldsPayloadChunks = array_chunk($customeFieldsPayload, ceil(count($customeFieldsPayload) / (count($customeFieldsPayload)/10)));
			//return $customFieldsPayloadChunks;
			$customFieldsResponse = array();
			foreach($customFieldsPayloadChunks as $customChunkData){
				$result1 = $this->ApiCall("https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers/form-field-values", $customChunkData, "PUT");		
				if($result1['status'] != 200){
					$customFieldsResponse[] = $result1;								
					break;					
				} else {
					$customFieldsResponse[] = $result1;
				}
			}
			//return $customFieldsResponse;
			$apiValidateResponse = $this->validateApiResponse($customFieldsResponse);
			if(!$apiValidateResponse){
				$deleteResponse = $this->deleteCustomer($customerId);					
				return $finalResponse[] = $deleteResponse;
			} else {				
				if($this->validateApiResponse($customFieldsResponse)){
					return $finalResponse[] = array('status'=> 200, 'data'=> $customFieldsResponse);
				} else{
					return $finalResponse[] = array('status'=> 400, 'data'=> $customFieldsResponse);
				}				
			}	
				
		} else {
			return $finalResponse = array('data'=>$result);
		}	
		
    }
	
	public function ApiCall($URL = '', $payload = array(), $method = '')
	{
		$curl = curl_init();

		curl_setopt_array($curl, array(
			  CURLOPT_URL => $URL,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => $method,
			  CURLOPT_POSTFIELDS => json_encode($payload),
			  CURLOPT_HTTPHEADER => array(
				"content-type: application/json",
				"x-auth-token: tqhvp7fmyqr438pewjwtcwi1vggxpky"
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
            return array(
                "status"=> $statusCode,
                "responseData" => $response
            );
        }
		
	}
	
	public function deleteCustomer($customerId)
	{
		$URL = "https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers?id:in=$customerId";
		$method = "DELETE";
		return $this->ApiCall($URL, $payload = array(), $method);		 
	}
	
	public function validateApiResponse($apiResponseData)
	{
		Log::info("customer API response",$apiResponseData);
		$validateResponse = true;
		foreach($apiResponseData as $responseVal){
			if ($responseVal['status'] != 200) {
				$validateResponse = false;
			}
		}
		return $validateResponse;
    }

	public function testDatabase(){		
		try {
			DB::connection()->getPdo();
			if(DB::connection()->getDatabaseName()){
				echo "Yes! Successfully connected to the DB: " . DB::connection()->getDatabaseName();
			}else{
				die("Could not find the database. Please check your configuration.");
			}
		} catch (\Exception $e) {
			die("Could not open connection to database server.  Please check your configuration.");
		}
	}


   
}
