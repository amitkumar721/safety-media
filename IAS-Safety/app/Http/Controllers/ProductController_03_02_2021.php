<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;


class ProductController
{
    /**
     * @OA\Post(
     * path="/createProduct",
     * summary="Create product on big commerce",
     * description="Create product on big commerce using Spire api response",
     * operationId="",
     * tags={""},
     * security={ {"authorization": {} }},
     * @OA\RequestBody(
     *    required=false,
     *    description="Pass spire api response",
     *    @OA\JsonContent(
     *       
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong auth token. Please try again")
     *        )
     *     )
     * )
     */
    public function showAllProduct($start)
    {
        /**
         * Returns a product list form Spire
         *
         *
         * @return array of product list  in human readable format
         *
         * */

        $url = config('config.Spire_Api_Url'); //get spire api url
        $limit = config('config.constants.limit'); //get spire api limit
        $limit_offset_url = $url . "items/?start=$start&limit=$limit";
        //$limit_offset_url = $url."items/?q=FSPINFO";
        $authorization = config('config.Spire_Api_Auth'); //get spire api authorization key
        /**
         * Returns a human readable array data
         *
         * @param string $url
         * url contains the spire api url
         *
         * @param string $method
         * curl request method
         *
         * @param array $payload
         * Request post data.
         *
         * @param string $authorization
         * curl header authorozation 
         *
         * @return a array data in human readable format
         *
         * */
        $getProductList = call_curl($limit_offset_url, $method = "GET",  $payload = '', $authorization);
        return $getProductList;
    }
    public function createProduct($start = 2164)
    {
        //set_time_limit(0);

        /**
         * Create to send product in bigcommerce
         *
         *
         * @showAllProduct method used to get data from spire 
         *
         * @makeAndmappshcema method used to mapped big commerce api response with spire api response and return new mapped array
         *
         * @param array $getproductfromSpire
         * contains the spire api array data
         *
         * Returns a human readable array data
         *
         * @param string $url
         * url contains the bigcommerce api url
         *
         * @param string $method
         * curl request method
         *
         * @param array $payload
         * Request post data.
         *
         * @param string $authorization
         * curl header authorozation 
         *
         * @return a array data in human readable format
         *
         * */
        $getproductfromSpire = $this->showAllProduct($start); //get product list from spire using this function
        $getproductfromSpireserver = json_decode($getproductfromSpire, true);
        $count = config('config.constants.count'); //2466;//$getproductfromSpireserver['count'];
        $limit = $getproductfromSpireserver['limit'];
        $start = $getproductfromSpireserver['start'];
        $getStart = $limit + $start;
        // echo "<pre>";print_r($getStart);die;
        if ($count > $getStart) {
            // $getproductfromSpire = $this->showAllProduct($getStart);//get product list from spire using this function
            //$getproductfromSpireserver = json_decode($getproductfromSpire,true);
            //echo "<pre>";print_r($getproductfromSpireserver);die;
            $authorizationSpire = config('config.Spire_Api_Auth');
            $getProductDetailsFromSpire = array();
            if ($getproductfromSpireserver) {
                foreach ($getproductfromSpireserver['records'] as $key => $newSchemaData) {
                    $link_self = $newSchemaData['links']['self'];
                    $getProductDetails = call_curl($link_self, $method = "GET",  $payload = '', $authorizationSpire);
                    $getProductDetailsFromSpire[$key] = json_decode($getProductDetails, true);
                    //$getProductDetailsFromSpire[$key] = $getProductDetailsFromSpire;
                    //echo "<pre>";print_r($getProductDetailsFromSpire[$key]);
                }
                //echo "<pre>";
                //print_r($getProductDetailsFromSpire);
                //die;
                $primarySelectorVariantProducts = $this->CreatePrimarySelectorProduct($getProductDetailsFromSpire); //call method to get primary selector varinants products
            }
            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
            $bigcommerce_createProduct_url = $url . 'catalog/products';
            if ($primarySelectorVariantProducts) {
                $getBigcommerce_requestSchema = makeAndmappshcema($primarySelectorVariantProducts);
            } else {
                $getBigcommerce_requestSchema = array();
            }
            //echo "<pre>";
            //print_r($getBigcommerce_requestSchema);
            //die;
            $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
            //echo $authorization;die;

            if ($getBigcommerce_requestSchema) {
                foreach ($getBigcommerce_requestSchema as $key => $productDataList) {
                    //echo "<pre>";print_r(json_encode($productDataList));die;
                    $getProductList = call_curl($bigcommerce_createProduct_url, $method = "POST",  $productDataList, $authorization);
                }
                echo "<pre>";
                print_r(json_decode($getProductList, true));
            }
            $this->createProduct($getStart);
        } else {
            echo 'Break!';
        }
    }
    public function getProductPriceTable(Request $request)
    {

        /**
         * get the product price table from spire
         *
         * @param $partNo string
         * contains the partNumber data
         *
         * @param $customerNo string
         * contains the customer number data
         *
         * Returns a human readable json data
         *
         * */
        $partNo = $request->partNo;
        $customerNo = $request->customerNo;
        if (!empty($partNo) && isset($customerNo)) {
            $url =  config('config.Spire_Api_Url'); //get spire api url
            $get_Product_PriceTable_url = $url . 'price_matrix/?filter={"partNo":"' . $partNo . '"}';
            $authorization = config('config.Spire_Api_Auth'); //get spire api authorization key
            //echo $get_Product_PriceTable_url;die;
            $getProductPriceTable = call_curl($get_Product_PriceTable_url, $method = "GET",  $payload = '', $authorization); //call curl
            $getProductPriceData = json_decode($getProductPriceTable, true);
            //$getProductPriceData = array();
            if ($getProductPriceData) {
                foreach ($getProductPriceData['records'] as $priceTableKey => $getProductPriceTableData) {
                    if ($getProductPriceTableData['promoCode']) { // check promocode
                        if ($getProductPriceTableData['customerNo'] == $customerNo) { // condition for customer number for special prices
                            $getProductPriceDatas[$priceTableKey] = $getProductPriceTableData;
                        } else if ($getProductPriceTableData['customerNo'] == 'null') { // condition for guest customer prices
                            $getProductPriceDatas[$priceTableKey] = $getProductPriceTableData;
                        }
                    } else {
                        $getProductPriceDatas = array();
                    }
                }
                if ($getProductPriceDatas) {
                    $result['Status'] = 'True';
                    $result['Message'] = 'Quantity breakdown range!';
                    $result['Result'] = array_values($getProductPriceDatas);
                    return json_encode($result);
                    die;
                } else {
                    $result['Status'] = 'False';
                    $result['Message'] = 'Quantity breakdown range not found!';
                    $result['Result'] = $getProductPriceDatas;
                    return json_encode($result);
                    die;
                }
            } else {
                $result['Status'] = 'False';
                $result['Message'] = 'Quantity breakdown range not found!';
                $result['Result'] = array();
                return json_encode($result);
                die;
            }
        } else {
            $result['Status'] = 'False';
            $result['Message'] = 'Invalid Request!';
            return json_encode($result);
            die;
        }
    }
    public function CreatePrimarySelectorProduct($spireProductDataPayload)
    {

        /**
         * get the product primary selector refrence option
         *
         * @param $spireProductDataPayload array
         * contains the Spire product data payload
         *
         * Returns a human readable primary selector payload to parse it to big commerce schema
         *
         * */
        if ($spireProductDataPayload) {
            foreach ($spireProductDataPayload as $primaryKey => $spireProductListDataPayload) {
                //echo "<pre>";print_r($spireProductListDataPayload);die;
                if (isset($spireProductListDataPayload['udf']['primaryselector']) && !empty($spireProductListDataPayload['udf']['primaryselector'])) {
                    $primarySelectorOption = $spireProductListDataPayload['udf']['primaryselector'];
                } else {
                    $primarySelectorOption = '';
                }
                $partNo = $spireProductListDataPayload['partNo'];
                if (empty($primarySelectorOption) || $primarySelectorOption == $partNo) {
                    $getPrimarySelectorData[$primaryKey] = $spireProductListDataPayload;
                } else {
                    $getPrimarySelectorDataChild[$primaryKey] = $spireProductListDataPayload;
                }
            }
            // echo "<pre>";
            //print_r($getPrimarySelectorDataChild);
            //die;
            if ($getPrimarySelectorData) {
                $finalProductMappedData = array();
                foreach ($getPrimarySelectorData as $primarykey1 => $getPrimarySelectorDataList) {
                    $finalProductMappedData[$primarykey1] = $getPrimarySelectorDataList;
                    $finalProductMappedData[$primarykey1]['variants'] = array();
                    foreach ($getPrimarySelectorDataChild as $getPrimarySelectorDataChildList) {
                        //$finalProductMappedData = array();
                        if ($getPrimarySelectorDataList['partNo'] == $getPrimarySelectorDataChildList['udf']['primaryselector']) {
                            $finalProductMappedData[$primarykey1]['variants'][] = $getPrimarySelectorDataChildList;
                        }
                    }
                }
                //echo "<pre>";
                //print_r($finalProductMappedData);
                //die;
                return $finalProductMappedData;
            } else {
                return $finalProductMappedData = array();
            }
        }
    }
    public function SaveCustomer(Request $request) {
        $data = $request->data;
        return $data;
    }
}
