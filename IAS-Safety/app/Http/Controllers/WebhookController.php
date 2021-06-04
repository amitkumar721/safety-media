<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Log;
use App\Http\Controllers\ZohoController as ZohoController;
use App\Http\Controllers\SpireController as SpireController;
use App\Http\Controllers\CustomerController as CustomerController;

class WebhookController extends Controller
{
    public function getWebhook(Request $request)
    {
        //die;
        $webhookData = $request->all();
        Log::info($webhookData);
        if (!empty($webhookData)) {
            $customerId = $webhookData['data']['id'];
            Log::info('customer id -' . $customerId);
            $apiResponse = $this->curlRequest($customerId);
            // Case Existing Customer and update its details 
            if ($webhookData['scope'] == 'store/customer/updated') {
                if ($apiResponse['status_code'] == 200) {
                    $apiResponseData = json_decode($apiResponse['data'], true);
                    if (!empty($apiResponseData['data'])) {
                        //echo "<pre>";print_r($apiResponseData['data']);die;
                        $customerEmail = $apiResponseData['data'][0]['email'];
                        // Get customer data from ZOHO CRM
                        $zohoData = (new ZohoController)->getRecords($customerEmail);
                        //dd($zohoData);
                        Log::info("zoho data",$zohoData);
                        // verify customer with Zoho
                        if ($zohoData['status'] == 'success') {
                            // prepare payload for Zoho
                            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
                            $bigCommerceCustomerFormValueUrl = $url . 'customers/form-field-values?customer_id=' . $customerId;
                            $getCustomerFormFieldsDetails = (new CustomerController)->ApiCall($bigCommerceCustomerFormValueUrl,  $payload = '', $method = "GET");
                            $bigcommerceCustomerData = array(
                                'Customer_No' => $getCustomerFormFieldsDetails['responseData']['data'][13]['value'] ? $getCustomerFormFieldsDetails['responseData']['data'][13]['value'] : '',
                                'entityId' => $zohoData['data']['entityId'] ? $zohoData['data']['entityId'] : '',
                                'Contact_Customer_Type' => $zohoData['data']['Contact_Customer_Type'] ? $zohoData['data']['Contact_Customer_Type'] : '',
                                'Currency' => $zohoData['data']['Currency'] ? $zohoData['data']['Currency'] : '',
                            );
                            $customerPayload = $this->createPayloadForZoho($bigcommerceCustomerData, $apiResponseData['data']);
                            // dd($customerPayload);
                            if ($customerPayload['status']) {
                                // Save customer data into App database
                                $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $customerPayload['customerPayload']['bigc_customer_id']), $customerPayload['customerPayload']);

                                // Save customer address data into App database
                                foreach ($customerPayload['customerAddressPayload'] as $address) {
                                    $dbAddressDataInsertResponse = DB::table('bigc_customer_addresses')->updateOrInsert(array("address_id" => $address['address_id']), $address);
                                }

                                Log::info('Database customer insert response', array(
                                    'bcCustomerDataInsertResponse' => $dbCustomerDataInsertResponse,
                                    'bcCustomerAddressDataInsertResponse' => $dbAddressDataInsertResponse
                                ));

                                //$customerPayload['customerPayload']['zoho_contact_customer_type'] = "";

                                // Update customer data into Zoho
                                $updateZohoResponse = (new ZohoController)->updateRecords($customerPayload['customerPayload']);
                                Log::info('zohoUpdateApiResponse', array($updateZohoResponse));
                            } else {
                                Log::error('payload failed', $customerPayload);
                                return $customerPayload;
                            }
                            // get customer data from Spire
                            $authorizationSpire = config('config.Spire_Api_Auth');
                            $url =  config('config.Spire_Customer_Api_Url') . '?filter={"customerNo":"' . $zohoData['data']['Fixed_Customer_No'] . '"}';
                            $spireApiResponse = call_curl($url, $method = "GET",  $payload = '', $authorizationSpire);
                            $spireApiResponseArray = json_decode($spireApiResponse['response'], true);
                            Log::info(
                                "spire customer request data",
                                array(
                                    "SpireURL" => $url,
                                    "spire customer api response" => $spireApiResponse
                                )
                            );
                            if (!empty($spireApiResponseArray['records'][0]['paymentTerms']['code'])) {
                                $termsData = array(
                                    array(
                                        "name" => "paymentTermCode",
                                        "value" => $spireApiResponseArray['records'][0]['paymentTerms']['code'],
                                        "customer_id" => $customerId
                                    ), array(
                                        "name" => "paymentTermCode",
                                        "value" => "COD",
                                        "customer_id" => $customerId
                                    ),
                                    array(
                                        "name" => "Spire Currency",
                                        "value" => $spireApiResponseArray['records'][0]['currency'],
                                        "customer_id" => $customerId
                                    )
                                );
                                $this->updatePaymentTermsDataInBc($termsData);
                            }

                            // Get Spire address ID and store into Big Commerce Address
                            $url =  config('config.Spire_Address_Api_Url') . '?filter={"linkNo":"' . $zohoData['data']['Fixed_Customer_No'] . '"}';
                            $spireAddressApiResponse = call_curl($url, $method = "GET",  $payload = '', $authorizationSpire);
                            $spireAddressApiResponseArray = json_decode($spireAddressApiResponse['response'], true);
                            Log::info(
                                "spire request data",
                                array(
                                    "SpireURL" => $url,
                                    "spire authorizationSpire" => $authorizationSpire
                                )
                            );
                            Log::info("spire curl response", $spireAddressApiResponse);
                            Log::info("spire address api response", $spireAddressApiResponse);

                            
                            // Flag to identify customer is an Ordering contact or not
                            //if (isset($zohoData['data']['Contact_Customer_Type'])) {
                            if ($zohoData['data']['Contact_Customer_Type'] == 'Ordering') {
                                $customerPayload['customerPayload']['payment_terms_code'] = $spireApiResponseArray['records'][0]['paymentTerms']['code'];
                                $customerPayload['customerPayload']['payment_terms_description'] = $spireApiResponseArray['records'][0]['paymentTerms']['description'];
                                $customerPayload['customerPayload']['zoho_contact_customer_type'] = $zohoData['data']['Contact_Customer_Type'];
                                $customerPayload['customerPayload']['zoho_approve_web_access'] = $zohoData['data']['Approve_Web_Access'];
                                $customerPayload['customerPayload']['zoho_account_id'] = $zohoData['data']['Account_id'];
                                $customerPayload['customerPayload']['zoho_account_name'] = $zohoData['data']['Account_Name'];

                                $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $customerPayload['customerPayload']['bigc_customer_id']), $customerPayload['customerPayload']);
                                Log::info('Case1 as Ordering process', array($dbCustomerDataInsertResponse));
                            } else {
                                // set customer as not Ordering contact
                                $customerPayload['customerPayload']['zoho_contact_customer_type'] = 'Ordering';
                                // update into Database
                                $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $customerPayload['customerPayload']['bigc_customer_id']), $customerPayload['customerPayload']);

                                // Update on Zoho                         
                                $updateZohoResponse = (new ZohoController)->updateRecords($customerPayload['customerPayload']);

                                Log::info('Case2 as Non Ordering process', array($dbCustomerDataInsertResponse, $updateZohoResponse));
                            }
                            // } else {
                            // return "Ordering contact is NULL!";
                            // }
                        } else {
                            Log::error('Zoho not verified the customer ', array($zohoData));
                            return $zohoData;
                        }
                    } else {
                        Log::error('Big Commerce customer data empty ', array($apiResponseData));
                        return "Data not found!";
                    }
                } else {
                    Log::error('Big Commerce customer API failed - ' . $apiResponse);
                    return $apiResponse;
                }
            }
            // Case New customer on Big Commerce
            if ($webhookData['scope'] == 'store/customer/created') {
                // New customer on Big Commerce
                if ($apiResponse['status_code'] == 200) {
                    $apiResponseData = json_decode($apiResponse['data'], true);
                    if (!empty($apiResponseData['data'])) {
                        $customerEmail = $apiResponseData['data'][0]['email'];
                        // Get customer data from ZOHO CRM
                        $zohoData = (new ZohoController)->getRecords($customerEmail);
                        //dd($zohoData);
                        // verify customer with Zoho
                        if ($zohoData['status'] == 'success') {
                            // prepare payload for Zoho

                            $customerPayload = $this->createPayloadForZoho($zohoData['data'], $apiResponseData['data']);
                            // dd($customerPayload);
                            if ($customerPayload['status']) {
                                // Save customer data into App database
                                $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $customerPayload['customerPayload']['bigc_customer_id']), $customerPayload['customerPayload']);

                                // Save customer address data into App database
                                foreach ($customerPayload['customerAddressPayload'] as $address) {
                                    $dbAddressDataInsertResponse = DB::table('bigc_customer_addresses')->updateOrInsert(array("address_id" => $address['address_id']), $address);
                                }

                                Log::info('Database customer insert response', array(
                                    'bcCustomerDataInsertResponse' => $dbCustomerDataInsertResponse,
                                    'bcCustomerAddressDataInsertResponse' => $dbAddressDataInsertResponse
                                ));

                                $customerPayload['customerPayload']['zoho_contact_customer_type'] = "";

                                // Update customer data into Zoho
                                $updateZohoResponse = (new ZohoController)->updateRecords($customerPayload['customerPayload']);
                                Log::info('zohoUpdateApiResponse', array($updateZohoResponse));
                            } else {
                                Log::error('payload failed', $customerPayload);
                                return $customerPayload;
                            }
                            // get data from Spire
                            $authorizationSpire = config('config.Spire_Api_Auth');
                            $url =  config('config.Spire_Customer_Api_Url') . '?filter={"customerNo":"' . $zohoData['data']['Fixed_Customer_No'] . '"}';
                            $spireApiResponse = call_curl($url, $method = "GET",  $payload = '', $authorizationSpire);
                            $spireApiResponseArray = json_decode($spireApiResponse['response'], true);
                            if (!empty($spireApiResponseArray['records'][0]['paymentTerms']['code'])) {
                                $termsData = array(
                                    array(
                                        "name" => "paymentTermCode",
                                        "value" => $spireApiResponseArray['records'][0]['paymentTerms']['code'],
                                        "customer_id" => $customerId
                                    ), array(
                                        "name" => "paymentTermCode",
                                        "value" => "COD",
                                        "customer_id" => $customerId
                                    ),
                                    array(
                                        "name" => "Spire Currency",
                                        "value" => $spireApiResponseArray['records'][0]['currency'],
                                        "customer_id" => $customerId
                                    )
                                );
                                $this->updatePaymentTermsDataInBc($termsData);
                            }


                            // Get Spire address ID and store into Big Commerce Address
                            $url =  config('config.Spire_Address_Api_Url') . '?filter={"linkNo":"' . $zohoData['data']['Fixed_Customer_No'] . '"}';
                            $spireAddressApiResponse = call_curl($url, $method = "GET",  $payload = '', $authorizationSpire);
                            $spireAddressApiResponseArray = json_decode($spireAddressApiResponse['response'], true);
                            Log::info("spire address api response", $spireAddressApiResponse);



                            //die;
                            // Flag to identify customer is an Ordering contact or not                           
                            if ($zohoData['data']['Contact_Customer_Type'] == 'Ordering') {
                                $customerPayload['customerPayload']['payment_terms_code'] = $spireApiResponseArray['records'][0]['paymentTerms']['code'];
                                $customerPayload['customerPayload']['payment_terms_description'] = $spireApiResponseArray['records'][0]['paymentTerms']['description'];
                                $customerPayload['customerPayload']['zoho_contact_customer_type'] = $zohoData['data']['Contact_Customer_Type'];
                                $customerPayload['customerPayload']['zoho_approve_web_access'] = $zohoData['data']['Approve_Web_Access'];

                                // update into Database
                                $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $customerPayload['customerPayload']['bigc_customer_id']), $customerPayload['customerPayload']);
                                Log::info('Case1 as Ordering process', array($dbCustomerDataInsertResponse));
                            } else {
                                // set customer as not Ordering contact
                                $customerPayload['customerPayload']['zoho_contact_customer_type'] = 'Ordering';
                                // update into Database
                                $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $customerPayload['customerPayload']['bigc_customer_id']), $customerPayload['customerPayload']);

                                // Update on Zoho                         
                                $updateZohoResponse = (new ZohoController)->updateRecords($customerPayload['customerPayload']);

                                Log::info('Case2 as Non Ordering process', array($dbCustomerDataInsertResponse, $updateZohoResponse));
                            }
                        } else {
                            Log::error('Zoho not verified the customer', array($zohoData));
                            // prepare payload for Zoho
                            //$fixedCustomerNo = rand(1000, 10000) . $apiResponseData['data'][0]['company'];
                            $zohoInsertData = array(
                                'entityId' => '',
                                'Currency' => 'COD',
                                'Customer_No' => 'Unassigned',
                                'Contact_Customer_Type' => 'Ordering'
                            );
                            Log::info('customerData', array($apiResponseData['data']));
                            //echo "<pre>";print_r($apiResponseData['data']);die;
                            $customerPayload = $this->createPayloadForZoho($zohoInsertData, $apiResponseData['data']);
                            //echo "<pre>";print_r($customerPayload);die;
                            if ($customerPayload['status']) {
                                // Save customer data into App database
                                $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $customerPayload['customerPayload']['bigc_customer_id']), $customerPayload['customerPayload']);

                                // Save customer address data into App database
                                foreach ($customerPayload['customerAddressPayload'] as $address) {
                                    $dbAddressDataInsertResponse = DB::table('bigc_customer_addresses')->updateOrInsert(array("address_id" => $address['address_id']), $address);
                                }
                                $formFields = array(
                                    array(
                                        'name' => 'zoho_currency',
                                        'value' => $zohoInsertData['Currency'] ? $zohoInsertData['Currency'] : '',
                                        'customer_id' => $customerPayload['customerPayload']['bigc_customer_id'] ? $customerPayload['customerPayload']['bigc_customer_id'] : null
                                    ),
                                    array(
                                        'name' => 'zoho_fixed_customer_no',
                                        'value' => $zohoInsertData['Customer_No'] ? $zohoInsertData['Customer_No'] : '',
                                        'customer_id' => $customerPayload['customerPayload']['bigc_customer_id'] ? $customerPayload['customerPayload']['bigc_customer_id'] : null
                                    )
                                );
                                $updateFormFields = $this->updatePaymentTermsDataInBc($formFields);

                                Log::info('Database customer insert response', array(
                                    'bcCustomerDataInsertResponse' => $dbCustomerDataInsertResponse,
                                    'bcCustomerAddressDataInsertResponse' => $dbAddressDataInsertResponse
                                ));

                                //$customerPayload['customerPayload']['zoho_contact_customer_type'] = "Ordering";

                                // Update customer data into Zoho
                                $updateZohoResponse = (new ZohoController)->insertRecordsZoho($customerPayload['customerPayload']);
                                Log::info('zohoUpdateApiResponse', array($updateZohoResponse));
                                $statusCode = $updateZohoResponse['HTTP Status Code'];
                                if ($statusCode == '201') {
                                    $zohoEntityId = $updateZohoResponse['Details']['id'];
                                    $bigc_customer_id = $updateZohoResponse['bigc_customer_id'];
                                    $dbCustomerDataInsertResponse = DB::table('bigc_customers')->updateOrInsert(array("bigc_customer_id" => $bigc_customer_id), ['zoho_entityId' => $zohoEntityId, 'zoho_customer_no' => $zohoInsertData['Customer_No']]);
                                }
                            } else {
                                Log::error('payload failed', $customerPayload);
                                return $customerPayload;
                            }
                        }
                    } else {
                        Log::error('Big Commerce customer data empty ', array($apiResponseData));
                        return "Data not found!";
                    }
                } else {
                    Log::error('Big Commerce customer API failed - ' . $apiResponse);
                    return $apiResponse;
                }
            }
        } else {
            Log::info('webhook response is empty!', $webhookData);
        }
    }

    public function curlRequest($customerId = '')
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => "https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers?include=addresses,formfields&id:in=" . $customerId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array("x-auth-token: tqhvp7fmyqr438pewjwtcwi1vggxpky"),
            )
        );

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            return $response = array(
                "cURL Error #:" => $err,
                "status_code" => $statusCode
            );
        } else {
            return $response = array(
                "data" => $response,
                "status_code" => $statusCode
            );
        }
    }

    public function updatePaymentTermsDataInBc($body)
    {
        
        if (!empty($body)) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers/form-field-values",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/json",
                    "x-auth-token: tqhvp7fmyqr438pewjwtcwi1vggxpky"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return "cURL Error #:" . $err;
            } else {
                return $response;
            }
        }
    }

    public function createPayloadForZoho($zohoContactData = null, $bigCommerceCustomerData = null)
    {
        Log::info("bigCommerceCustomerData", $bigCommerceCustomerData);
        if (!empty($zohoContactData) && !empty($bigCommerceCustomerData)) {
            //echo "<pre>";print_r($bigCommerceCustomerData);die;
            $customerPayload = array(
                'bigc_customer_id' => $bigCommerceCustomerData[0]['id'] ? $bigCommerceCustomerData[0]['id'] : null,
                'company_name' => $bigCommerceCustomerData[0]['company'] ? $bigCommerceCustomerData[0]['company'] : null,
                'first_name' =>   $bigCommerceCustomerData[0]['first_name'] ? $bigCommerceCustomerData[0]['first_name'] : null,
                'last_name' =>  $bigCommerceCustomerData[0]['last_name'] ? $bigCommerceCustomerData[0]['last_name'] : null,
                'email' =>  $bigCommerceCustomerData[0]['email'] ? $bigCommerceCustomerData[0]['email'] : null,
                'title' =>  $bigCommerceCustomerData[0]['form_fields'][4]['value'] ? $bigCommerceCustomerData[0]['form_fields'][4]['value'] : null,
                'phone' => $bigCommerceCustomerData[0]['phone'] ? $bigCommerceCustomerData[0]['phone'] : null,
                'zoho_entityId' => $zohoContactData['entityId'] ? $zohoContactData['entityId'] : null,
                'zoho_currency' => $zohoContactData['Currency'] ? $zohoContactData['Currency'] : null,
                'zoho_customer_no' => $zohoContactData['Customer_No'] ? $zohoContactData['Customer_No'] : null,
                'zoho_contact_customer_type' => isset($zohoContactData['Contact_Customer_Type']) ? $zohoContactData['Contact_Customer_Type'] : null,
            );

            if ($bigCommerceCustomerData[0]['form_fields'][0]['name'] == 'Address Line 3') {
                $customerPayload['address_line3'] =  $bigCommerceCustomerData[0]['form_fields'][0]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][1]['name'] == 'Address Line 4') {
                $customerPayload['address_line4'] =  $bigCommerceCustomerData[0]['form_fields'][1]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][3]['name'] == 'Website ') {
                $customerPayload['website'] =  $bigCommerceCustomerData[0]['form_fields'][3]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][4]['name'] == 'Title') {
                $customerPayload['title'] =  $bigCommerceCustomerData[0]['form_fields'][4]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][5]['name'] == 'Address Line 1') {
                $customerPayload['address_line1'] =  $bigCommerceCustomerData[0]['form_fields'][5]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][6]['name'] == 'Address Line 2') {
                $customerPayload['address_line2'] =  $bigCommerceCustomerData[0]['form_fields'][6]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][7]['name'] == 'State') {
                $customerPayload['state'] =  $bigCommerceCustomerData[0]['form_fields'][7]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][8]['name'] == 'City') {
                $customerPayload['city'] =  $bigCommerceCustomerData[0]['form_fields'][8]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][9]['name'] == 'Zip Code') {
                $customerPayload['zipcode'] =  $bigCommerceCustomerData[0]['form_fields'][9]['value'];
            }
            if ($bigCommerceCustomerData[0]['form_fields'][10]['name'] == 'Country') {
                $customerPayload['country'] =  $bigCommerceCustomerData[0]['form_fields'][10]['value'];
            }


            foreach ($bigCommerceCustomerData[0]['addresses'] as $key => $val) {
                //Log::info($val);die;
                $addresPayload[$key] = array(
                    "address_id" => $val['id'],
                    "customer_id" => $val['customer_id'],
                    "address1" => $val['address1'],
                    "address2" => $val['address2'],
                    "address3" => isset($val['form_fields'][0]['value']) ? $val['form_fields'][0]['value'] : '',
                    "address4" => isset($val['form_fields'][1]['value']) ? $val['form_fields'][1]['value'] : '',
                    "address_type" => $val['address_type'],
                    "city" => $val['city'],
                    "country" => $val['country'],
                    "country_code" => $val['country_code'],
                    "first_name" => $val['first_name'],
                    "last_name" => $val['last_name'],
                    "phone" => $val['phone'],
                    "postal_code" => $val['postal_code'],
                    "state_or_province" => $val['state_or_province'],
                    "Website" => isset($val['form_fields'][2]['value']) ? $val['form_fields'][2]['value'] : ''
                );
            }
            return array(
                'status' => true,
                'customerPayload' => $customerPayload,
                'customerAddressPayload' => $addresPayload
            );
        } else {
            return array(
                'status' => false,
                'message' => "Payload is empty!"
            );
        }
    }


    public function curlPostRequest($payload = '', $method = '')
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers",
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

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }
    public function getZohoWebhook(Request $request)
    {
        
        $data = $request->all();
        Log::info($data);
    
        if ($data) {
            //echo "<pre>";print_r($data);die;
            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
            $bigCommerceCustomerUrl = $url . 'customers?email%3Ain=' . $data['email'];
            $getCustomerDetails = (new CustomerController)->ApiCall($bigCommerceCustomerUrl,  $payload = '', $method = "GET");
            if ($getCustomerDetails['status'] == 200) {
                if ($getCustomerDetails['responseData']['data']) {
                    $customerId = $getCustomerDetails['responseData']['data'][0]['id'];
                    $bigCommerceCustomerFormValueUrl = $url . 'customers/form-field-values?customer_id=' . $customerId;
                    $getCustomerFormFieldsDetails = (new CustomerController)->ApiCall($bigCommerceCustomerFormValueUrl,  $payload = '', $method = "GET");
                    //  echo "<pre>";
                    //  print_r($getCustomerFormFieldsDetails['responseData']['data']);
                    //  die;
                    //echo $data['allowedAddresses'];die;
                    if ($data['approveWebAccess'] == 'true') {
                        $approveWebAccess = 'Yes';
                    } else {
                        $approveWebAccess = 'No';
                    }
                    if ($data['allowedAddresses'] == null) {
                        $allowedAddresses = '';
                    } else {
                        $allowedAddresses = $data['allowedAddresses'];
                    }
                    $customerNumberBigC = '';
                    $aproveWebBigC = '';
                    $allowedAddBigC = '';
                    if ($getCustomerFormFieldsDetails['responseData']['data']) {
                        foreach ($getCustomerFormFieldsDetails['responseData']['data'] as $formKey => $formFiledsValue) {
                            if ($formFiledsValue == 'zoho_fixed_customer_no') {
                                $customerNumberBigC = $formFiledsValue;
                            }
                            if ($formFiledsValue == 'Approve Web Access') {
                                $aproveWebBigC = $formFiledsValue;
                            }
                            if ($formFiledsValue == 'Allowed Addresses') {
                                $allowedAddBigC = $formFiledsValue;
                            }
                        }
                    }
                    Log::info('DataDB', array($getCustomerFormFieldsDetails['responseData']['data']));
                    if ($customerNumberBigC != $data['fixedCustomerNo'] || $aproveWebBigC != $approveWebAccess  || $allowedAddBigC != $allowedAddresses) {
                    $zohoData = (new ZohoController)->getRecords($data['email']);
                    Log::info('zohoData', array($zohoData));
                    // echo "<pre>";
                    // print_r($zohoData);
                    // die;
                    // verify customer with Zoho
                    if ($zohoData['status'] == 'success') {
                        // prepare payload for Big commerce
                        $customerPayload = $this->createPayloadForBigcommerce($zohoData['data'], $customerId);
                        Log::info('formFieldsValue: ', $customerPayload['formFields']);
                        //     echo "<pre>";
                        // print_r($customerPayload['customerPayloaddatabase']);
                        // die;
                        if ($customerPayload['status']) {
                            // Update customer data into App database
                            $dbCustomerDataUpdateResponse = DB::table('bigc_customers')->updateOrInsert(
                                ["bigc_customer_id" => $customerPayload['customerPayloaddatabase']['bigc_customer_id']],
                                $customerPayload['customerPayloaddatabase']
                            );
                            $bigCPayload = [$customerPayload['customerPayload']];
                            // Update customer data into big commerce
                            $updateZohoResponse = $this->curlPostRequest($bigCPayload, $method = 'PUT');
                            Log::info('bigcommerceUpdateApiResponse', array($updateZohoResponse));
                            $customFieldsPayloadChunks = array_chunk($customerPayload['formFields'], ceil(count($customerPayload['formFields']) / (count($customerPayload['formFields'])/10)));
                            Log::info('formFieldsApi', $customFieldsPayloadChunks);
                            foreach ($customFieldsPayloadChunks as $formFieldskey => $customFieldsPayloadChunksData) {
                                Log::info('formFieldsloop', $customFieldsPayloadChunksData);
                                $updateFormFields[] = $this->updatePaymentTermsDataInBc($customFieldsPayloadChunksData);
                            }
                            Log::info('formFieldsApiData', array($updateFormFields));
                            die;
                        } else {
                            Log::error('payload failed');
                            return array();
                        }
                    } else {
                        Log::error('Zoho not verified the customer ', array($zohoData));
                        return $zohoData;
                    }
                    } else {
                        Log::error('No update found ', array());
                        return false;
                    }
                } else {
                    $result['Status'] = 'False';
                    $result['Message'] = 'User not matched!';
                    $result['Result'] = array();
                    return json_encode($result);
                    die;
                }
            } else {
                $result['Status'] = $getCustomerDetails['status'];
                $result['Message'] = $getCustomerDetails['title'];
                $result['Result'] = array();
                return json_encode($result);
                die;
            }
        }
    }
    public function createPayloadForBigcommerce($zohoContactData = null, $bigCommerceCustomerId = null)
    {
        if (!empty($zohoContactData) && !empty($bigCommerceCustomerId)) {
            $customerPayload = array(
                'company' => $zohoContactData['Company_Name'] ? $zohoContactData['Company_Name'] : null,
                'first_name' =>   $zohoContactData['First_Name'] ? $zohoContactData['First_Name'] : null,
                'last_name' =>  $zohoContactData['Last_Name'] ? $zohoContactData['Last_Name'] : null,
                'phone' => $zohoContactData['Phone'] ? $zohoContactData['Phone'] : null,
                'notes' => $zohoContactData['Fixed_Customer_No'] ? $zohoContactData['Fixed_Customer_No'] : null,
                'id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null,
            );
            if ($zohoContactData['Approve_Web_Access'] == 1) {
                $approveWebAccess = 'Yes';
            } else {
                $approveWebAccess = 'No';
            }
            $formFields = array(
                array(
                    'name' => 'zoho_entityID',
                    'value' => $zohoContactData['entityId'] ? $zohoContactData['entityId'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'zoho_currency',
                    'value' => $zohoContactData['Currency'] ? $zohoContactData['Currency'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'zoho_fixed_customer_no',
                    'value' => $zohoContactData['Fixed_Customer_No'] ? $zohoContactData['Fixed_Customer_No'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Title',
                    'value' => $zohoContactData['Title'] ? $zohoContactData['Title'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Approve Web Access',
                    'value' => $approveWebAccess ? $approveWebAccess : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Allowed Addresses',
                    'value' => $zohoContactData['Allowed_Addresses'] ? $zohoContactData['Allowed_Addresses'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Address Line 1',
                    'value' => $zohoContactData['Mailing_Street'] ? $zohoContactData['Mailing_Street'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Address Line 2',
                    'value' => $zohoContactData['Address_2'] ? $zohoContactData['Address_2'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Address Line 3',
                    'value' => $zohoContactData['Address_3'] ? $zohoContactData['Address_3'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Address Line 4',
                    'value' => $zohoContactData['Address_4'] ? $zohoContactData['Address_4'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'State',
                    'value' => $zohoContactData['Mailing_State'] ? $zohoContactData['Mailing_State'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'City',
                    'value' =>  $zohoContactData['Mailing_City'] ? $zohoContactData['Mailing_City'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Zip Code',
                    'value' =>  $zohoContactData['Mailing_Zip'] ? $zohoContactData['Mailing_Zip'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                ),
                array(
                    'name' => 'Country',
                    'value' =>  $zohoContactData['Mailing_Country'] ? $zohoContactData['Mailing_Country'] : '',
                    'customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null
                )
            );
            $customerPayloaddatabase = array(
                'company_name' => $zohoContactData['Company_Name'] ? $zohoContactData['Company_Name'] : null,
                'first_name' =>   $zohoContactData['First_Name'] ? $zohoContactData['First_Name'] : null,
                'last_name' =>  $zohoContactData['Last_Name'] ? $zohoContactData['Last_Name'] : null,
                'email' =>  $zohoContactData['email'] ? $zohoContactData['email'] : null,
                'phone' => $zohoContactData['Phone'] ? $zohoContactData['Phone'] : null,
                'title' => $zohoContactData['Title'] ? $zohoContactData['Title'] : null,
                'zoho_contact_customer_type' => $zohoContactData['Contact_Customer_Type'] ? $zohoContactData['Contact_Customer_Type'] : null,
                'zoho_entityId' => $zohoContactData['entityId'] ? $zohoContactData['entityId'] : null,
                'zoho_currency' => $zohoContactData['Currency'] ? $zohoContactData['Currency'] : null,
                'zoho_customer_no' => $zohoContactData['Fixed_Customer_No'] ? $zohoContactData['Fixed_Customer_No'] : null,
                'zoho_Approve_web_access' => $zohoContactData['Approve_Web_Access'] ? $zohoContactData['Approve_Web_Access'] : null,
                'allowed_addresses' => $zohoContactData['Allowed_Addresses'] ? $zohoContactData['Allowed_Addresses'] : null,
                'bigc_customer_id' => $bigCommerceCustomerId ? $bigCommerceCustomerId : null,
            );
            return array(
                'status' => true,
                'customerPayload' => $customerPayload,
                'customerPayloaddatabase' => $customerPayloaddatabase,
                'formFields' => $formFields,
                //'customerAddressPayload' => $addresPayload
            );
        } else {
            return array(
                'status' => false,
                'message' => "Payload is empty!"
            );
        }
    }
}
