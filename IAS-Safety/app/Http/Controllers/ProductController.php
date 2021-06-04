<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use App\Product;
use App\ProductPrice;
use App\ProductAddOns;
use App\ProductOptions;
use App\ProductBulkPrice;
use App\ProductMetafields;
use Log;


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
    public function showAllProduct($queryString)
    {
        /**
         * Returns a product list form Spire
         *
         *
         * @return array of product list  in human readable format
         *
         * */

        $url = config('config.Spire_Api_Url'); //get spire api url
        $limit_offset_url = $url . 'inventory/' . $queryString;
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
    public function importProduct()
    {

        $productInfo = Product::get()->last();
        if ($productInfo) {
            $initialStart = $productInfo->spire_product_id;
        } else {
            $initialStart = 0;
        }
        $this->createProduct($initialStart = 6100);
    }
    public function createProduct($start = 160)
    {
        set_time_limit(0);
        /**
         * Create to send product in bigcommerce
         *
         *
         * @showAllProduct method used to get data from spire 
         *
         * @makeAndMapSchema method used to mapped big commerce api response with spire api response and return new mapped array
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
        $limit = config('config.constants.limit');
        $queryString = "items/?start=$start&limit=$limit";
        $getproductfromSpire = $this->showAllProduct($queryString); //get product list from spire using this function
        $getproductfromSpireserver = json_decode($getproductfromSpire['response'], true);
        $limit = $getproductfromSpireserver['limit'];
        $start = $getproductfromSpireserver['start'];
        $getStart = $limit + $start;
        if ($getproductfromSpireserver['records']) {
            $authorizationSpire = config('config.Spire_Api_Auth');
            $getProductDetailsFromSpire = array();
            if ($getproductfromSpireserver) {
                foreach ($getproductfromSpireserver['records'] as $key => $newSchemaData) {
                    $link_self = $newSchemaData['links']['self'];
                    $getProductDetails = call_curl($link_self, $method = "GET",  $payload = '', $authorizationSpire);
                    $getProductDetailsFromSpire[$key] = json_decode($getProductDetails['response'], true);
                }
                $primarySelectorVariantProducts = $this->CreatePrimarySelectorProduct($getProductDetailsFromSpire); //call method to get primary selector varinants products
                if ($getProductDetailsFromSpire) {
                    $getAddOns = $this->getAddOns($getProductDetailsFromSpire); //call method to get addons products
                }
            }
            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
            $bigcommerce_createProduct_url = $url . 'catalog/products';
            if ($primarySelectorVariantProducts) {
                $getBigcommerce_requestSchema = makeAndMapSchema($primarySelectorVariantProducts);
            } else {
                $getBigcommerce_requestSchema = array();
            }
            $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key

            if ($getBigcommerce_requestSchema) {
                foreach ($getBigcommerce_requestSchema as $key => $productDataList) {
                    $getProductList[$key] = call_curl($bigcommerce_createProduct_url, $method = "POST",  $productDataList, $authorization);
                    $insertindb = json_decode($getProductList[$key]['response'], true);

                    if (isset($insertindb['data'])) {
                        Product::insertOrIgnore([
                            'bigc_product_id' => $insertindb['data']['id'],
                            'spire_product_id' => $productDataList['images'][0]['product_id'],
                            'product_name' => $insertindb['data']['name'],
                            'sku' => $insertindb['data']['sku'],
                            'description' => $insertindb['data']['description'],
                            'price' => $insertindb['data']['price'],
                            'is_featured' => $insertindb['data']['is_featured'],
                            'availability' => $insertindb['data']['availability'],
                            'availability_description' => $insertindb['data']['availability_description'],
                            'images' => $insertindb['data']['images'][0]['url_standard'],
                            'custom_fields' => json_encode($insertindb['data']['custom_fields'])
                        ]);
                    }
                }
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
            $getProductPriceTable = ProductPrice::where('part_no', $partNo)
                ->orderByRaw('LENGTH(minimumQty)', 'ASC')
                ->get()->toArray();
            $getProductPriceData = json_decode(json_encode($getProductPriceTable), true);
            $getProductPriceDatas = array();
            $getProductPriceDataList = array();
            $getProductPriceForGuest = array();
            $getProductPriceForGuestList = array();
            if ($getProductPriceData) {
                $currentDate = date('Y-m-d');
                foreach ($getProductPriceData as $priceTableKey => $getProductPriceTableData) {
                    if ($getProductPriceTableData['promoCode']) { // check promocode
                        if ($getProductPriceTableData['customerNo'] == $customerNo) { // condition for customer number for special prices
                            if ($getProductPriceTableData['startDate'] <= $currentDate && $getProductPriceTableData['endDate'] >= $currentDate) {
                                $getProductPriceDatas[$priceTableKey] = $getProductPriceTableData;
                            }
                            if ($getProductPriceTableData['startDate'] == '') {
                                $getProductPriceDataList[$priceTableKey] = $getProductPriceTableData;
                            }
                        }
                        if ($getProductPriceTableData['customerNo'] == '') { // condition for guest customer prices
                            if ($getProductPriceTableData['startDate'] <= $currentDate && $getProductPriceTableData['endDate'] >= $currentDate) {
                                $getProductPriceForGuest[$priceTableKey] = $getProductPriceTableData;
                            }
                            if ($getProductPriceTableData['startDate'] == '') {
                                $getProductPriceForGuestList[$priceTableKey] = $getProductPriceTableData;
                            }
                        }
                    } else {
                        $getProductPriceDatas = array();
                    }
                }
                switch (true) {
                    case $getProductPriceDatas:
                        $getProductPriceDatas = $getProductPriceDatas;
                        break;
                    case $getProductPriceDataList:
                        $getProductPriceDatas = $getProductPriceDataList;
                        break;
                    case $getProductPriceForGuest:
                        $getProductPriceDatas = $getProductPriceForGuest;
                        break;
                    case $getProductPriceForGuestList:
                        $getProductPriceDatas = $getProductPriceForGuestList;
                        break;
                    default:
                        $getProductPriceDatas = $getProductPriceDatas;
                        break;
                }
                //if ($getProductPriceDatas) {
                //$getProductPriceDatas = $getProductPriceDatas;
                //} else if ($getProductPriceDataList) {
                //$getProductPriceDatas = $getProductPriceDataList;
                //} else if ($getProductPriceForGuest) {
                //$getProductPriceDatas = $getProductPriceForGuest;
                //} else if ($getProductPriceForGuestList) {
                //$getProductPriceDatas = $getProductPriceForGuestList;
                //} else {
                //$getProductPriceDatas = $getProductPriceDatas;
                //}
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
            $getPrimarySelectorDataChild = array();
            foreach ($spireProductDataPayload as $primaryKey => $spireProductListDataPayload) {
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
            if ($getPrimarySelectorData) {
                $finalProductMappedData = array();
                foreach ($getPrimarySelectorData as $primarykey1 => $getPrimarySelectorDataList) {
                    $finalProductMappedData[$primarykey1] = $getPrimarySelectorDataList;
                    $finalProductMappedData[$primarykey1]['variants'] = array();
                    if ($getPrimarySelectorDataChild) {
                        foreach ($getPrimarySelectorDataChild as $getPrimarySelectorDataChildList) {
                            if ($getPrimarySelectorDataList['partNo'] == $getPrimarySelectorDataChildList['udf']['primaryselector']) {
                                $finalProductMappedData[$primarykey1]['variants'][] = $getPrimarySelectorDataChildList;
                            }
                        }
                    }
                }
                return $finalProductMappedData;
            } else {
                return $finalProductMappedData = array();
            }
        }
    }
    public function getAddOns($spireProductDataPayloadForAddon)
    {

        /**
         * get the product add ons
         *
         * @param $spireProductDataPayloadForAddon array
         * contains the Spire product data payload
         *
         * Returns true when add on is inserted
         *
         * */
        if ($spireProductDataPayloadForAddon) {
            $addonproducts = array();
            $authorizationSpire = config('config.Spire_Api_Auth');
            foreach ($spireProductDataPayloadForAddon as $addonkey => $spireProductDataPayloadForAddonList) {
                $components = $spireProductDataPayloadForAddonList['links']['components'];
                $getAddOnDetails = call_curl($components, $method = "GET",  $payload = '', $authorizationSpire);
                $addonproducts[$addonkey] = json_decode($getAddOnDetails['response'], true);
                if ($addonproducts[$addonkey]['records']) {
                    foreach ($addonproducts[$addonkey]['records'] as $addonkeyNext => $addonproductsList) {
                        if (isset($addonproductsList)) {
                            ProductAddOns::updateOrInsert(
                                ['add_on_id' => $addonproductsList['id']],
                                [
                                    'add_on_id' => $addonproductsList['id'],
                                    'part_num' => $addonproductsList['partNo'],
                                    'inventory' => json_encode($addonproductsList['inventory']),
                                    'productid_addon' => $spireProductDataPayloadForAddonList['id']
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
    public function importSpireProduct($start = 366)
    {
        set_time_limit(0);
        /**
         * Create to send product in bigcommerce
         *
         *
         * @showAllProduct method used to get data from spire 
         *
         * @makeAndMapSchema method used to mapped big commerce api response with spire api response and return new mapped array
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
        $limit = config('config.constants.limit');
        $queryString = "items/?start=$start&limit=$limit";
        $getproductfromSpire = $this->showAllProduct($queryString); //get product list from spire using this function
        $getproductfromSpireserver = json_decode($getproductfromSpire['response'], true);
        $limit = $getproductfromSpireserver['limit'];
        $start = $getproductfromSpireserver['start'];
        $getStart = $limit + $start;
        if ($getproductfromSpireserver['records']) {
            $this->updateOrInsertProduct($getproductfromSpireserver, $updateProduct = false);
            $this->importSpireProduct($getStart);
        } else {
            echo 'Break!';
        }
    }
    public function updateOrInsertProduct($getproductfromSpireserver, $updateProduct)
    {
        $authorizationSpire = config('config.Spire_Api_Auth');
        $getProductDetailsFromSpire = array();
        if ($getproductfromSpireserver) {
            foreach ($getproductfromSpireserver['records'] as $key => $newSchemaData) {
                $link_self = $newSchemaData['links']['self'];
                $getProductDetails = call_curl($link_self, $method = "GET",  $payload = '', $authorizationSpire);
                $getProductDetailsFromSpire[$key] = json_decode($getProductDetails['response'], true);
            }
            if ($getProductDetailsFromSpire) {
                $getAddOns = $this->getAddOns($getProductDetailsFromSpire); //call method to get addons products
                foreach ($getProductDetailsFromSpire as $pDetailkey => $getProductDetailsFromSpireList) {
                    if (!empty($getProductDetailsFromSpireList['udf']) && !empty($getProductDetailsFromSpireList['udf']['webcategory']) && $getProductDetailsFromSpireList['udf']['webcategory'] != '#N/A') {
                        if (isset($getProductDetailsFromSpireList['udf']['primaryselector']) && !empty($getProductDetailsFromSpireList['udf']['primaryselector'])) {
                            $primarySelectorOption = $getProductDetailsFromSpireList['udf']['primaryselector'];
                        } else {
                            $primarySelectorOption = '';
                        }
                        if ($updateProduct == true) {
                            $newcustumFieldsValues = $this->checkUpdatedCustomValue($getProductDetailsFromSpireList);
                        } else {
                            $newcustumFieldsValues = '';
                        }
                        $visibleNamespacesMetafields = array(
                            'screws' => isset($getProductDetailsFromSpireList['udf']['screws']) ? $getProductDetailsFromSpireList['udf']['screws'] : '',
                            'size' => isset($getProductDetailsFromSpireList['udf']['size']) ? $getProductDetailsFromSpireList['udf']['size'] : '',
                            'holes' => isset($getProductDetailsFromSpireList['udf']['holes']) ? $getProductDetailsFromSpireList['udf']['holes'] : '',
                            'flexible' => isset($getProductDetailsFromSpireList['udf']['flexible']) ? $getProductDetailsFromSpireList['udf']['flexible'] : '',
                            'material' => isset($getProductDetailsFromSpireList['udf']['material']) ? $getProductDetailsFromSpireList['udf']['material'] : '',
                            'slotholes' => isset($getProductDetailsFromSpireList['udf']['slotholes']) ? $getProductDetailsFromSpireList['udf']['slotholes'] : '',
                            '2sidedtape' => isset($getProductDetailsFromSpireList['udf']['2sidedtape']) ? $getProductDetailsFromSpireList['udf']['2sidedtape'] : '',
                            'selfadhesivesticker' => isset($getProductDetailsFromSpireList['udf']['selfadhesivesticker']) ? $getProductDetailsFromSpireList['udf']['selfadhesivesticker'] : '',
                            'srnote' => isset($getProductDetailsFromSpireList['udf']['srnote']) ? $getProductDetailsFromSpireList['udf']['srnote'] : '',
                            'brandname' => isset($getProductDetailsFromSpireList['udf']['brandname']) ? $getProductDetailsFromSpireList['udf']['brandname'] : '',
                        );
                        $getResult = Product::updateOrInsert(
                            ['spire_product_id'  => $getProductDetailsFromSpireList['id']],
                            [
                                'spire_product_id' => $getProductDetailsFromSpireList['id'],
                                'product_name' => isset($getProductDetailsFromSpireList['udf']['prodlongdescrip']) ? $getProductDetailsFromSpireList['udf']['prodlongdescrip'] : '',
                                'sku' => $getProductDetailsFromSpireList['partNo'],
                                'status' => 0,
                                'price' => isset($getProductDetailsFromSpireList['pricing'][$getProductDetailsFromSpireList['buyMeasureCode']]['sellPrices'][0]) ? (float) $getProductDetailsFromSpireList['pricing'][$getProductDetailsFromSpireList['buyMeasureCode']]['sellPrices'][0] : '',
                                'standard_cost' => isset($getProductDetailsFromSpireList['standardCost']) ? $getProductDetailsFromSpireList['standardCost'] : '',
                                'image_path' => isset($getProductDetailsFromSpireList['images']['path']) ? $getProductDetailsFromSpireList['images']['path'] : '',
                                //'image_url' => isset($getProductDetailsFromSpireList['links']['images']) ? $getProductDetailsFromSpireList['links']['images'] . $getProductDetailsFromSpireList['id'] . '/data' : '',
                                'image_url' => isset($getProductDetailsFromSpireList['links']['images']) ? $getProductDetailsFromSpireList['links']['images'] : '',
                                'weight' => isset($getProductDetailsFromSpireList['weight']) ? $getProductDetailsFromSpireList['weight'] : '',
                                'visible_udf_data' => isset($visibleNamespacesMetafields) ? json_encode($visibleNamespacesMetafields) : '',
                                'updated_custom_fields' => isset($newcustumFieldsValues) ? json_encode($newcustumFieldsValues) : '',
                                'custom_fields' => isset($getProductDetailsFromSpireList['udf']) ? json_encode($getProductDetailsFromSpireList['udf']) : '',
                                //'material_details' => isset($getProductDetailsFromSpireList['udf']['note']) ? $getProductDetailsFromSpireList['udf']['note'] : '',
                                'parent_product_code' => ((empty($primarySelectorOption) || $primarySelectorOption == $getProductDetailsFromSpireList['partNo'])) ? '' : $primarySelectorOption,
                            ]
                        );
                        if (isset($getProductDetailsFromSpireList['partNo'])) {
                            //$getSkuPriceList = $this->getSkuPriceList($getProductDetailsFromSpireList['partNo']); //call method to get price list of products
                        }
                    }
                }
            }
        } else {
            return false;
        }
    }
    public function createProductBigC()
    {
        set_time_limit(0);
        /**
         * Create to send product in bigcommerce
         *
         * @schemaCreateProduct method used to mapped big commerce api response with spire api response and return new mapped array
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
        $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
        $bigcommerce_createProduct_url = $url . 'catalog/products';
        $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
        $getdatafromdb = Product::where('parent_product_code', '')
            ->where('status', 5)
            ->where('product_name', '!=', '')
            ->get()->toArray();
        $getdatafromdb = json_decode(json_encode($getdatafromdb), true);
        //echo "<pre>";print_r($getdatafromdb);die;
        if ($getdatafromdb) {
            foreach ($getdatafromdb as $pKey => $getdatafromdbList) {
                if ($getdatafromdbList) {
                    $productUrlPrimary = '';
                    $productSku = $getdatafromdbList['sku'];
                    $variantIdChange = $getdatafromdbList['variant_id'];
                    if ($variantIdChange) {
                        $getDataPrimary = Product::where('bigc_product_id', $getdatafromdbList['bigc_variant_productid'])
                            ->where('status', 1)
                            ->first();
                        $getDataPrimary = json_decode(json_encode($getDataPrimary), true);
                        $customValueDecode = json_decode($getDataPrimary['custom_fields'], true);
                        $urlencodePrimary = cleanUrl($customValueDecode['seourl']);
                        $productUrlPrimary = '/' . $urlencodePrimary . '?sku=' . ltrim($getdatafromdbList['sku'], '+');
                        $variantChangeToProduct = $this->variantChangeToProduct($getdatafromdbList);
                    }
                    $partNumberUrl = false;
                    if ($productSku) {
                        $getdataSelector = Product::where('parent_product_code', $productSku)
                            //->where('status', 0)
                            ->get()->toArray();
                        $getdataSelector = json_decode(json_encode($getdataSelector), true);
                        if ($getdataSelector) {
                            $partNumberUrl = true;
                        }
                    }
                    //echo $partNumberUrl;die;
                    $getImageDataUrl = $getdatafromdbList['image_url'];
                    $productImageArray = array();
                    if ($getImageDataUrl) {
                        $authorizationSpire = config('config.Spire_Api_Auth');
                        $spireImageRecords = call_curl($getImageDataUrl, $method = "GET",  '', $authorizationSpire);
                        if ($spireImageRecords['status'] == 200) {
                            $spireImageRecords = json_decode($spireImageRecords['response'], true);
                            if ($spireImageRecords['records']) {
                                foreach ($spireImageRecords['records'] as $keyImage => $spireImageRecordsData) {
                                    $getImagePath = $this->getImagePath($spireImageRecordsData);
                                    if ($getImagePath['status'] == 200) {
                                        $productImageArray[]['image_url_eas'] = $getImagePath['response'];
                                    }
                                }
                                // echo "<pre>";
                                // print_r($productImageArray);
                                // die;
                            }
                        }
                    }
                    $getBigcommerce_requestSchema = schemaCreateProduct($getdatafromdbList, $partNumberUrl);
                } else {
                    $getBigcommerce_requestSchema = array();
                }
                // echo "<pre>";
                // print_r($getBigcommerce_requestSchema);
                // die;
                if ($getBigcommerce_requestSchema) {
                    if ($getdatafromdbList['bigc_product_id'] == '') {
                        $productResponse = callCurlAuth($bigcommerce_createProduct_url, $method = "POST",  $getBigcommerce_requestSchema, $authorization);
                        if ($productUrlPrimary) {
                            if ($productResponse['status'] == 200) {
                                $getNewUrl = $getBigcommerce_requestSchema['custom_url']['url'];
                                if ($getNewUrl != $productUrlPrimary) {
                                    $getRedirection = $this->urlRedirection($productUrlPrimary, $getNewUrl, $getdatafromdbList['id']);
                                }
                            }
                        }
                    } else {
                        if (!$productUrlPrimary) {
                            $getProductDataById = $this->getProductDataById($getdatafromdbList['bigc_product_id']);
                            $productOldUrl = '';
                            if ($getProductDataById) {
                                $productOldUrl = $getProductDataById['data']['custom_url']['url'];
                            }
                        } else {
                            $productOldUrl = $productUrlPrimary;
                        }
                        $bigcommerce_updateProduct_url = $url . 'catalog/products/' . $getdatafromdbList['bigc_product_id'];
                        $productResponse = callCurlAuth($bigcommerce_updateProduct_url, $method = "PUT",  $getBigcommerce_requestSchema, $authorization);
                        if ($productResponse['status'] == 200) {
                            $getNewUrl = $getBigcommerce_requestSchema['custom_url']['url'];
                            if ($getNewUrl != $productOldUrl) {
                                $getRedirection = $this->urlRedirection($productOldUrl, $getNewUrl, $getdatafromdbList['id']);
                            }
                        }
                    }
                    $getImportProductData = json_decode($productResponse['response'], true);
                    // echo "<pre>";
                    // print_r($getImportProductData);
                    // die;
                    if ($productResponse['token'] = 'Token matched') {
                        if ($productResponse['status'] == 200) {
                            if ($productImageArray) {
                                $imageApiResponse = $this->imageUploadBigc($productImageArray, $getImportProductData['data']['id'], '', $getdatafromdbList['image_id']);
                            }
                            if ($partNumberUrl == false) {
                                $bulkPrice = $this->updateBulkPricingRules($getImportProductData['data']['sku'], $start = 0, $getImportProductData['data']['id'], $price = '', $variantId = '');
                            }
                            if ($getdatafromdbList['bigc_product_id'] == '') {
                                if ($getImportProductData) {
                                    $metaFieldsData = $this->CreateMetafieldsData($getdatafromdbList, $getImportProductData['data']['id'], $variantId = NULL);
                                    $affectedData = Product::where('spire_product_id', $getdatafromdbList['spire_product_id'])
                                        ->update(['status' => 1, 'bigc_product_id' => $getImportProductData['data']['id'], 'updated_custom_fields' => json_encode($getImportProductData['data']['custom_fields'])]); //,'image_id' => $getImportProductData['data']['primary_image']['id']]);
                                    $this->optionVariant($getImportProductData, $getdatafromdbList);
                                }
                            } else {
                                $metaFieldsData = $this->CreateMetafieldsData($getdatafromdbList, $getImportProductData['data']['id'], $variantId = NULL);
                                $customFields = json_decode($getdatafromdbList['updated_custom_fields'], true);
                                if ($customFields) {
                                    foreach ($customFields as $cKey => $customFields) {
                                        if (!empty($customFields['name'])) {
                                            $customName[$cKey]['name'] = $customFields['name'];
                                            $customName[$cKey]['value'] = $customFields['value'];
                                            $bigcommerce_updateCustomFields_url = $url . 'catalog/products/' . $getdatafromdbList['spire_product_id'] . '/custom-fields/' . $customFields['id'];
                                            $productResponse[] = callCurlAuth($bigcommerce_updateCustomFields_url, $method = "PUT",  $customName[$cKey], $authorization);
                                        }
                                    }
                                }
                                if ($getImportProductData) {
                                    $affectedData = Product::where('spire_product_id', $getdatafromdbList['spire_product_id'])
                                        ->update(['status' => 1, 'bigc_product_id' => $getImportProductData['data']['id']]);
                                    $this->optionVariant($getImportProductData, $getdatafromdbList);
                                }
                            }
                            //     
                        } else {
                            continue;
                        }
                    } else {
                        echo "Not authorized request";
                    }
                }
            }
        } else {
            $getvariantfromdb = Product::where('parent_product_code', '!=', '')
                ->where('status', 0)
                ->get()->toArray();
            $getvariantfromdb = json_decode(json_encode($getvariantfromdb), true);
            //echo "<pre>";print_r($getvariantfromdb);die;
            if ($getvariantfromdb) {
                foreach ($getvariantfromdb as $variantKeys => $getVariantListfromdb) {
                    $parentProductSku = isset($getVariantListfromdb['parent_product_code']) ? $getVariantListfromdb['parent_product_code'] : '';
                    $udfDataVariantData = json_decode($getVariantListfromdb['custom_fields'], true);
                    $getparentfromdb = Product::where('sku', $parentProductSku)
                        ->where('status', 1)
                        ->first();
                    $getparentfromdb = json_decode(json_encode($getparentfromdb), true);
                    if ($getparentfromdb) {
                        $productIdBigc = isset($getparentfromdb['bigc_product_id']) ? $getparentfromdb['bigc_product_id'] : '';
                        $productSku = isset($getparentfromdb['sku']) ? $getparentfromdb['sku'] : '';
                        $getProductSelectorValue = $this->getProductSelectorValue($getparentfromdb);
                        $getVariantfromdbList = Product::where('parent_product_code', $productSku)
                            ->get()->toArray();
                        $getVariantfromdbList = json_decode(json_encode($getVariantfromdbList), true);
                        if (isset($udfDataVariantData['ref1']) || isset($udfDataVariantData['ref2']) || isset($udfDataVariantData['ref3'])) {
                            $variantoptionSchemaData = [];
                            $j = 1;
                            foreach ($getVariantfromdbList as $variantKey => $getVariantfromdbList) {
                                $bigCProductId = $getVariantfromdbList['bigc_product_id'];
                                if ($bigCProductId) {
                                    $getProductDataById = $this->getProductDataById($getVariantfromdbList['bigc_product_id']);
                                    $productOldUrl = '';
                                    if ($getProductDataById) {
                                        $productOldUrl = $getProductDataById['data']['custom_url']['url'];
                                    }
                                    $udfDataForUrl = json_decode($getparentfromdb['custom_fields'], true);
                                    $VarProUrl = cleanUrl($udfDataForUrl['seourl']);
                                    $getNewUrl = '/' . $VarProUrl . '?sku=' . ltrim($getVariantfromdbList['sku'], '+');
                                    $productChangeToVariant = $this->productChangedToVariant($getVariantfromdbList);
                                    if ($getNewUrl != $productOldUrl) {
                                        $getRedirection = $this->urlRedirection($productOldUrl, $getNewUrl, $getVariantfromdbList['id']);
                                    }
                                }
                                $udfDataVariant = json_decode($getVariantfromdbList['custom_fields'], true);
                                if (!empty($udfDataVariant['ref1']) || !empty($udfDataVariant['ref2']) || !empty($udfDataVariant['ref3'])) {

                                    $labelSchema = array();
                                    if ($getProductSelectorValue) {
                                        foreach ($getProductSelectorValue as $selectorLabelsData) {
                                            foreach ($selectorLabelsData as $keySelector => $selectorLabels) {
                                                $index = $keySelector + 1;
                                                $labelOptionValue = 'ref' . $index;
                                                $labelSchema[$keySelector] = [
                                                    "label" => $udfDataVariant[$labelOptionValue],
                                                    "sort_order" => $j + 1,
                                                    "is_default" => false,
                                                    "type" => $selectorLabels['type']
                                                ];
                                            }
                                        }
                                    }
                                    $variantoptionSchemaData[$variantKey] = [
                                        $labelSchema
                                    ];
                                }
                                $j++;
                            }
                            $variantoptionArray = array();
                            foreach ($variantoptionSchemaData as $variantoptionValues) {
                                foreach ($variantoptionValues as $variantoptionList) {
                                    $variantoptionArray[] = $variantoptionList;
                                }
                            }
                        }
                        $variantoptionSchemaDataMerge = array_merge($getProductSelectorValue, $variantoptionArray);
                        if ($variantoptionSchemaDataMerge) {
                            $newOptionSchema = array();
                            $newOptionSchemaoption = array();
                            if ($getProductSelectorValue) {
                                foreach ($getProductSelectorValue as $selectorRefData) {
                                    foreach ($selectorRefData as $selectorRefDataList) {
                                        $newOptionSchemaoption[$selectorRefDataList['type']] = array();
                                    }
                                }
                            }
                            foreach ($variantoptionSchemaDataMerge as $vKey => $variantoptionSchemaDataMerge) {
                                foreach ($variantoptionSchemaDataMerge as $cKey => $variantoptionType) {
                                    if ($getProductSelectorValue) {
                                        foreach ($getProductSelectorValue as $selectorRef) {
                                            foreach ($selectorRef as $selectorRefList) {
                                                if ($variantoptionType['type'] == $selectorRefList['type']) {
                                                    if (!in_array($variantoptionType['label'], $newOptionSchemaoption[$variantoptionType['type']])) {
                                                        $newOptionSchema[$variantoptionType['type']][$vKey]['label'] = $variantoptionType['label'];
                                                        $newOptionSchema[$variantoptionType['type']][$vKey]['sort_order'] = $variantoptionType['sort_order'];
                                                        $newOptionSchema[$variantoptionType['type']][$vKey]['is_default'] = $variantoptionType['is_default'];
                                                        $newOptionSchemaoption[$variantoptionType['type']][] = $variantoptionType['label'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        //if ($getVariantListfromdb['variant_id'] != '') {
                        $getOptionList = ProductOptions::where('product_id', $productIdBigc)
                            ->get()->toArray();
                        $getOptionList = json_decode(json_encode($getOptionList), true);
                        if ($getOptionList) {
                            foreach ($getOptionList as $getOptionList) {
                                $bigcommerce_deleteOption_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/options/' . $getOptionList['product_option_id'];
                                $getDeleteOptionResponse[] = callCurlAuth($bigcommerce_deleteOption_url, $method = "DELETE", '', $authorization);
                            }
                            $getOptionList = ProductOptions::where('product_id', $productIdBigc)
                                ->delete();
                        }
                        //}
                        foreach ($newOptionSchema as $OptionKeys => $childOptionValue) {
                            $optionSchema[$OptionKeys]['product_id'] = $productIdBigc;
                            $optionSchema[$OptionKeys]['name'] = trim($OptionKeys);
                            $optionSchema[$OptionKeys]['display_name'] = trim($OptionKeys);
                            $optionSchema[$OptionKeys]['type'] = 'dropdown';
                            $optionSchema[$OptionKeys]['sort_order'] = 1;
                            $optionSchema[$OptionKeys]['option_values'] = array_values($childOptionValue);
                            // if ($getVariantfromdb['variant_id'] != '') {
                            //     $optionSchema[$OptionKeys]['id'] = $getOptionList[$k]['product_option_id'];
                            // }

                            //if ($getVariantfromdb['variant_id'] == '') {
                            $bigcommerce_option_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/options';
                            $getOptionResponse[] = callCurlAuth($bigcommerce_option_url, $method = "POST",  $optionSchema[$OptionKeys], $authorization);
                            // } 
                            //else {
                            //     $bigcommerce_updateOption_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/options/'. $getOptionList[$k]['product_option_id'];
                            //     $getOptionResponse[] = call_curl($bigcommerce_updateOption_url, $method = "PUT",  $optionSchema[$OptionKeys], $authorization);
                            // }
                            //$k--;
                        }
                        if ($getOptionResponse) {
                            foreach ($getOptionResponse as $keyResponse => $getOptionResponseList) {
                                $getOptionResponseDataa = json_decode($getOptionResponseList['response'], true);
                                ProductOptions::updateOrInsert(
                                    ['product_option_id' => $getOptionResponseDataa['data']['id']],
                                    [
                                        'product_option_id' => $getOptionResponseDataa['data']['id'],
                                        'product_id' => $getOptionResponseDataa['data']['product_id'],
                                        'display_name' => $getOptionResponseDataa['data']['display_name'],
                                        'type' => $getOptionResponseDataa['data']['type'],
                                        'option_values' => json_encode($getOptionResponseDataa['data']['option_values'])
                                    ]
                                );
                            }
                        }
                        $getVariantfromdbs = Product::where('parent_product_code', $productSku)
                            //->where('status', 0)
                            ->get()->toArray();
                        $getVariantfromdbs = json_decode(json_encode($getVariantfromdbs), true);
                        if ($getVariantfromdbs) {
                            foreach ($getVariantfromdbs as $variantKeys => $getVariantfromdbs) {
                                $udfDataVariantData = json_decode($getVariantfromdbs['custom_fields'], true);
                                if (isset($udfDataVariantData['ref1']) || isset($udfDataVariantData['ref2']) || isset($udfDataVariantData['ref3'])) {
                                    $variantOptionSchema = array();
                                    if ($getOptionResponse) {
                                        if (!empty($getOptionResponse)) {
                                            foreach ($getOptionResponse as $optionKey => $getOptionResponseArray) {
                                                foreach ($getProductSelectorValue as $selectorRef) {
                                                    foreach ($selectorRef as $selectorkey => $selectorRefList) {
                                                        $index = $selectorkey + 1;
                                                        $refOrder = 'ref' . $index;
                                                        $getOptionResponseData = json_decode($getOptionResponseArray['response'], true);
                                                        if (trim($getOptionResponseData['data']['display_name']) == $selectorRefList['type']) {
                                                            foreach ($getOptionResponseData['data']['option_values'] as $optionValueKey => $optionValue) {
                                                                if ($udfDataVariantData[$refOrder] == $optionValue['label']) {
                                                                    $optionId = $optionValue['id'];
                                                                }
                                                            }
                                                            if (isset($udfDataVariantData[$refOrder])) {
                                                                $variantOptionSchema[$optionKey]['option_id'] = $getOptionResponseData['data']['id'];
                                                                $variantOptionSchema[$optionKey]['id'] = isset($optionId) ? $optionId : '';
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    // $getImageUrl = saveImage($getVariantfromdbs['image_url']);
                                    // $getImageUrlhtpp = 'https://safetymediainc.net/' . $getImageUrl;
                                    // $filedirectory = base_path('/');
                                    // $getImageUrls = $filedirectory . $getImageUrl;

                                    // $getFileSize = filesize($getImageUrls);
                                    // if ($getFileSize != 232) {
                                    //     $getImageUrlhtppImage = $getImageUrlhtpp;
                                    // } else {
                                    //     $getImageUrlhtppImage = config('config.Default_Image_Url');
                                    // }
                                    $variantSchema[$variantKeys]['cost_price'] = 0;
                                    $variantSchema[$variantKeys]['depth'] = isset($udfDataVariantData['Length']) ? $udfDataVariantData['Length'] : '';
                                    $variantSchema[$variantKeys]['height'] = isset($udfDataVariantData['Height']) ? $udfDataVariantData['Height'] : '';
                                    //$variantSchema[$variantKeys]['image_url'] = $getImageUrlhtppImage;
                                    $variantSchema[$variantKeys]['is_free_shipping'] = false;
                                    $variantSchema[$variantKeys]['option_values'] = $variantOptionSchema;

                                    $variantSchema[$variantKeys]['price'] = isset($getVariantfromdbs['price']) ? $getVariantfromdbs['price'] : '';
                                    $variantSchema[$variantKeys]['product_id'] = $productIdBigc;
                                    $variantSchema[$variantKeys]['purchasing_disabled'] = false;
                                    $variantSchema[$variantKeys]['purchasing_disabled_message'] = '';
                                    $variantSchema[$variantKeys]['retail_price'] = 0;
                                    $variantSchema[$variantKeys]['sku'] = isset($getVariantfromdbs['sku']) ? $getVariantfromdbs['sku'] : '';
                                    $variantSchema[$variantKeys]['weight'] = isset($getVariantfromdbs['weight']) ? $getVariantfromdbs['weight'] : '';
                                    if (isset($getVariantfromdb['Width'])) {
                                        $variantSchema[$variantKeys]['width'] = (float)$getVariantfromdbs['width'];
                                    }
                                    //if ($getVariantfromdbs['variant_id'] == '') {
                                    $bigcommerce_variant_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/variants';
                                    $getVariantResponse[$variantKeys] = callCurlAuth($bigcommerce_variant_url, $method = "POST",  $variantSchema[$variantKeys], $authorization);
                                    // } else {
                                    //     $bigcommerce_updatevariant_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/variants/' . $getVariantfromdbs['variant_id'];
                                    //     $getVariantResponse[$variantKeys] = call_curl($bigcommerce_updatevariant_url, $method = "PUT",  $variantSchema[$variantKeys], $authorization);
                                    // }
                                    $getImageDataUrl = $getVariantfromdbs['image_url'];
                                    $productImageArray = array();
                                    if ($getImageDataUrl) {
                                        $authorizationSpire = config('config.Spire_Api_Auth');
                                        $spireImageRecords = call_curl($getImageDataUrl, $method = "GET",  '', $authorizationSpire);
                                        if ($spireImageRecords['status'] == 200) {
                                            $spireImageRecords = json_decode($spireImageRecords['response'], true);
                                            if ($spireImageRecords['records']) {
                                                foreach ($spireImageRecords['records'] as $keyImage => $spireImageRecordsData) {
                                                    $getImagePath = $this->getImagePath($spireImageRecordsData);
                                                    if ($getImagePath['status'] == 200) {
                                                        $productImageArray[]['image_url_eas'] = $getImagePath['response'];
                                                    }
                                                }
                                                // echo "<pre>";
                                                // print_r($productImageArray);
                                                // die;
                                            }
                                        }
                                    }
                                    if ($getVariantResponse[$variantKeys]['token'] == 'Token matched') {
                                        if ($getVariantResponse[$variantKeys]['status'] == 200) {
                                            $getVariantResponse = json_decode($getVariantResponse[$variantKeys]['response'], true);
                                            if ($getVariantResponse) {
                                                if ($productImageArray) {
                                                    $imageApiResponse = $this->imageUploadBigc($productImageArray, $productIdBigc, $getVariantResponse['data']['id'], '');
                                                }
                                                $metaFieldsData = $this->CreateMetafieldsData($getVariantfromdbs, $productIdBigc, $getVariantResponse['data']['id']);
                                                $bulkPrice = $this->updateBulkPricingRules($getVariantResponse['data']['sku'], $start = 0, $productId = '', $price = $getVariantResponse['data']['price'], $variantId = $getVariantResponse['data']['id']);
                                                $affectedData = Product::where('spire_product_id', $getVariantfromdbs['spire_product_id'])
                                                    ->update(['status' => 1, 'variant_id' => $getVariantResponse['data']['id'], 'bigc_variant_productid' => $productIdBigc]);
                                            }
                                        } else {
                                            continue;
                                        }
                                    } else {
                                        echo "Not authorized request";
                                    }
                                }
                            }
                            // echo "<pre>";
                            // print_r($getVariantResponse);
                            // die;
                        }
                    }
                }
            }
        }
    }
    public function optionVariant($getImportProductData, $getdatafromdbList)
    {
        $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
        $bigcommerce_createProduct_url = $url . 'catalog/products';
        $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
        if ($getImportProductData) {
            $productIdBigc = isset($getImportProductData['data']['id']) ? $getImportProductData['data']['id'] : '';
            $productSku = isset($getImportProductData['data']['sku']) ? $getImportProductData['data']['sku'] : '';
            $getVariantfromdbList = Product::where('parent_product_code', $productSku)
                ->where('status', 0)
                ->get()->toArray();
            $getVariantfromdbList = json_decode(json_encode($getVariantfromdbList), true);
            //echo "<pre>";print_r($getVariantfromdbList);die;
            if ($getVariantfromdbList) {
                $getVariantfromdb = Product::where('parent_product_code', $productSku)
                    ->get()->toArray();

                $getVariantfromdb = json_decode(json_encode($getVariantfromdb), true);
                //echo "<pre>";print_r($getVariantfromdb);die;
                $getProductSelectorValue = $this->getProductSelectorValue($getdatafromdbList);
                $j = 1;
                foreach ($getVariantfromdb as $variantKey => $getVariantfromdb) {
                    $bigCProductId = $getVariantfromdb['bigc_product_id'];
                    if ($bigCProductId) {
                        $getProductDataById = $this->getProductDataById($getVariantfromdb['bigc_product_id']);
                        $productOldUrl = '';
                        if ($getProductDataById) {
                            $productOldUrl = $getProductDataById['data']['custom_url']['url'];
                        }
                        $udfDataForUrl = json_decode($getdatafromdbList['custom_fields'], true);
                        $VarProUrl = cleanUrl($udfDataForUrl['seourl']);
                        $getNewUrl = '/' . $VarProUrl . '?sku=' . ltrim($getVariantfromdb['sku'], '+');
                        if ($getNewUrl != $productOldUrl) {
                            $getRedirection = $this->urlRedirection($productOldUrl, $getNewUrl, $getdatafromdbList['id']);
                        }
                        $productChangeToVariant = $this->productChangedToVariant($getVariantfromdb);
                    }
                    $udfDataVariant = json_decode($getVariantfromdb['custom_fields'], true);
                    if (!empty($udfDataVariant['ref1']) || !empty($udfDataVariant['ref2']) || !empty($udfDataVariant['ref3'])) {
                        $udfSelecorRef = isset($udfDataVariant['selectorref']) ? $udfDataVariant['selectorref'] : '';
                        $selectorRefOrder = '';
                        if ($udfSelecorRef) {
                            $selectorRefOrder = explode(',', $udfDataVariant['selectorref']);
                        }
                        $labelSchema = array();
                        if ($getProductSelectorValue) {
                            foreach ($getProductSelectorValue as $selectorLabelsData) {
                                foreach ($selectorLabelsData as $keySelector => $selectorLabels) {
                                    $index = $keySelector + 1;
                                    $labelOptionValue = 'ref' . $index;
                                    $labelSchema[$keySelector] = [
                                        "label" => $udfDataVariant[$labelOptionValue],
                                        "sort_order" => $j + 1,
                                        "is_default" => false,
                                        "type" => $selectorLabels['type']
                                    ];
                                }
                            }
                        }
                        $variantoptionSchemaData[$variantKey] = [
                            $labelSchema
                        ];
                    }
                    $j++;
                }
                $variantoptionArray = array();
                foreach ($variantoptionSchemaData as $variantoptionValues) {
                    foreach ($variantoptionValues as $variantoptionList) {
                        $variantoptionArray[] = $variantoptionList;
                    }
                }
                $variantoptionSchemaDataMerge = array_merge($getProductSelectorValue, $variantoptionArray);
                if ($variantoptionSchemaDataMerge) {
                    $newOptionSchema = array();
                    $newOptionSchemaoption = array();
                    if ($getProductSelectorValue) {
                        foreach ($getProductSelectorValue as $selectorRefData) {
                            foreach ($selectorRefData as $selectorRefDataList) {
                                $newOptionSchemaoption[$selectorRefDataList['type']] = array();
                            }
                        }
                    }
                    foreach ($variantoptionSchemaDataMerge as $vKey => $variantoptionSchemaDataMerge) {
                        foreach ($variantoptionSchemaDataMerge as $cKey => $variantoptionType) {
                            if ($getProductSelectorValue) {
                                foreach ($getProductSelectorValue as $selectorRef) {
                                    foreach ($selectorRef as $selectorRefList) {
                                        if ($variantoptionType['type'] == $selectorRefList['type']) {
                                            if (!in_array($variantoptionType['label'], $newOptionSchemaoption[$variantoptionType['type']])) {
                                                $newOptionSchema[$variantoptionType['type']][$vKey]['label'] = $variantoptionType['label'];
                                                $newOptionSchema[$variantoptionType['type']][$vKey]['sort_order'] = $variantoptionType['sort_order'];
                                                $newOptionSchema[$variantoptionType['type']][$vKey]['is_default'] = $variantoptionType['is_default'];
                                                $newOptionSchemaoption[$variantoptionType['type']][] = $variantoptionType['label'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                //echo "<pre>";print_r($newOptionSchema);die;
                if ($newOptionSchema) {
                    //if ($getVariantfromdb['variant_id'] != '') {
                    $getOptionList = ProductOptions::where('product_id', $productIdBigc)
                        ->get()->toArray();
                    $getOptionList = json_decode(json_encode($getOptionList), true);
                    if ($getOptionList) {
                        foreach ($getOptionList as $getOptionList) {
                            $bigcommerce_deleteOption_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/options/' . $getOptionList['product_option_id'];
                            $getDeleteOptionResponse[] = callCurlAuth($bigcommerce_deleteOption_url, $method = "DELETE", '', $authorization);
                        }
                        $getOptionList = ProductOptions::where('product_id', $productIdBigc)
                            ->delete();
                    }
                    //}
                    foreach ($newOptionSchema as $OptionKeys => $childOptionValue) {
                        $optionSchema[$OptionKeys]['product_id'] = $productIdBigc;
                        $optionSchema[$OptionKeys]['name'] = trim($OptionKeys);
                        $optionSchema[$OptionKeys]['display_name'] = trim($OptionKeys);
                        $optionSchema[$OptionKeys]['type'] = 'dropdown';
                        $optionSchema[$OptionKeys]['sort_order'] = 1;
                        $optionSchema[$OptionKeys]['option_values'] = array_values($childOptionValue);
                        // if ($getVariantfromdb['variant_id'] != '') {
                        //     $optionSchema[$OptionKeys]['id'] = $getOptionList[$k]['product_option_id'];
                        // }

                        //if ($getVariantfromdb['variant_id'] == '') {
                        $bigcommerce_option_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/options';
                        $getOptionResponse[] = callCurlAuth($bigcommerce_option_url, $method = "POST",  $optionSchema[$OptionKeys], $authorization);
                        // } 
                        //else {
                        //     $bigcommerce_updateOption_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/options/'. $getOptionList[$k]['product_option_id'];
                        //     $getOptionResponse[] = call_curl($bigcommerce_updateOption_url, $method = "PUT",  $optionSchema[$OptionKeys], $authorization);
                        // }
                        //$k--;
                    }
                    //echo "<pre>";print_r($getOptionResponse);die;
                    if ($getOptionResponse) {
                        foreach ($getOptionResponse as $keyResponse => $getOptionResponseList) {
                            if ($getOptionResponseList['status'] == 200) {
                                $getOptionResponseDataa = json_decode($getOptionResponseList['response'], true);
                                ProductOptions::updateOrInsert(
                                    ['product_option_id' => $getOptionResponseDataa['data']['id']],
                                    [
                                        'product_option_id' => $getOptionResponseDataa['data']['id'],
                                        'product_id' => $getOptionResponseDataa['data']['product_id'],
                                        'display_name' => $getOptionResponseDataa['data']['display_name'],
                                        'type' => $getOptionResponseDataa['data']['type'],
                                        'option_values' => json_encode($getOptionResponseDataa['data']['option_values'])
                                    ]
                                );
                            }
                        }
                    }
                    $getVariantfromdbs = Product::where('parent_product_code', $productSku)
                        //->where('status', 0)
                        //->orWhere('sku', $productSku)
                        ->get()->toArray();
                    $getVariantfromdbs = json_decode(json_encode($getVariantfromdbs), true);
                    //echo "<pre>";print_r($getVariantfromdbs);die;
                    if ($getVariantfromdbs) {
                        foreach ($getVariantfromdbs as $variantKeys => $getVariantfromdbs) {
                            $udfDataVariantData = json_decode($getVariantfromdbs['custom_fields'], true);
                            if (isset($udfDataVariantData['ref1']) || isset($udfDataVariantData['ref2']) || isset($udfDataVariantData['ref3'])) {
                                $variantOptionSchema = array();
                                if ($getOptionResponse) {
                                    if (!empty($getOptionResponse)) {
                                        foreach ($getOptionResponse as $optionKey => $getOptionResponseArray) {
                                            foreach ($getProductSelectorValue as $selectorRef) {
                                                foreach ($selectorRef as $selectorkey => $selectorRefList) {
                                                    $index = $selectorkey + 1;
                                                    $refOrder = 'ref' . $index;
                                                    $getOptionResponseData = json_decode($getOptionResponseArray['response'], true);
                                                    if (trim($getOptionResponseData['data']['display_name']) == $selectorRefList['type']) {
                                                        foreach ($getOptionResponseData['data']['option_values'] as $optionValueKey => $optionValue) {
                                                            if ($udfDataVariantData[$refOrder] == $optionValue['label']) {
                                                                $optionId = $optionValue['id'];
                                                            }
                                                        }
                                                        if (isset($udfDataVariantData[$refOrder])) {
                                                            $variantOptionSchema[$optionKey]['option_id'] = $getOptionResponseData['data']['id'];
                                                            $variantOptionSchema[$optionKey]['id'] = isset($optionId) ? $optionId : '';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                // $getImageUrl = saveImage($getVariantfromdbs['image_url']);
                                // $getImageUrlhtpp = 'https://safetymediainc.net/' . $getImageUrl;
                                // $filedirectory = base_path('/');
                                // $getImageUrls = $filedirectory . $getImageUrl;

                                // $getFileSize = filesize($getImageUrls);
                                // if ($getFileSize != 232) {
                                //     $getImageUrlhtppImage = $getImageUrlhtpp;
                                // } else {
                                //     $getImageUrlhtppImage = config('config.Default_Image_Url');
                                // }
                                $variantSchema[$variantKeys]['cost_price'] = 0;
                                $variantSchema[$variantKeys]['depth'] = isset($udfDataVariantData['Length']) ? $udfDataVariantData['Length'] : '';
                                $variantSchema[$variantKeys]['height'] = isset($udfDataVariantData['Height']) ? $udfDataVariantData['Height'] : '';
                                //$variantSchema[$variantKeys]['image_url'] = $getImageUrlhtppImage;
                                $variantSchema[$variantKeys]['is_free_shipping'] = false;
                                $variantSchema[$variantKeys]['option_values'] = $variantOptionSchema;

                                $variantSchema[$variantKeys]['price'] = isset($getVariantfromdbs['price']) ? $getVariantfromdbs['price'] : '';
                                $variantSchema[$variantKeys]['product_id'] = $productIdBigc;
                                $variantSchema[$variantKeys]['purchasing_disabled'] = false;
                                $variantSchema[$variantKeys]['purchasing_disabled_message'] = '';
                                $variantSchema[$variantKeys]['retail_price'] = 0;
                                $variantSchema[$variantKeys]['sku'] = isset($getVariantfromdbs['sku']) ? $getVariantfromdbs['sku'] : '';
                                $variantSchema[$variantKeys]['weight'] = isset($getVariantfromdbs['weight']) ? $getVariantfromdbs['weight'] : '';
                                if (isset($getVariantfromdb['Width'])) {
                                    $variantSchema[$variantKeys]['width'] = (float)$getVariantfromdbs['width'];
                                }
                                //if ($getVariantfromdbs['variant_id'] == '') {
                                $bigcommerce_variant_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/variants';
                                $getVariantResponse[$variantKeys] = callCurlAuth($bigcommerce_variant_url, $method = "POST",  $variantSchema[$variantKeys], $authorization);
                                // } else {
                                //     $bigcommerce_updatevariant_url = $bigcommerce_createProduct_url . '/' . $productIdBigc . '/variants/' . $getVariantfromdbs['variant_id'];
                                //     $getVariantResponse[$variantKeys] = call_curl($bigcommerce_updatevariant_url, $method = "PUT",  $variantSchema[$variantKeys], $authorization);
                                // }
                                if ($getVariantResponse[$variantKeys]['token'] == 'Token matched') {
                                    if ($getVariantResponse[$variantKeys]['status'] == 200) {
                                        $getVariantResponse = json_decode($getVariantResponse[$variantKeys]['response'], true);
                                        if ($getVariantResponse) {
                                            $metaFieldsData = $this->CreateMetafieldsData($getVariantfromdbs, $productIdBigc, $getVariantResponse['data']['id']);
                                            $bulkPrice = $this->updateBulkPricingRules($getVariantResponse['data']['sku'], $start = 0, $productId = '', $price = $getVariantResponse['data']['price'], $variantId = $getVariantResponse['data']['id']);
                                            $affectedData = Product::where('spire_product_id', $getVariantfromdbs['spire_product_id'])
                                                ->update(['status' => 1, 'variant_id' => $getVariantResponse['data']['id'], 'bigc_variant_productid' => $productIdBigc]);
                                        }
                                    } else {
                                        continue;
                                    }
                                } else {
                                    echo "Not authorized request";
                                }
                            }
                        }
                        //echo "<pre>";
                        //print_r($getVariantResponse);
                        //die;
                    }
                }
            }
        }
    }
    public function getProductSelectorValue($getdatafromdbList)
    {
        $udfData = json_decode($getdatafromdbList['custom_fields'], true);
        $selectorRefOrder = isset($udfData['selectorref']) ? $udfData['selectorref'] : '';
        if ($selectorRefOrder) {
            if (isset($udfData['ref1']) || isset($udfData['ref2']) || isset($udfData['ref3'])) {
                $selectorRefOrder = explode(',', $selectorRefOrder);
                foreach ($selectorRefOrder as $keySelector => $selectorLabels) {
                    $index = $keySelector + 1;
                    $labelOptionValue = 'ref' . $index;
                    $labelSchema[$keySelector] = [
                        "label" => $udfData[$labelOptionValue],
                        "sort_order" => 1,
                        "is_default" => false,
                        "type" => trim($selectorLabels)
                    ];
                }
                $coptionSchema = [
                    $labelSchema
                ];
            }
            return $coptionSchema;
        } else {
            return $coptionSchema = array();
        }
    }
    public function getSkuPriceList($sku = NULL, $start = 0)
    {
        set_time_limit(0);
        /**
         * get the product price list using sku
         *
         * @param $sku string
         * contains the Spire product part number
         *
         * insert price list in database
         *
         * */
        $partNo = $sku;
        if (!empty($partNo)) {
            $url =  config('config.Spire_Api_Url'); //get spire api url
            $get_Product_PriceTable_url = $url . 'inventory/price_matrix/?start=' . $start . '&&limit=100&&filter={"partNo":"' . $partNo . '"}';
            $authorization = config('config.Spire_Api_Auth'); //get spire api authorization key
            $getProductPriceTable = call_curl($get_Product_PriceTable_url, $method = "GET",  $payload = '', $authorization); //call curl
            $getProductPriceData = json_decode($getProductPriceTable['response'], true);
            //echo "<pre>";print_r($getProductPriceData);die;
            $limit = $getProductPriceData['limit'];
            $start = $getProductPriceData['start'];
            $getStart = $limit + $start;
            if ($getProductPriceData['records']) {
                foreach ($getProductPriceData['records'] as $pricekey => $getProductPriceData) {
                    if (isset($getProductPriceData)) {
                        DB::enableQueryLog();
                        $getPData = ProductPrice::updateOrInsert(
                            ['price_matrix_id' => $getProductPriceData['id']],
                            [
                                'price_matrix_id' => $getProductPriceData['id'],
                                'part_no' => $getProductPriceData['partNo'],
                                'unitOfMeasure' => $getProductPriceData['unitOfMeasure'],
                                'promoCode' => $getProductPriceData['promoCode'],
                                'amount' => $getProductPriceData['amount'],
                                'amountType' => $getProductPriceData['amountType'],
                                'customerNo' => $getProductPriceData['customerNo'],
                                'margin' => $getProductPriceData['margin'],
                                'minimumQty' => $getProductPriceData['minimumQty'],
                                'inventory' => json_encode($getProductPriceData['inventory']),
                                'customer' => json_encode($getProductPriceData['customer']),
                                'startDate' => $getProductPriceData['startDate'],
                                'endDate' => $getProductPriceData['endDate'],
                            ]
                        );
                    }
                    // $query = DB::getQueryLog();
                    // Log::info('PriceList', array($query));
                    // dd($partNo);
                }
                $this->getSkuPriceList($sku = $partNo, $getStart);
            }
        }
    }
    public function getUpdatedProduct($start = 0)
    {
        set_time_limit(0);
        /**
         * Create to get update product from spire
         *
         *
         * @showAllProduct method used to get data from spire 
         *
         * @makeAndMapSchema method used to mapped big commerce api response with spire api response and return new mapped array
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
        //$limit = config('config.constants.limit');
        $todayDate = date("Y-m-d 00:00:00");
        $prev_date = date('Y-m-d%2000:00:00', strtotime($todayDate . ' -1 day'));
        //$queryString = 'items/?start=' . $start . '&&limit=100&&filter={"lastModified":{"$gte":"' . $prev_date . '"}}';
        $queryString = 'items/?filter={"partNo":"SI02"}';
        $getUpdatedproductfromSpire = $this->showAllProduct($queryString); //get product list from spire using this function
        $getproductfromSpireserver = json_decode($getUpdatedproductfromSpire['response'], true);
        $limit = $getproductfromSpireserver['limit'];
        $start = $getproductfromSpireserver['start'];
        $getStart = $limit + $start;
        Log::info('Cron2', $getproductfromSpireserver);
        // echo "<pre>";
        // print_r($getproductfromSpireserver);
        // die;
        if ($getproductfromSpireserver['records']) {
            $this->updateOrInsertProduct($getproductfromSpireserver, $updatedProduct = true);
            //$this->createProductBigC();
            $this->getUpdatedProduct($getStart);
        } else {
            echo "No record found!";
        }
    }
    public function checkUpdatedCustomValue($payLoadArray)
    {
        if ($payLoadArray) {
            $custumValues = $payLoadArray['udf'];
            $productId = $payLoadArray['id'];
            if ($custumValues) {
                $getdatafromdb = Product::where('spire_product_id', $productId)
                    ->get()->toArray();
                $getdatafromdb = json_decode(json_encode($getdatafromdb), true);
                if ($getdatafromdb) {
                    $oldCustomValues = json_decode($getdatafromdb[0]['custom_fields'], true);
                    if ($oldCustomValues) {
                        $newCustumValues = array_replace($oldCustomValues, $custumValues);
                        return $newCustumValues;
                    }
                }
            }
        } else {
            echo "No record found!";
        }
    }
    public function getProductAddons(Request $request)
    {

        /**
         * get the product custom data, material information and add ons from DB
         *
         * @param $partNo string
         * contains the partNumber data
         *
         * Returns a human readable json data
         *
         * */
        $partNo = $request->partNo;
        if (!empty($partNo)) {
            $getProductid = Product::where('sku', $partNo)
                ->get()->toArray();
            $getProductid = json_decode(json_encode($getProductid), true);
            $getProductAddonsData = array();
            if ($getProductid) {
                $getProductAddons = ProductAddOns::where('productid_addon', $getProductid[0]['spire_product_id'])
                    ->orderBy('add_on_id', 'DESC')->get()->toArray();
                $getProductAddons = json_decode(json_encode($getProductAddons), true);
                $getProductsAddonList = array();
                if ($getProductAddons) {
                    foreach ($getProductAddons as $addonKey => $getProductAddonsList) {
                        $inverntoryArray = json_decode($getProductAddonsList['inventory'], true);
                        $productAddonId = $inverntoryArray['id'];
                        $getProductAddonDetails = Product::select('bigc_product_id', 'product_name', 'price', 'sku', 'image_url', 'custom_fields')
                            ->where('spire_product_id', $productAddonId)
                            ->first();
                        if (!empty($getProductAddonDetails['custom_fields'])) {
                            $accquant = json_decode($getProductAddonDetails['custom_fields'], true);
                            $getProductsAddonList[$addonKey] = $getProductAddonDetails;
                            $getProductsAddonList[$addonKey]['accquant'] = $accquant['accquant'];
                            $getImageUrl = saveImage($getProductAddonDetails['image_url'], $getProductAddonDetails['image_url']);
                            $getImageUrlhtpp = config('config.Base_Url') . $getImageUrl;
                            $filedirectory = base_path('/');
                            $getImageUrls = $filedirectory . $getImageUrl;
                            $getFileSize = filesize($getImageUrls);
                            if ($getFileSize != 232) {
                                $getProductsAddonList[$addonKey]['image_path'] = $getImageUrlhtpp;
                            } else {
                                $getProductsAddonList[$addonKey]['image_path'] = config('config.Default_Image_Url');
                            }
                        }
                    }
                }
                $getProductAddonsData = [
                    'AddOns' => array_filter($getProductsAddonList),
                    'CustomData' => $getProductid
                ];
                if ($getProductAddonsData) {
                    $result['Status'] = 'True';
                    $result['Message'] = 'Product add-ons data!';
                    $result['Result'] = $getProductAddonsData;
                    return json_encode($result);
                    die;
                } else {
                    $result['Status'] = 'False';
                    $result['Message'] = 'Product add-ons data not found!';
                    $result['Result'] = array();
                    return json_encode($result);
                    die;
                }
            } else {
                $result['Status'] = 'False';
                $result['Message'] = 'Product add-ons data not found!';
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
    public function updateBulkPricingRules($partNo = NULL, $start = 0, $productId = NULL, $price = NULL, $variantId = NULL)
    {

        /**
         * get the product price table from spire
         *
         * @param $partNo string
         * contains the partNumber data
         *
         * update bulk princing in big commerce API
         * 
         * Returns a human readable json data
         *
         * */

        $partNo = $partNo;
        $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
        $bigcommerce_createProduct_url = $url . 'catalog/products';
        $authorizationBigc = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
        // echo $authorizationBigc;echo "<br>";
        // echo $variantId;die;
        if (!empty($partNo)) {
            $url =  config('config.Spire_Api_Url'); //get spire api url
            $get_Product_PriceTable_url = $url . 'inventory/price_matrix/?start=' . $start . '&&limit=250&&filter={"partNo":"' . $partNo . '"}';
            $authorization = config('config.Spire_Api_Auth'); //get spire api authorization key
            $getProductPriceTable = call_curl($get_Product_PriceTable_url, $method = "GET",  $payload = '', $authorization); //call curl
            $getProductPriceData = json_decode($getProductPriceTable['response'], true);
            $getProductPriceDatas = array();
            //$getProductPriceDataList = array();
            $getProductPriceForGuest = array();
            $getProductPriceForGuestList = array();
            if ($getProductPriceData['records']) {
                $currentDate = date('Y-m-d');
                foreach ($getProductPriceData['records'] as $priceTableKey => $getProductPriceTableData) {
                    if ($getProductPriceTableData['promoCode']) { // check promocode
                        if ($getProductPriceTableData['customerNo'] == '') { // condition for guest customer prices
                            if ($getProductPriceTableData['startDate'] <= $currentDate && $getProductPriceTableData['endDate'] >= $currentDate) {
                                $getProductPriceForGuest[$priceTableKey] = $getProductPriceTableData;
                            }
                            if ($getProductPriceTableData['startDate'] == '') {
                                $getProductPriceForGuestList[$priceTableKey] = $getProductPriceTableData;
                            }
                        }
                    } else {
                        $getProductPriceDatas = array();
                    }
                }
                switch (true) {
                    case $getProductPriceForGuest:
                        $getProductPriceDatas = $getProductPriceForGuest;
                        break;
                    case $getProductPriceForGuestList:
                        $getProductPriceDatas = $getProductPriceForGuestList;
                        break;
                    default:
                        $getProductPriceDatas = $getProductPriceDatas;
                        break;
                }
                if ($productId) {
                    if ($getProductPriceDatas) {
                        $getProductPriceDatas = array_values($getProductPriceDatas);
                        $bigcommerce_bulkprice_url = $bigcommerce_createProduct_url . '/' . $productId . '/bulk-pricing-rules';
                        //foreach (array_values($getProductPriceDatas) as $priceKey=> $getProductPriceLists) {
                        $getBulkPrice = ProductBulkPrice::where('product_id', $productId)
                            ->get()->toArray();
                        $getBulkPrice = json_decode(json_encode($getBulkPrice), true);
                        if ($getBulkPrice) {
                            foreach ($getBulkPrice as $getBulkPrice) {
                                $bigcommerce_deleteBulkPrice_url = $bigcommerce_createProduct_url . '/' . $productId . '/bulk-pricing-rules/' . $getBulkPrice['product_bulkprice_id'];
                                $getDeleteOptionResponse[] = callCurlAuth($bigcommerce_deleteBulkPrice_url, $method = "DELETE", '', $authorizationBigc);
                            }
                            $getBulkPriceList = ProductBulkPrice::where('product_id', $productId)
                                ->delete();
                        }
                        for ($i = 0; $i < count($getProductPriceDatas); $i++) {
                            if ($i < count($getProductPriceDatas) - 1) {
                                $maximumQty = ($getProductPriceDatas[$i + 1]['minimumQty']) - 1;
                            } else {
                                $maximumQty = 0;
                            }
                            // echo "<pre>";
                            // print_r($maximumQty);
                            // die;
                            $priceList[$i] = [
                                "amount" => $getProductPriceDatas[$i]['amount'],
                                "quantity_max" => $maximumQty,
                                "quantity_min" => $getProductPriceDatas[$i]['minimumQty'],
                                "type" => 'fixed'
                            ];
                            $getBulkPriceResponse[] = callCurlAuth($bigcommerce_bulkprice_url, $method = "POST",  $priceList[$i], $authorizationBigc);
                        }
                        if ($getBulkPriceResponse) {
                            foreach ($getBulkPriceResponse as $keyResponse => $getBulkPriceResponse) {
                                if ($getBulkPriceResponse['status'] == 200) {
                                    $getBulkPriceResponseDataa = json_decode($getBulkPriceResponse['response'], true);
                                    ProductBulkPrice::updateOrInsert(
                                        ['product_bulkprice_id' => $getBulkPriceResponseDataa['data']['id']],
                                        [
                                            'product_bulkprice_id' => $getBulkPriceResponseDataa['data']['id'],
                                            'amount' => $getBulkPriceResponseDataa['data']['amount'],
                                            'product_id' => $productId,
                                            'quantity_max' => $getBulkPriceResponseDataa['data']['quantity_max'],
                                            'quantity_min' => $getBulkPriceResponseDataa['data']['quantity_min'],
                                            'type' => $getBulkPriceResponseDataa['data']['type']
                                        ]
                                    );
                                }
                            }
                        }
                        //echo "<pre>";print_r($getBulkPriceResponse);die;
                    }
                } else {
                    $fixedRate = $this->getCurrencyRate();
                    $getProductPriceDatas = array_values($getProductPriceDatas);
                    $priceResponse = $this->bulkPriceVariant($getProductPriceDatas, $variantId, $price, $fixedRate);
                }
            }
        }
    }
    public function bulkPriceVariant($getProductPriceDatas = NULL, $variantId = NULL, $price = NULL, $fixedRate = NULL)
    {
        if ($getProductPriceDatas) {
            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
            $authorizationBigc = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
            $currencyArray = array(
                array(
                    "code" =>  'usd',
                ),
                array('code' => 'cad')

            );
            foreach ($currencyArray as $currencyCode) {
                for ($j = 0; $j < count($getProductPriceDatas); $j++) {
                    if ($j < count($getProductPriceDatas) - 1) {
                        $maximumQty = ($getProductPriceDatas[$j + 1]['minimumQty']) - 1;
                    } else {
                        $maximumQty = 0;
                    }
                    $currencyamount = $getProductPriceDatas[$j]['amount'];
                    if ($currencyCode['code'] == 'usd') {
                        $currencyamount = round($getProductPriceDatas[$j]['amount'] / $fixedRate, 2);
                    }
                    // echo "<pre>";
                    // print_r($getBulkPriceVariantResponse);
                    // die;
                    //$currencyamount = $this->currency('cad','usd',$getProductPriceDatas[$j]['amount']);
                    $priceList[$j] = [
                        "quantity_min" => (int) $getProductPriceDatas[$j]['minimumQty'],
                        "quantity_max" => (int) $maximumQty,
                        "type" => 'fixed',
                        "amount" => $currencyamount
                    ];
                    //$getBulkPriceResponse[] = callCurlAuth($bigcommerce_bulkprice_url, $method = "POST",  $priceList[$j], $authorizationBigc);
                }
                $bulkPrice = array(
                    //"variant_id" => $variantId,
                    //"currency" => $currencyCode['code'],
                    "price" => (float) $price,
                    "bulk_pricing_tiers" =>  $priceList
                );
                // echo "<pre>";
                // print_r(json_encode($bulkPrice));
                // die;
                $bigcommerce_bulkpriceVariant_url = $url . 'pricelists/2/records/' . $variantId . '/' . $currencyCode['code'];
                //echo $bigcommerce_bulkpriceVariant_url;die;
                //$bigcommerce_bulkpriceVariant_url = 'https://api.bigcommerce.com/stores/z84xkjcnbz/pricelists/2/records';
                $getBulkPriceVariantResponse = callCurlAuth($bigcommerce_bulkpriceVariant_url, $method = "PUT",  $bulkPrice, $authorizationBigc);
                // echo "<pre>";
                // print_r($getBulkPriceVariantResponse);
                // die;
            }
        }
    }
    public function getCurrencyRate()
    {
        $url =  config('config.Spire_Api_Url'); //get spire api url
        $authorization = config('config.Spire_Api_Auth'); //get spire api authorization key
        $spire_currency_url = $url . 'currencies/';
        $currencyData = call_curl($spire_currency_url, $method = 'GET', $payload = '', $authorization);
        $currencyDataList = json_decode($currencyData['response'], true);
        //echo "<pre>";print_r($currencyDataList);die;
        $fixedRate = '';
        if ($currencyDataList['records']) {
            foreach ($currencyDataList['records'] as $currencyList) {
                if ($currencyList['code'] == 'USD') {
                    $fixedRate = $currencyList['fixedRate'];
                }
            }
        }
        return $fixedRate;
    }
    public function productChangedToVariant($getProductData = NULL)
    {
        if ($getProductData) {
            //echo "<pre>";print_r($getProductData);die;
            $productId = $getProductData['bigc_product_id'];
            $primarySelector = $getProductData['parent_product_code'];
            if (!empty($productId) && !empty($primarySelector)) {
                $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
                $productDeleteUrl = $url . 'catalog/products/' . $productId;
                $authorizationBigc = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
                $deleteProductfromBigc = callCurlAuth($productDeleteUrl, $method = 'DELETE', $payload = '', $authorizationBigc);
                if ($deleteProductfromBigc['status'] == '204') {
                    $affectedData = Product::where('spire_product_id', $getProductData['spire_product_id'])
                        ->update(['bigc_product_id' => NULL]);
                }
            }
        }
    }
    public function variantChangeToProduct($getProductData = NULL)
    {
        if ($getProductData) {
            //echo "<pre>";print_r($getProductData);die;
            $variantId = $getProductData['variant_id'];
            $bigcProductId = $getProductData['bigc_variant_productid'];
            if (!empty($bigcProductId) && !empty($variantId)) {
                $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
                $variantDeleteUrl = $url . 'catalog/products/' . $bigcProductId . '/variants/' . $variantId;
                $authorizationBigc = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
                $deletVariantfromBigc = callCurlAuth($variantDeleteUrl, $method = 'DELETE', $payload = '', $authorizationBigc);
                if ($deletVariantfromBigc['status'] == '204') {
                    $affectedData = Product::where('spire_product_id', $getProductData['spire_product_id'])
                        ->update(['variant_id' => NULL, 'bigc_variant_productid' => NULL]);
                }
            }
        }
    }
    public function CreateMetafieldsData($productData = NULL, $productId = NULL, $variantId = NULL)
    {
        $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
        $bigcommerce_createProduct_url = $url . 'catalog/products';
        $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
        if ($productData) {
            if ($productId) {
                if (!$variantId) {
                    $getMetfieldsList = ProductMetafields::where('product_id', $productId)
                        ->get()->toArray();
                    $getMetfieldsList = json_decode(json_encode($getMetfieldsList), true);
                    if ($getMetfieldsList) {
                        foreach ($getMetfieldsList as $getMetfieldsList) {
                            $bigcommerce_deleteMetafield_url = $bigcommerce_createProduct_url . '/' . $productId . '/metafields/' . $getMetfieldsList['product_metafield_id'];
                            $getDeleteOptionResponse[] = call_curl($bigcommerce_deleteMetafield_url, $method = "DELETE", '', $authorization);
                        }
                        $getOptionList = ProductMetafields::where('product_id', $productId)
                            ->delete();
                    }
                }
            }
            $customValues = json_decode($productData['custom_fields'], true);
            if ($variantId) {
                $nameSpace = 'Variant';
                $bigcommerce_metafields_url = $bigcommerce_createProduct_url . '/' . $productId . '/variants/' . $variantId . '/metafields';
            } else {
                $nameSpace = 'Product';
                $bigcommerce_metafields_url = $bigcommerce_createProduct_url . '/' . $productId . '/metafields';
            }
            if ($customValues) {
                foreach ($customValues as $keyCustom => $customValueList) {
                    if ($customValueList) {
                        $metaFiledsPayload[$keyCustom] = [
                            "description" => 'Product Custom values.',
                            "key" => $keyCustom,
                            "namespace" => $nameSpace,
                            "permission_set" => 'write_and_sf_access',
                            "value" => (string) $customValueList
                        ];

                        //echo "<pre>";print_r($metaFiledsPayload[$keyCustom]);die;
                        $getMetaFieldsResponse[] = callCurlAuth($bigcommerce_metafields_url, $method = "POST",  $metaFiledsPayload[$keyCustom], $authorization);
                    }
                }
                // echo "<pre>";
                // print_r($getMetaFieldsResponse);
                // die;
                if (!$variantId) {
                    if ($getMetaFieldsResponse) {
                        foreach ($getMetaFieldsResponse as $keyResponse => $getMetaFieldsResponse) {
                            $getMetaFieldsResponseDataa = json_decode($getMetaFieldsResponse['response'], true);
                            if ($getMetaFieldsResponse['status'] == 200) {
                                ProductMetafields::updateOrInsert(
                                    ['product_metafield_id' => $getMetaFieldsResponseDataa['data']['id']],
                                    [
                                        'product_metafield_id' => $getMetaFieldsResponseDataa['data']['id'],
                                        'product_id' => $getMetaFieldsResponseDataa['data']['resource_id'],
                                        'namespace' => $getMetaFieldsResponseDataa['data']['namespace'],
                                        'key' => $getMetaFieldsResponseDataa['data']['key'],
                                        'value' => $getMetaFieldsResponseDataa['data']['value']
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    public function getProductDataById($productId = NULL)
    {
        $productResponseData = array();
        if ($productId) {
            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
            $bigcommerce_createProduct_url = $url . 'catalog/products';
            $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
            $getProductUrl = $bigcommerce_createProduct_url . '/' . $productId;
            $productResponseDataArray = callCurlAuth($getProductUrl, $method = "GET",  '', $authorization);
            if ($productResponseDataArray['status'] == 200) {
                $productResponseData = json_decode($productResponseDataArray['response'], true);
            }
        }
        return $productResponseData;
    }
    public function urlRedirection($oldUrl = NULL, $newUrl = NULL, $productId = NULL)
    {
        if ($newUrl) {
            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
            $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
            $getRedirectbyFilterUrl = $url . 'storefront/redirects?keyword=' . $oldUrl;
            $getDataRedirect = callCurlAuth($getRedirectbyFilterUrl, $method = "GET",  '', $authorization);
            $getOneredirect = json_decode($getDataRedirect['response'], true);
            // $getNewRedirect = array();
            // if ($getOneredirect['data']) {
            //     foreach ($getOneredirect['data'] as $getOneredirect) {
            //         //if ($getOneredirect['to']['url'] == $oldUrl) {
            //             $getNewRedirect = $getOneredirect;
            //         //}
            //     }
            // }
            $getOldUrlId = '';
            $getOldUrlBack = false;
            $nRecord = count($getOneredirect['data']);
            if ($getOneredirect['data']) {
                foreach ($getOneredirect['data'] as $getNewRedirect) {
                    $getOldUrlId = $getNewRedirect['id'];
                    //echo $getOldUrlId;
                    $deleteRedirectUrl = $url . 'storefront/redirects?id:in=' . $getOldUrlId;
                    $deleteRidirect = callCurlAuth($deleteRedirectUrl, $method = "DELETE",  '', $authorization);
                }
                foreach ($getOneredirect['data'] as $keyNew => $getRedirectionArray) {
                    $newRedirectArray[$keyNew] = array(

                        'oldUrl' => $getRedirectionArray['from_path']
                    );
                    $newRedirectArray[$keyNew + 1] = array(
                        'oldUrl' => $getRedirectionArray['to']['url']
                        // array(
                        //     'oldUrl' => $getRedirectionArray['to']['url']
                        // )
                    );
                    if ($getRedirectionArray['from_path'] == $newUrl) {
                        $getOldUrlBack = true;
                    }
                }
            }
            if ($nRecord >= 1 && $getOldUrlBack == false) {
                // echo "<pre>";
                // print_r($newRedirectArray);
                // die;
                foreach ($newRedirectArray as $keyUrl => $newRedirectArray) {
                    $payload[$keyUrl] = array(
                        [
                            'from_path' => $newRedirectArray['oldUrl'],
                            'site_id' => 1000,
                            'to' => [
                                'type' => 'url',
                                'url' => $newUrl
                            ]

                        ]
                    );
                    //echo "<pre>";print_r($payload);die;
                    $createRedirectUrl = $url . 'storefront/redirects';
                    $getRidirectData[] = callCurlAuth($createRedirectUrl, $method = "PUT",  $payload[$keyUrl], $authorization);
                    // echo "<pre>";
                    // print_r($getRidirectData);
                }
            } else {
                $oldUrl = $oldUrl;
                if ($getOldUrlBack == true) {
                    $oldUrl = $getOneredirect['data'][$nRecord - 1]['to']['url'];
                }
                $payload = array(
                    [
                        'from_path' => $oldUrl,
                        'site_id' => 1000,
                        'to' => [
                            'type' => 'url',
                            'url' => $newUrl
                        ]

                    ]
                );
                //echo "<pre>";print_r($payload);die;
                $createRedirectUrl = $url . 'storefront/redirects';
                $getRidirectData = callCurlAuth($createRedirectUrl, $method = "PUT",  $payload, $authorization);
                // echo "<pre>";
                // print_r($getRidirectData);die;
            }
        }
    }
    public function getImagePath($imageRecords = NULL)
    {
        $getImagePath = array();
        //$imageRecords = "http://209.151.135.27:10880/api/v2/companies/smi/inventory/items/165/images/6028/data";
        if ($imageRecords) {
            $url = $imageRecords['links']['data'];
            $id = $imageRecords['id'];
            $sequence = $imageRecords['sequence'];
            //echo $url;die;
            $authorization = config('config.Spire_Api_Auth'); //get spire api authorization key
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "authorization: $authorization",
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            $typeImage = substr($content_type, strrpos($content_type, '/') + 1);
            if ($typeImage == 'octet-stream') {
                $typeImage = 'webp';
            }
            $base64 = 'data:image/' . $typeImage . ';base64,' . base64_encode($response);
            curl_close($curl);
            if ($err) {
                $getImagePath = array();
            } else {
                $getImagePath = saveImageRequest($base64, $typeImage, $sequence, $id);
                // echo "<pre>";
                // print_r($getImagePath);
                // die;
            }
        }
        return $getImagePath;
    }
    public function imageUploadBigc($imageArrayData = NULL, $productId = NULL, $variantId = NULL, $imageIds = NULL)
    {
        if ($imageArrayData) {
            $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
            $bigcommerce_createProduct_url = $url . 'catalog/products/';
            $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
            $createProductImageUrl = $bigcommerce_createProduct_url . $productId . '/images'; //Create product image url
            if (!$variantId) {
                if ($imageIds) {
                    $imageIdsList = json_decode($imageIds, true);
                    if ($imageIdsList) {
                        foreach ($imageIdsList as $imageIdKey => $imageIdsarray) {
                            $imageDeleteUrl = $createProductImageUrl . '/' . $imageIdsarray;
                            //echo $imageDeleteUrl;die;
                            $getImageResponseDel[] = callCurlAuth($imageDeleteUrl, $method = "DELETE",   '', $authorization);
                        }
                    }
                }
                foreach ($imageArrayData as $imageKey => $imageArrayData) {
                    $imageSchema[$imageKey] =
                        [
                            "description" => '',
                            "image_file" => '',
                            "image_url" => $imageArrayData['image_url_eas'],
                            "is_thumbnail" => true,
                            "url_standard" => $imageArrayData['image_url_eas'],
                            "url_thumbnail" => '',
                        ];
                    $getImageResponse[] = callCurlAuth($createProductImageUrl, $method = "POST",   $imageSchema[$imageKey], $authorization);
                }
                // echo "<pre>";
                // print_r($getImageResponse);
                // die;
                if ($getImageResponse) {
                }
                $saveImageids = array();
                foreach ($getImageResponse as $keyResp => $getImageResponseData) {
                    if ($getImageResponseData['status'] == 200) {
                        $imageData = json_decode($getImageResponseData['response'], true);
                        $saveImageids[$keyResp] = $imageData['data']['id'];
                    }
                }
                if ($saveImageids) {
                    $saveImageJson = json_encode($saveImageids);
                    //echo $saveImageJson;die;
                    $affectedData = Product::where('bigc_product_id', $productId)
                        ->update(['image_id' =>  $saveImageJson]);
                }
            } else {
                foreach ($imageArrayData as $imageKey => $imageArrayData) {
                    if ($imageArrayData) {
                        $imageSchema[$imageKey] =
                            [
                                "image_url" => $imageArrayData['image_url_eas'],
                            ];
                        $variantImageUrl = $bigcommerce_createProduct_url . $productId . '/variant/' . $variantId . '/image';
                        $getImageResponse[$imageKey] = callCurlAuth($variantImageUrl, $method = "POST",   $imageSchema[$imageKey], $authorization);
                        if (!$getImageResponse[$imageKey]['status'] == 200) {
                            continue;
                        }
                    }
                }
            }
        }
    }
}
