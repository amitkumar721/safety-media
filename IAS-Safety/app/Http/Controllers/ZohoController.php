<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';
use Illuminate\Http\Request;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\crud\ZCRMJunctionRecord;
use zcrmsdk\crm\crud\ZCRMNote;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMTax;
use zcrmsdk\crm\setup\users\ZCRMUser;
use zcrmsdk\crm\crud\ZCRMCustomView;
use zcrmsdk\crm\crud\ZCRMTag;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\oauth\ZohoOAuthClient;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use DB;
use App\OauthTokens;
use Config;
use Illuminate\Support\Facades\Auth;
use App\User;
//The controller class containing functionalities of get, insert data
class ZohoController extends Controller
{
    protected $configuration;
    //intialize and configure a client for ZOHO CRM

    public function __construct()
    {
        $this->configuration = array(
            "client_id" => '1000.C9210HML8ZP01DRMWV8DNOYVM30YVA',
            "client_secret" => '286218a4bc498ddbd8ce432f504368f0001fd67ecf',
            "redirect_uri" => 'https://safetymediainc.net/public/api/getToken',
            "currentUserEmail" => 'ratnakarm@chetu.com'
        );
        ZCRMRestClient::initialize($this->configuration);
        
    }


    public function getZohoData()
    {       
        
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
        } else {
            return array(
                "token" => 'Unauthorized call' // return unauthorized call
            );
        }

        $configuration = array(
            "client_id" => '1000.C9210HML8ZP01DRMWV8DNOYVM30YVA',
            "client_secret" => '286218a4bc498ddbd8ce432f504368f0001fd67ecf',
            "redirect_uri" => 'https://safetymediainc.net/public/api/getToken',
            "currentUserEmail" => 'ratnakarm@chetu.com',
            "refresh_token" => "1000.de6d1ee45e7fcc3ab1707e5547c50167.44b249602dc20667c917bb4b5c832c48"
        );
        $payload = array("email"=>"faialk10@chetu.com");
        $actionMethod = "getRecords";
        $urls = 'http://192.168.6.2:8000/api/getZohoRequest'; // EAS function url 
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $urls,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => json_encode($payload), // send payload to EAS
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "zoho-configuration:".json_encode($configuration), // send big commerce auth token
                "action: $actionMethod", // send big commerce API url to EAS
                "token: $token" // send IAS generated authenctication token to EAS
            ),

        ));
        $response = curl_exec($curl);
        return $response;
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);
        $userdata = User::all(); // query to get token form database
        $mydata = json_decode($response, TRUE);
        if ($userdata[0]['usertoken'] == $mydata['token']) { // verify the token of EAS and database
            if ($err) {
                return array( // return response
                    "status" => $mydata['status'],
                    "response" => $err,
                    "token" => 'Token matched'
                );
            } else {
                return array( // return response
                    "status" => $mydata['status'],
                    "response" => json_encode($mydata['bigcommercedata']),
                    "token" => 'Token matched'
                );
            }
        } else {
            return array( // return response
                "status" => $mydata['status'],
                "response" => $err,
                "token" => 'Token not matched'
            );
        }
    }

    
   

    /** function to generate access token from pre generated refresh token */
    public function getToken()
    {
        ZCRMRestClient::initialize($this->configuration); 
        $oAuthClient = ZohoOAuth::getClientInstance();
        $refreshToken = env('ZOHO_REFRESH_TOKEN');
        $userIdentifier = env('ZOHO_CURRENT_USER_EMAIL');
        //$oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
        $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken('1000.de6d1ee45e7fcc3ab1707e5547c50167.44b249602dc20667c917bb4b5c832c48', 'ratnakarm@chetu.com');
        //echo "<pre>";print_r($oAuthTokens);die;
        $result = DB::connection('mysql2')->table('oauthtokens')->get();       
        foreach ($result as $row)
        {
            echo "Access Token:" . $row->accesstoken . '<br>';
            $this->access_token = $row->accesstoken;
        }
        
    }

    public function getAllRecords() {
        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); // To get module instance
        $allRecords = $moduleIns->getRecords();
        dd($allRecords);
    }

    public function getRecords($email = null)
    {
        //$email = "faisalk10@chetu.com";
        //$email = "jpage@woodbine.com";
        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); // To get module instance
        try
        {
			$zohoSearchResponse = $moduleIns->searchRecordsByEmail($email);
            //dd($zohoSearchResponse);
			if($zohoSearchResponse){
                $zohoRecords = $zohoSearchResponse->getData();
               // dd($records);
				$zohoContactPayload = array();
				foreach ($zohoRecords as $record)
				{
					//echo "<pre>";print_r($record);die;
					$zohoContactPayload['email'] = $record->getFieldValue('Email');
					$zohoContactPayload['entityId'] = $record->getEntityId();
					$zohoContactPayload['Currency'] = $record->getFieldValue('Currency');
					$zohoContactPayload['Customer_No'] = $record->getFieldValue('Customer_No');
					$zohoContactPayload['Contact_Customer_Type'] = $record->getFieldValue('Contact_Customer_Type');
					$zohoContactPayload['Approve_Web_Access'] = $record->getFieldValue('Approve_Web_Access');
					$zohoContactPayload['Company_Name'] = $record->getFieldValue('Company_Name');
					$zohoContactPayload['Full_Name'] = $record->getFieldValue('Full_Name');
					$zohoContactPayload['Company_Type'] = $record->getFieldValue('Company_Type');
					$zohoContactPayload['Record_Image'] = $record->getFieldValue('Record_Image');
					$zohoContactPayload['BigCommerceUserID'] = $record->getFieldValue('BigCommerceUserID');
					$zohoContactPayload['First_Name'] = $record->getFieldValue('First_Name');
					$zohoContactPayload['Last_Name'] = $record->getFieldValue('Last_Name');
					$zohoContactPayload['Fixed_Customer_No'] = $record->getFieldValue('Fixed_Customer_No');
					$zohoContactPayload['Phone'] = $record->getFieldValue('Phone');
					$zohoContactPayload['Date_of_Birth'] = $record->getFieldValue('Date_of_Birth');
                    $zohoContactPayload['Title'] = $record->getFieldValue('Title');
                    $zohoContactPayload['Allowed_Addresses'] = $record->getFieldValue('Allowed_Addresses');
                    $zohoContactPayload['Mailing_Street'] = $record->getFieldValue('Mailing_Street');
                    $zohoContactPayload['Address_2'] = $record->getFieldValue('Address_2');
                    $zohoContactPayload['Address_3'] = $record->getFieldValue('Address_3');
                    $zohoContactPayload['Address_4'] = $record->getFieldValue('Address_4');
                    $zohoContactPayload['Mailing_State'] = $record->getFieldValue('Mailing_State');
                    $zohoContactPayload['Mailing_City'] = $record->getFieldValue('Mailing_City');
                    $zohoContactPayload['Mailing_Zip'] = $record->getFieldValue('Mailing_Zip');
                    $zohoContactPayload['Mailing_Country'] = $record->getFieldValue('Mailing_Country');
                    $zohoContactPayload['Account_id'] = $record->getFieldValue('Account_id');
                    $zohoContactPayload['Account_Name'] = $record->getFieldValue('Account_Name');
                }
               
				return array(
                    'status' => 'success',
                    'data' => $zohoContactPayload
                );
			}
        }
        catch(ZCRMException $ex)
        {
			if ($ex->getMessage() == 'No Content') {
				return array(
                    'message' => $ex->getMessage(),
                    'status' => $ex->getExceptionCode(),
                    'file' => $ex->getFile()
                );
			}            
        }
    }


    public function insertRecords(Request $request)
	{
        $moduleIns=ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); //to get the instance of the module
        $records=array();
        $record=ZCRMRecord::getInstance("Contacts",null);  //To get ZCRMRecord instance
        $record->setFieldValue("Subject","Invoice"); //This function use to set FieldApiName and value similar to all other FieldApis and Custom field
        $record->setFieldValue("Account_Name","chetu"); //This function is for Invoices module
        $record->setFieldValue("Last_Name","kumari");
        $record->setFieldValue("First_Name","Monika");
        $record->setFieldValue("Email","monika@chetu.com");
        /** Following methods are being used only by Inventory modules **/

        // echo "<pre>";print_r($record);
        // exit;

        $lineItem=ZCRMInventoryLineItem::getInstance(null);  //To get ZCRMInventoryLineItem instance
        $lineItem->setDescription("Product_description");  //To set line item description
        $lineItem ->setDiscount(5);  //To set line item discount
        $lineItem->setListPrice(100);  //To set line item list price
        
        $taxInstance1=ZCRMTax::getInstance("{tax_name}");  //To get ZCRMTax instance
        $taxInstance1->setPercentage(2);  //To set tax percentage
        $taxInstance1->setValue(50);  //To set tax value
        $lineItem->addLineTax($taxInstance1);  //To set line tax to line item
        
        $taxInstance1=ZCRMTax::getInstance("{tax_name}"); //to get the tax instance
        $taxInstance1->setPercentage(12); //to set the tax percentage
        $taxInstance1->setValue(50); //to set the tax value
        $lineItem->addLineTax($taxInstance1); //to add the tax to line item
        
        $lineItem->setProduct(ZCRMRecord::getInstance("Contacts","Last_Name"));  //To set product to line item
        $lineItem->setQuantity(100);  //To set product quantity to this line item
        
        $record->addLineItem($lineItem);   //to add the line item to the record

        array_push($records, $record); //pushing the record to the array 
        $responseIn=$moduleIns->createRecords($records); //updating the records
        foreach($responseIn->getEntityResponses() as $responseIns){
            echo "HTTP Status Code:".$responseIn->getHttpStatusCode();  //To get http response code
            echo "Status:".$responseIns->getStatus();  //To get response status
            echo "Message:".$responseIns->getMessage();  //To get response message
            echo "Code:".$responseIns->getCode();  //To get status code
            echo "Details:".json_encode($responseIns->getDetails());
        }
    }
    public function insertRecordsZoho($data)
	{
        //echo "<pre>";print_r($data);die;
        $moduleIns=ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); //to get the instance of the module
        $records=array();
        $record=ZCRMRecord::getInstance("Contacts",null);  //To get ZCRMRecord instance
        if (isset($data['first_name'])) {
            $record->setFieldValue("First_Name", $data['first_name']);
        }
        if (isset($data['last_name'])) {
            $record->setFieldValue("Last_Name", $data['last_name']);
        }
        if (isset($data['company_name'])) {
            $record->setFieldValue("Company_Name", $data['company_name']);
        }
        if (isset($data['phone'])) {
            $record->setFieldValue("Phone", $data['phone']);
        }
        if (isset($data['title'])) {
            $record->setFieldValue("Title", $data['title']);
        }
        if (isset($data['zoho_customer_no'])) {
            $record->setFieldValue("Fixed_Customer_No", $data['zoho_customer_no']);
        }
        if (isset($data['bigc_customer_id'])) {
            $record->setFieldValue("BigCommerceUserID", (string) $data['bigc_customer_id']);
        }
        if (isset($data['email'])) {
            $record->setFieldValue("Email", $data['email']);
        }
        if (isset($data['address_line1'])) {
            $record->setFieldValue("Mailing_Street", $data['address_line1']);
        }
        if (isset($data['address_line2'])) {
            $record->setFieldValue("Address_2", $data['address_line2']);
        }
        if (isset($data['address_line3'])) {
            $record->setFieldValue("Address_3", $data['address_line3']);
        }
        if (isset($data['address_line4'])) {
            $record->setFieldValue("Address_4", $data['address_line4']);
        }
        if (isset($data['state'])) {
            $record->setFieldValue("Mailing_State", $data['state']);
        }
        if (isset($data['city'])) {
            $record->setFieldValue("Mailing_City", $data['city']);
        }
        if (isset($data['zipcode'])) {
            $record->setFieldValue("Mailing_Zip", $data['zipcode']);
        }
        if (isset($data['country'])) {
            $record->setFieldValue("Mailing_Country", $data['country']);
        }
        $record->setFieldValue("Contact_Customer_Type", 'Ordering');
        
        /** Following methods are being used only by Inventory modules **/

        //   echo "<pre>";print_r($record);
        //   exit;

        $lineItem=ZCRMInventoryLineItem::getInstance(null);  //To get ZCRMInventoryLineItem instance
        $lineItem->setDescription("Product_description");  //To set line item description
        $lineItem ->setDiscount(5);  //To set line item discount
        $lineItem->setListPrice(100);  //To set line item list price
        
        $taxInstance1=ZCRMTax::getInstance("{tax_name}");  //To get ZCRMTax instance
        $taxInstance1->setPercentage(2);  //To set tax percentage
        $taxInstance1->setValue(50);  //To set tax value
        $lineItem->addLineTax($taxInstance1);  //To set line tax to line item
        
        $taxInstance1=ZCRMTax::getInstance("{tax_name}"); //to get the tax instance
        $taxInstance1->setPercentage(12); //to set the tax percentage
        $taxInstance1->setValue(50); //to set the tax value
        $lineItem->addLineTax($taxInstance1); //to add the tax to line item
        
        $lineItem->setProduct(ZCRMRecord::getInstance("Contacts","Last_Name"));  //To set product to line item
        $lineItem->setQuantity(100);  //To set product quantity to this line item
        
        $record->addLineItem($lineItem);   //to add the line item to the record

        array_push($records, $record); //pushing the record to the array 
        $responseIn=$moduleIns->createRecords($records); //updating the records
        foreach($responseIn->getEntityResponses() as $responseIns){
           return $responseZoho =  array(
             "HTTP Status Code" => $responseIn->getHttpStatusCode(), //To get http response code
             "Status" => $responseIns->getStatus(),  //To get response status
             "Message" => $responseIns->getMessage(),  //To get response message
             "Code" => $responseIns->getCode(), //To get status code
             "Details" => $responseIns->getDetails(),
             'bigc_customer_id' => $data['bigc_customer_id']
           );
        }
	}


	public function updateRecords($data)
	{      
        // $zohoEntityId = "4227565000000534002";
        $zohoEntityId = $data['zoho_entityId'];
       
		$record = ZCRMRestClient::getInstance()->getRecordInstance("Contacts", $zohoEntityId);

        if (isset($data['first_name'])) {
            $record->setFieldValue("First_Name", $data['first_name']);
        }
        if (isset($data['last_name'])) {
            $record->setFieldValue("Last_Name", $data['last_name']);
        }
        if (isset($data['title'])) {
            $record->setFieldValue("Title", $data['title']);
        }
        if (isset($data['company_name'])) {
            $record->setFieldValue("Company_Name", $data['company_name']);
        }
        if (isset($data['phone'])) {
            $record->setFieldValue("Phone", $data['phone']);
        }
        if (isset($data['zoho_contact_customer_type'])) {
            $record->setFieldValue("Contact_Customer_Type", $data['zoho_contact_customer_type']);
        }
        if (isset($data['zoho_customer_no'])) {
            $record->setFieldValue("Fixed_Customer_No", $data['zoho_customer_no']);
        }
        if (isset($data['zoho_approve_web_access'])) {
            $record->setFieldValue("Approve_Web_Access", $data['zoho_approve_web_access']);
        }
        if (isset($data['address_line1'])) {
            $record->setFieldValue("Mailing_Street", $data['address_line1']);
        }
        if (isset($data['address_line2'])) {
            $record->setFieldValue("Address_2", $data['address_line2']);
        }
        if (isset($data['address_line3'])) {
            $record->setFieldValue("Address_3", $data['address_line3']);
        }
        if (isset($data['address_line4'])) {
            $record->setFieldValue("Address_4", $data['address_line4']);
        }
        if (isset($data['state'])) {
            $record->setFieldValue("Mailing_State", $data['state']);
        }
        if (isset($data['city'])) {
            $record->setFieldValue("Mailing_City", $data['city']);
        }
        if (isset($data['zipcode'])) {
            $record->setFieldValue("Mailing_Zip", $data['zipcode']);
        }
        if (isset($data['country'])) {
            $record->setFieldValue("Mailing_Country", $data['country']);
        }
        
	    $responseIns = $record->update();//to update the record
       
        $response = array(
            "HTTP Status Code:" => $responseIns->getHttpStatusCode(),
            "Status:"=> $responseIns->getStatus(),
            "Message:" => $responseIns->getMessage(),
            "Code:" => $responseIns->getCode(),
            "Details:" => json_encode($responseIns->getDetails())
        );
        return $response;
        //print_r($response); 
    }

    public function updateCustomerNoOnZoho($data)
	{      
        // $zohoEntityId = "4227565000000534002";
        $zohoEntityId = $data['zoho_entityId'];
       
		$record = ZCRMRestClient::getInstance()->getRecordInstance("Contacts", $zohoEntityId);
   
        
        if (isset($data['zoho_customer_no'])) {
            $record->setFieldValue("Fixed_Customer_No", $data['zoho_customer_no']);
			$record->setFieldValue("company_name", $data['companyname']);
            $responseIns = $record->update();
        } else {
            $response = array("zoho customer number not exixts");
        }
      
        $response = array(
            "HTTP Status Code:" => $responseIns->getHttpStatusCode(),
            "Status:"=> $responseIns->getStatus(),
            "Message:" => $responseIns->getMessage(),
            "Code:" => $responseIns->getCode(),
            "Details:" => json_encode($responseIns->getDetails())
        );
        return $response;
        
    }

}