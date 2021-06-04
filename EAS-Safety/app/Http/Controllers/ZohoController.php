<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';
use Illuminate\Support\Facades\DB;
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
//use \DB;
use App\OauthTokens;
use Config;
//The controller class containing functionalities of get, insert data
class ZohoController extends Controller
{
    protected $configuration;
    //intialize and configure a client for ZOHO CRM

    // public function __construct()
    // {
    //     $this->configuration = array(
    //         "client_id" => '1000.C9210HML8ZP01DRMWV8DNOYVM30YVA',
    //         "client_secret" => '286218a4bc498ddbd8ce432f504368f0001fd67ecf',
    //         "redirect_uri" => 'http://192.168.6.2:8000/api/getToken',
    //         "currentUserEmail" => 'ratnakarm@chetu.com'
    //     );
    //     ZCRMRestClient::initialize($this->configuration);
    // }

    // function to get the customer information by Email
    public function getZohoRequest(){
        $headers = apache_request_headers();   
        //dd($headers);
        $accessToken = $this->getToken($headers);
        if($accessToken){
            $customerEmail = "faisalk10@chetu.com";
            $zohoCustomerRecord = $this->getRecords($customerEmail);
            // $zohoCustomerRecord = $this->getAllRecords();
            return $zohoCustomerRecord;
        } else {
            return array(
                "status"=>"error",
                "message"=>"Access token not created!"
            );
        }
    }
   
    /** function to generate access token from pre generated refresh token */
    public function getToken($zohoConfigurationData = null)
    {         
        $zohoConfigurationData =  json_decode($zohoConfigurationData['zoho-configuration'],true);
        
        $configuration = array(
            "client_id" => $zohoConfigurationData['client_id'],
            "client_secret" => $zohoConfigurationData['client_secret'],
            "redirect_uri" => $zohoConfigurationData['redirect_uri'],
            "currentUserEmail" => $zohoConfigurationData['currentUserEmail']
        );

       
        ZCRMRestClient::initialize($configuration);
        $oAuthClient = ZohoOAuth::getClientInstance();
        $refreshToken = $zohoConfigurationData['refresh_token'];
        $userIdentifier = $zohoConfigurationData['currentUserEmail'];
        $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
        $result = DB::connection('mysql')->table('oauthtokens')->get(); 
        foreach ($result as $row)
        {
            echo "Access Token:" . $row->accesstoken . '<br>';
            $this->access_token = $row->accesstoken;
        } 
        return $row->accesstoken;
               
    }

    public function getAllRecords() {
        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); // To get module instance
        $allRecords = $moduleIns->getRecords();
        dd($allRecords);
    }

    

    // get single record based on email
    public function getRecords($email = null)
    {

        $email = "faisalk10@chetu.com";
        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); // To get module instance
        try
        {
            $param_map=array("page"=>1,"per_page"=>10); // key-value pair containing all the parameters
           
            

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
    hhfsgdfh

    public function searchUsersByCriteria()
    {
        $configuration = array(
            "client_id" => '1000.C9210HML8ZP01DRMWV8DNOYVM30YVA',
            "client_secret" => '286218a4bc498ddbd8ce432f504368f0001fd67ecf',
            "redirect_uri" => 'http://192.168.6.2:8000/api/getToken',
            "currentUserEmail" => 'ratnakarm@chetu.com'
        );
        ZCRMRestClient::initialize($configuration);
        $oAuthClient = ZohoOAuth::getClientInstance();       
        $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken('1000.de6d1ee45e7fcc3ab1707e5547c50167.44b249602dc20667c917bb4b5c832c48', 'ratnakarm@chetu.com');

        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); // To get module instance
        $criteria="Modified_Time = 2019-02-19T12:05:23+05:30"; //criteria to search for
        $param_map=array("page"=>1,"per_page"=>1); // key-value pair containing all the parameters
        $response = $moduleIns->searchRecordsByCriteria($criteria,$param_map) ;// To get module records// $criteria to search for  to search for// $param_map-parameters key-value pair - optional
        $records = $response->getData(); // To get response data
        dd('$records');


        $orgIns = ZCRMRestClient::getOrganizationInstance('Contacts'); // to get the organization instance
        /* For VERSION <=2.0.6 $userInstances = $orgIns->searchUsersByCriteria("{criteria}", "{type}")->getData(); // to get the users of the organization based on criteria and type of the user(active,deleted,etc)*/
        dd($orgIns);
        $param_map=array("page"=>"1","per_page"=>"200"); // key-value pair containing all the parameters - optional
        $userInstances = $orgIns->searchUsersByCriteria("{criteria}", $param_map)->getData(); // to get the users of the organization based on criteria 
        dd($userInstances);
        foreach ($userInstances as $userInstance) {
            echo $userInstance->getId(); // to get the user id
            echo $userInstance->getCountry(); // to get the country of the user
            $roleInstance = $userInstance->getRole(); // to get the role of the user in form of ZCRMRole instance
            echo $roleInstance->getId(); // to get the role id
            
            
            echo $roleInstance->getName(); // to get the role name
            $customizeInstance = $userInstance->getCustomizeInfo(); // to get the customization information of the user in for of the ZCRMUserCustomizeInfo form
            if ($customizeInstance != null) {
                echo $customizeInstance->getNotesDesc(); // to get the note description
                echo $customizeInstance->getUnpinRecentItem(); // to get the unpinned recent items
                echo $customizeInstance->isToShowRightPanel(); // to check whether the right panel is shown
                echo $customizeInstance->isBcView(); // to check whether the business card view is enabled
                echo $customizeInstance->isToShowHome(); // to check whether the home is shown
                echo $customizeInstance->isToShowDetailView(); // to check whether the detail view is shows
            }
            echo $userInstance->getCity(); // to get the city of the user
            echo $userInstance->getSignature(); // to get the signature of the user
            echo $userInstance->getNameFormat(); // to get the name format of the user
            echo $userInstance->getLanguage(); // to get the language of the user
            echo $userInstance->getLocale(); // to get the locale of the user
            echo $userInstance->isPersonalAccount(); // to check whther this is a personal account
            echo $userInstance->getDefaultTabGroup(); // to get the default tab group
            echo $userInstance->getAlias(); // to get the alias of the user
            echo $userInstance->getStreet(); // to get the street name of the user
            $themeInstance = $userInstance->getTheme(); // to get the theme of the user in form of the ZCRMUserTheme
            if ($themeInstance != null) {
                echo $themeInstance->getNormalTabFontColor(); // to get the normal tab font color
                echo $themeInstance->getNormalTabBackground(); // to get the normal tab background
                echo $themeInstance->getSelectedTabFontColor(); // to get the selected tab font color
                echo $themeInstance->getSelectedTabBackground(); // to get the selected tab background
            }
            echo $userInstance->getState(); // to get the state of the user
            echo $userInstance->getCountryLocale(); // to get the country locale of the user
            echo $userInstance->getFax(); // to get the fax number of the user
            echo $userInstance->getFirstName(); // to get the first name of the user
            echo $userInstance->getEmail(); // to get the email id of the user
            echo $userInstance->getZip(); // to get the zip code of the user
            echo $userInstance->getDecimalSeparator(); // to get the decimal separator
            echo $userInstance->getWebsite(); // to get the website of the user
            echo $userInstance->getTimeFormat(); // to get the time format of the user
            $profile = $userInstance->getProfile(); // to get the user's profile in form of ZCRMProfile
            echo $profile->getId(); // to get the profile id
            echo $profile->getName(); // to get the name of the profile
            echo $userInstance->getMobile(); // to get the mobile number of the user
            echo $userInstance->getLastName(); // to get the last name of the user
            echo $userInstance->getTimeZone(); // to get the time zone of the user
            echo $userInstance->getZuid(); // to get the zoho user id of the user
            echo $userInstance->isConfirm(); // to check whether it is a confirmed user
            echo $userInstance->getFullName(); // to get the full name of the user
            echo $userInstance->getPhone(); // to get the phone number of the user
            echo $userInstance->getDob(); // to get the date of birth of the user
            echo $userInstance->getDateFormat(); // to get the date format
            echo $userInstance->getStatus(); // to get the status of the user
        }
    }

}