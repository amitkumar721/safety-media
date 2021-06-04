<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Log;
class CheckoutController extends Controller
{
    public function getPaymentTerms() {
        $authorizationSpire = config('config.Spire_Api_Auth');
        $url = "http://209.151.135.27:10880/api/v2/companies/smi/payment_terms/?limit=100";
        return $getPaymentTermsData = call_curl($url, $method = "GET",  $payload = '', $authorizationSpire);
        //return json_decode($getPaymentTermsData, true);
    }

    public function getAddressesFromSpire($data = null)
    {       
        Log::info("getaddressfromSpire data array", array($data));
        $data = explode(":",$data);
        $customerRelatedData = explode(",",$data[0]);        
        $customerNumber = $customerRelatedData[0];        
        $addressType = $customerRelatedData[1];
        $bcCustomerID = $customerRelatedData[2];

        //$AllowedAddressData = $data[1];
        if(isset($data[1])) {
            $allowedAddress = explode(",", $data[1]);
        } else {
            $allowedAddress = null;
        }
        
        $authorizationSpire = config('config.Spire_Api_Auth');
        if(isset($allowedAddress)) {
            Log::info("allowed address case", $allowedAddress);
            foreach($allowedAddress as $addresId) {                
                $param = '?filter={"id":"'.$addresId.'"}';
                $url = "http://209.151.135.27:10880/api/v2/companies/smi/addresses/".$param;                
                $getAddressData = call_curl($url, $method = "GET",  $payload = '', $authorizationSpire);
                $getAddressDataArr = json_decode($getAddressData['response'],true);
                Log::info("spire addrss id-$addresId",$getAddressDataArr['records']);
                if ($getAddressData['status'] == 200) {
                    $apiResponseData = json_decode($getAddressData['response'], true);
                    $spireAddressArr = $apiResponseData['records'];
                    if($spireAddressArr) {
                        $finalPayload = array();                        
                        foreach($spireAddressArr as $address) {
                            Log::info("Spire Address data", $address);
                            $fullName = explode(" ", $address['name'],2);
                            if(count($fullName) > 1) {
                                $firstName = $fullName[0];
                                $lastName = $fullName[1];
                            } else {
                                $firstName = $fullName[0];
                                $lastName = $fullName[0];
                            }
                            
                            if ( $address['country'] == "CAN" ) { 
                                $country = "CA";
                                $state = "Alberta";
                            } else if( $address['country'] == "USA" ){
                                $country = "US";
                                $state = "Alaska";
                            } else {
                                $country = "IN";
                                $state = "Bihar";
                            }
                            
                            if($address['type'] == 'S') {
                                $addressType = "commercial";
                            } else if($address['type'] == 'B') {
                                $addressType = "residential";
                            } else {
                                $addressType = "residential";
                            }
                        
                            $payload = array(
                                "first_name"=> $firstName?$firstName:"First Name",
                                "last_name"=> $lastName?$lastName:"Last Name",
                                "company"=> $address['name']?$address['name']:"Company Name",
                                "address1"=> $address['line1']?$address['line1']:"Address1",
                                "address2"=> $address['line2'],
                                "city"=> $address['city']?$address['city']:"City",
                                "state_or_province"=> $state,
                                "postal_code"=> $address['postalCode']?$address['postalCode']:"Postal Code",
                                "country_code"=> $country,
                                "phone"=> $address['phone']['number'],
                                "address_type"=> $addressType,
                                "customer_id"=> (int)$bcCustomerID
                            );
                            $finalPayload[] = $payload;
                        }
                       
                        //return json_encode($finalPayload);die;           
                        $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
                        $bigcommerceAddressApiUrl = config('config.BigCommerce_Api_Url') . 'customers/addresses';
                        $curlResponse = call_curl($bigcommerceAddressApiUrl, $method = "POST",  $finalPayload, $authorization);
                        Log::info("Bigcommerce customer address api save response", $curlResponse);
                        // return $curlResponse;die;
                        if ($curlResponse['status'] == 200) {                    
                            $apiResponseData = json_decode($curlResponse['response'], true);                   
                            $apiResponseDataArr = $apiResponseData['data'];
                            $finalAddressFormFieldsPayload = array();
                            foreach($apiResponseDataArr as $responseAddress) {
                                $addressFormFieldsPayload = array(
                                    "name"=> "isSpireAddress",
                                    "value"=> "yes",
                                    "address_id"=> (int)$responseAddress['id']
                                );
                                $finalAddressFormFieldsPayload[] = $addressFormFieldsPayload;
                            }
                           
                            $bigcommerceFormFieldsApiUrl = config('config.BigCommerce_Api_Url') . 'customers/form-field-values';
                            $curlResponse = call_curl($bigcommerceFormFieldsApiUrl, $method = "PUT",  $finalAddressFormFieldsPayload, $authorization);
                            if ($curlResponse['status'] == 200 && !empty($apiResponseData['data'])) {
                                return array(
                                    "status" => $curlResponse['status']                          
                                );
                            } else {
                                return array(
                                    "status" => $curlResponse['status'],
                                    "message" => $curlResponse['response']
                                );
                            }
                        } else {
                            return array(
                                "status" => $curlResponse['status'],
                                "message" => $curlResponse['response']
                            );
                        }
                    }
                }
            }
        } else {            
            Log::info("Not allowed addrss case");     
            $param = '?filter={"linkNo":"'.$customerNumber.'","type":"'.$addressType.'"}&limit=10';
            $url = "http://209.151.135.27:10880/api/v2/companies/smi/addresses/".$param;
            $getAddressData = call_curl($url, $method = "GET",  $payload = '', $authorizationSpire);
            Log:info("Spire Address", $getAddressData);
            if ($getAddressData['status'] == 200) {
                $apiResponseData = json_decode($getAddressData['response'], true);
                $spireAddressArr = $apiResponseData['records'];
                if($spireAddressArr) {
                    $finalPayload = array();
                    foreach($spireAddressArr as $address) {
                        $fullName = explode(" ", $address['name'],2);
                        if(count($fullName) > 1) {
                            $firstName = $fullName[0];
                            $lastName = $fullName[1];
                        } else {
                            $firstName = $fullName[0];
                            $lastName = $fullName[0];
                        }
                        
                        if ( $address['country'] == "CAN" ) { 
                            $country = "CA";
                            $state = "Alberta";
                        } else if( $address['country'] == "USA" ){
                            $country = "US";
                            $state = "Alaska";
                        } else {
                            $country = "IN";
                            $state = "Bihar";
                        }

                        if($address['type'] == 'S') {
                            $addressType = "commercial";
                        } else if($address['type'] == 'B') {
                            $addressType = "residential";
                        } else {
                            $addressType = "residential";
                        }
                    
                        $payload = array(
                            "first_name"=> $firstName?$firstName:"First Name",
                            "last_name"=> $lastName?$lastName:"Last Name",
                            "company"=> $address['name']?$address['name']:"Company Name",
                            "address1"=> $address['line1']?$address['line1']:"Address1",
                            "address2"=> $address['line2'],
                            "city"=> $address['city']?$address['city']:"City",
                            "state_or_province"=> $state,
                            "postal_code"=> $address['postalCode']?$address['postalCode']:"Postal Code",
                            "country_code"=> $country,
                            "phone"=> $address['phone']['number'],
                            "address_type"=> $addressType,
                            "customer_id"=> (int)$bcCustomerID
                        );
                        $finalPayload[] = $payload;
                    }   
                    //return json_encode($finalPayload);die;           
                    $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
                    $bigcommerceAddressApiUrl = config('config.BigCommerce_Api_Url') . 'customers/addresses';
                    $curlResponse = call_curl($bigcommerceAddressApiUrl, $method = "POST",  $finalPayload, $authorization);
                   // return $curlResponse;die;
                    if ($curlResponse['status'] == 200) {                    
                        $apiResponseData = json_decode($curlResponse['response'], true);                   
                        $apiResponseDataArr = $apiResponseData['data'];
                        $finalAddressFormFieldsPayload = array();
                        foreach($apiResponseDataArr as $responseAddress) {
                            $addressFormFieldsPayload = array(
                                "name"=> "isSpireAddress",
                                "value"=> "yes",
                                "address_id"=> (int)$responseAddress['id']
                            );
                            $finalAddressFormFieldsPayload[] = $addressFormFieldsPayload;
                        }
                       
                        $bigcommerceFormFieldsApiUrl = config('config.BigCommerce_Api_Url') . 'customers/form-field-values';
                        $curlResponse = call_curl($bigcommerceFormFieldsApiUrl, $method = "PUT",  $finalAddressFormFieldsPayload, $authorization);
                        if ($curlResponse['status'] == 200 && !empty($apiResponseData['data'])) {
                            return array(
                                "status" => $curlResponse['status']                          
                            );
                        } else {
                            return array(
                                "status" => $curlResponse['status'],
                                "message" => $curlResponse['response']
                            );
                        }
                    } else {
                        return array(
                            "status" => $curlResponse['status'],
                            "message" => $curlResponse['response']
                        );
                    }
                }
            }
        }        
    }
}
