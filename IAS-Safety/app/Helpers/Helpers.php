<?php

use App\Product;
use App\User;
use Illuminate\Support\Facades\Auth;

if (!function_exists('call_curl')) {
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
	 * @return string a array data in human readable format
	 *
	 * */
	function call_curl($url = null, $method = null, $payload = NULL, $authorization = null)
	{
		$curl = curl_init();
		$postmanToken = config('config.Postman_Token');
		if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') {
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => FALSE,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 300,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_POSTFIELDS => json_encode($payload),
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json",
					"postman-token: $postmanToken",
					"x-auth-token: $authorization"
				),

			));
		} else {
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => FALSE,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 300,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"postman-token: $postmanToken",
					"authorization: $authorization",
				),
			));
		}
		$response = curl_exec($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$err = curl_error($curl);

		curl_close($curl);
		if ($err) {
			return array(
				"status" => $statusCode,
				"response" => $err
			);
		} else {
			return array(
				"status" => $statusCode,
				"response" => $response
			);
		}
	}
}
if (!function_exists('callCurlAuth')) {
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
	 * @return string a array data in human readable format
	 *
	 * */
	function callCurlAuth($url = null, $method = null, $payload = NULL, $authorization = null)
	{
		$auth_user =  config('config.Auth_User'); // Authentication user name
		$auth_pass =  config('config.Auth_Pass'); // Authentication user passoword
		/**
		 * Auth::attempt function verify the user is valid or not and it takes two parameters email and password
		 *
		 * openssl_random_pseudo_bytes function Generates a string of pseudo-random bytes, with the number of bytes determined by the length parameter.
		 
		 */

		if (Auth::attempt(['email' => $auth_user, 'password' => $auth_pass])) {
			$user = Auth::user();
			$token = openssl_random_pseudo_bytes(16);

			//Convert the binary data into hexadecimal representation.
			$token = bin2hex($token);

			$update = User::where('id', $user['id']) // query to save the token into database table
				->update([
					'usertoken' => $token,
				]);
		} else {
			return array(
				"token" => 'Unauthorized call',
				'status' => false
			);
		}
		$urls = 'http://192.168.6.2:8000/api/callCurl'; // EAS function url 
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
				"x-auth-token: $authorization", // send big commerce auth token
				"url: $url", // send big commerce API url to EAS
				"method: $method", // send method to EAS eg. GET, POST and PUT
				"token: $token" // send IAS generated authenctication token to EAS
			),

		));
		$response = curl_exec($curl);
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
}
if (!function_exists('schemaCreateProduct')) {
	/**
	 * create big commerce create product api schema 
	 *
	 * @param array $payload
	 * Request post data.
	 *
	 * @return string a array data in human readable format
	 *
	 * */
	function schemaCreateProduct($getProductpayload = NULL, $partNumberUrl = false, $productImageArray = NULL)
	{
		$newProductSchema = array();
		if ($getProductpayload) {
			$udfData = json_decode($getProductpayload['custom_fields'], true);
			if (!empty($udfData) && !empty($udfData['webcategory']) && $udfData['webcategory'] != '#N/A') {
				if (!empty($udfData['bigcavailability'])) {
					if (!empty($udfData['ispricehidden'])) {
						if ($udfData['bigcavailability'] == '1' && $udfData['ispricehidden'] != '1') {
							$newProductSchema['availability'] = 'available';
						} else {
							$newProductSchema['availability'] = 'disabled';
						}
					} else {
						if ($udfData['bigcavailability'] == '1') {
							$newProductSchema['availability'] = 'available';
						} else {
							$newProductSchema['availability'] = 'disabled';
						}
					}
				} else {
					$newProductSchema['availability'] = 'disabled';
				}
				if (!empty($udfData['RegProdDel'])) {
					if ($udfData['RegProdDel'] >= 1 && isset($udfData['prodelrange'])) {
						$maxrange = $udfData['RegProdDel'] + max($udfData['prodelrange'], 1);
						$newProductSchema['availability_description'] = 'Ships within ' . $udfData['RegProdDel'] . '-' . $maxrange . ' Bussiness days';
					} else {
						$newProductSchema['availability_description'] = '';
					}
				}
				$newProductSchema['bin_picking_number'] = '';
				//$newProductSchema['brand_id'] = 0;
				if (!empty($udfData['brandname'])) {
					$newProductSchema['brand_name'] = $udfData['brandname'];
				}
				// if (!empty($udfData['othercategories'])) {
				// 	$otherCategories = explode(',', $udfData['othercategories']);
				// } else {
				// 	$otherCategories = '';
				// }
				$newProductSchema['bulk_pricing_rules'] = [];
				if (isset($udfData['webcategory'])) {
					if (!empty($udfData['othercategories'])) {
						$othercategory = explode(',', $udfData['othercategories']);
					} else {
						$othercategory = [];
					}
					$webcategory = explode('-', $udfData['webcategory']);
					$mainCategory = [rtrim($webcategory[0])];
					if (!empty($othercategory)) {
						$allCategories = array_merge($mainCategory, $othercategory);
					} else {
						$allCategories = $mainCategory;
					}
					$newProductSchema['categories'] = $allCategories;
				}
				$newProductSchema['condition'] = 'New';
				if (isset($udfData['standardCost'])) {
					$newProductSchema['cost_price'] = $udfData['standardCost'];
				}
				if ($getProductpayload['bigc_product_id'] == '') {
					if (!empty($udfData['note'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'note', 'value' => $udfData['note']);
					}
					if (!empty($udfData['screws'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'screws', 'value' => (string) $udfData['screws']);
					}
					if (!empty($udfData['srnote'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'srnote', 'value' => (string) substr($udfData['srnote'], 0, 249));
					}
					if (!empty($udfData['size'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'size', 'value' => $udfData['size']);
					}
					if (!empty($udfData['ref1'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'ref1', 'value' => $udfData['ref1']);
					}
					if (!empty($udfData['ref2'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'ref2', 'value' => $udfData['ref2']);
					}
					if (!empty($udfData['ref3'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'ref3', 'value' => $udfData['ref3']);
					}
					if (!empty($udfData['addons'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'addons', 'value' => $udfData['addons']);
					}
					if (!empty($udfData['holes'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'holes', 'value' => (string) $udfData['holes']);
					}
					if (!empty($udfData['SpecShip'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'SpecShip', 'value' => (string) $udfData['SpecShip']);
					}
					if (!empty($udfData['flexible'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'flexible', 'value' => (string) $udfData['flexible']);
					}
					if (!empty($udfData['includes'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'includes', 'value' => $udfData['includes']);
					}
					if (!empty($udfData['material'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'material', 'value' => $udfData['material']);
					}
					if (!empty($udfData['ShipReady'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'ShipReady', 'value' => (string) $udfData['ShipReady']);
					}
					if (!empty($udfData['brandname'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'Brand Name', 'value' => (string) $udfData['brandname']);
					}
					if (!empty($udfData['slotholes'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'slotholes', 'value' => (string) $udfData['slotholes']);
					}
					if (!empty($udfData['2sidedtape'])) {
						$newProductSchema['custom_fields'][] = array('name' => '2sidedtape', 'value' => (string) $udfData['2sidedtape']);
					}
					if (!empty($udfData['OfflinShip'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'OfflinShip', 'value' => (string)$udfData['OfflinShip']);
					}
					if (!empty($udfData['incremental'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'incremental', 'value' => (string) $udfData['incremental']);
					}
					if (isset($udfData['selectorref']) && !empty($udfData['selectorref'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'selectorref', 'value' => $udfData['selectorref']);
					}
					if (!empty($udfData['outsidedurable'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'outsidedurable', 'value' => (string) $udfData['outsidedurable']);
					}
					if (!empty($udfData['selfadhesivesticker'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'selfadhesivesticker', 'value' => (string) $udfData['selfadhesivesticker']);
					}
					if (!empty($udfData['primaryselector'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'primaryselector', 'value' => $udfData['primaryselector']);
					}
					if (!empty($udfData['assocaccessories'])) {
						$newProductSchema['custom_fields'][] = array('name' => 'assocaccessories', 'value' => $udfData['assocaccessories']);
					}
				}
				if (isset($udfData['seourl'])) {
					$seoUrl = cleanUrl($udfData['seourl']);
				} else {
					$seoUrl = '';
				}
				if ($seoUrl) {
					$urlSelector = '/' . $seoUrl;
					if (!$partNumberUrl) {
						$urlSelector = '/' . ltrim($getProductpayload['sku'], '+') . '/' . $seoUrl;
					}
					$newProductSchema['custom_url'] =
						[
							"is_customized" => true,
							"url" => $urlSelector,
						];
				}
				if (!empty($udfData['Length'])) {
					$newProductSchema['depth'] = $udfData['Length'];
				}
				if (isset($udfData['prodhtml'])) {
					$newProductSchema['description'] = str_replace("nbsp;", "", htmlspecialchars_decode($udfData['prodhtml']));
				}
				$newProductSchema['fixed_cost_shipping_price'] = 0;
				$newProductSchema['gift_wrapping_options_list'] = [];
				$newProductSchema['gift_wrapping_options_type'] = 'any';
				$newProductSchema['gtin'] = '';
				if (isset($udfData['Height'])) {
					$newProductSchema['height'] = $udfData['Height'];
				}
				// if ($productImageArray) {
				// 	foreach ($productImageArray as $imageKey => $productImageArray) {
				// 		$newProductSchema['images'][$imageKey] = 
				// 			[
				// 				"description" => '',
				// 				"image_file" => '',
				// 				"image_url" => $productImageArray['image_url_eas'],
				// 				"is_thumbnail" => true,
				// 				"sort_order" => '',
				// 				"date_modified" => '',
				// 				"id" => '',
				// 				"product_id" => $getProductpayload['id'],
				// 				"url_standard" => $productImageArray['image_url_eas'],
				// 				"url_thumbnail" => '',
				// 				"url_tiny" => '',
				// 				"url_zoom" => '',
				// 			];
				// 	}
				// }
				$newProductSchema['inventory_level'] = 0;
				$newProductSchema['inventory_tracking'] = 'none';
				$newProductSchema['inventory_warning_level'] = 0;
				$newProductSchema['is_condition_shown'] = false;
				if (isset($udfData['isfeatured'])) {
					$newProductSchema['is_featured'] = $udfData['isfeatured'];
				}
				$newProductSchema['is_free_shipping'] = false;
				$newProductSchema['is_preorder_only'] =  false;
				if (isset($udfData['ispricehidden'])) {
					$newProductSchema['is_price_hidden'] =  $udfData['ispricehidden'];
				}
				$newProductSchema['is_visible'] =  true;
				$newProductSchema['layout_file'] = '';
				if (isset($udfData['seodescription'])) {
					$newProductSchema['meta_description'] = $udfData['seodescription'];
				}
				if (isset($udfData['primarysearch'])) {
					$newProductSchema['meta_keywords'] = [$udfData['primarysearch']];
				}
				$newProductSchema['type'] = 'physical';
				if (isset($getProductpayload['weight'])) {
					$newProductSchema['weight'] = (float)$getProductpayload['weight'];
				}
				$newProductSchema['mpn'] = '';
				if (isset($udfData['prodlongdescrip'])) {
					$newProductSchema['name'] = $udfData['prodlongdescrip'];
				}
				$newProductSchema['open_graph_description'] = '';
				$newProductSchema['open_graph_title'] = '';
				$newProductSchema['open_graph_type'] = 'product';
				$newProductSchema['open_graph_use_image'] = true;
				$newProductSchema['open_graph_use_meta_description'] = true;
				$newProductSchema['open_graph_use_product_name'] = true;
				$newProductSchema['order_quantity_maximum'] = 0;
				$newProductSchema['order_quantity_minimum'] = 1;
				if (isset($udfData['seotitle'])) {
					$newProductSchema['page_title'] = $udfData['seotitle'];
				}
				$newProductSchema['preorder_message'] = '';
				$newProductSchema['price'] = (float)$getProductpayload['price'];
				if (isset($udfData['pricehiddenlabel'])) {
					$newProductSchema['price_hidden_label'] =  $udfData['pricehiddenlabel'];
				}
				if (isset($udfData['productCode'])) {
					$newProductSchema['product_tax_code'] = $udfData['productCode'];
				}
				if (isset($udfData['relatedprods'])) {
					$relatedProductsIds = [];
					if ($udfData['relatedprods']) {
						$relatedProducts = explode(',', $udfData['relatedprods']);
						foreach ($relatedProducts as $rPKey => $relatedProductsList) {
							$getdatafromdb = Product::where('sku', $relatedProductsList)
								->where('status', 1)
								->first();
							$getdatafromdb = json_decode(json_encode($getdatafromdb), true);
							if ($getdatafromdb) {
								$relatedProductsIds[$rPKey] = $getdatafromdb['bigc_product_id'];
							}
						}
					}
					$newProductSchema['related_products'] = $relatedProductsIds;
				}
				$newProductSchema['retail_price'] = 0;
				$newProductSchema['reviews_count'] = 0;
				$newProductSchema['reviews_rating_sum'] = 0;
				if (isset($udfData['primarysearch'])) {
					$newProductSchema['search_keywords'] = $udfData['primarysearch'];
				}
				$newProductSchema['sku'] = ltrim($getProductpayload['sku'], '+');
				if (isset($udfData['sortorder'])) {
					$newProductSchema['sort_order'] = $udfData['sortorder'];
				}
				$newProductSchema['tax_class_id'] = 0;
				$newProductSchema['total_sold'] = 0;
				$newProductSchema['upc'] = '';
				$newProductSchema['videos'] = [];
				$newProductSchema['view_count'] = 0;
				$newProductSchema['warranty'] = '';
				if (isset($udfData['Width'])) {
					$newProductSchema['width'] = $udfData['Width'];
				}
			}

			return $newProductSchema;
		} else {
			return $newProductSchema;
		}
	}
}
if (!function_exists('makeAndMapSchema')) {
	/**
	 * create big commerce create product api schema 
	 *
	 * @param array $payload
	 * Request post data.
	 *
	 * @return string a array data in human readable format
	 *
	 * */
	function makeAndMapSchema($getProductpayload = NULL)
	{
		$newProductSchema = array();
		if ($getProductpayload) {

			foreach ($getProductpayload as $key => $getproductkeydatafromSpire) {
				if (!empty($getproductkeydatafromSpire['udf']) && !empty($getproductkeydatafromSpire['udf']['webcategory']) && $getproductkeydatafromSpire['udf']['webcategory'] != '#N/A') {
					if (isset($getproductkeydatafromSpire['udf']['bigcavailability']) && !empty($getproductkeydatafromSpire['udf']['bigcavailability'])) {
						if ($getproductkeydatafromSpire['udf']['bigcavailability'] == '1') {
							$newProductSchema[$key]['availability'] = 'available';
						} else {
							$newProductSchema[$key]['availability'] = 'disabled';
						}
					} else {
						$newProductSchema[$key]['availability'] = 'disabled';
					}
					if (!empty($getproductkeydatafromSpire['udf']['RegProdDel'])) {
						if ($getproductkeydatafromSpire['udf']['RegProdDel'] >= 1 && isset($getproductkeydatafromSpire['udf']['prodelrange'])) {
							$maxrange = $getproductkeydatafromSpire['udf']['RegProdDel'] + max($getproductkeydatafromSpire['udf']['prodelrange'], 1);
							$newProductSchema[$key]['availability_description'] = 'Ships within ' . $getproductkeydatafromSpire['udf']['RegProdDel'] . '-' . $maxrange . ' Bussiness days';
						} else {
							$newProductSchema[$key]['availability_description'] = '';
						}
					}
					$newProductSchema[$key]['bin_picking_number'] = '';
					$newProductSchema[$key]['brand_id'] = 0;
					if (!empty($getproductkeydatafromSpire['udf']['brandname'])) {
						$newProductSchema[$key]['brand_name'] = $getproductkeydatafromSpire['udf']['brandname'];
					}
					$newProductSchema[$key]['bulk_pricing_rules'] = [];
					if (isset($getproductkeydatafromSpire['udf']['webcategory'])) {
						$webcategory = explode('-', $getproductkeydatafromSpire['udf']['webcategory']);
						$newProductSchema[$key]['categories'] = [rtrim($webcategory[0])];
					}
					$newProductSchema[$key]['condition'] = 'New';
					if (isset($getproductkeydatafromSpire['udf']['standardCost'])) {
						$newProductSchema[$key]['cost_price'] = $getproductkeydatafromSpire['udf']['standardCost'];
					}
					if (!empty($getproductkeydatafromSpire['udf']['note'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'note', 'value' => $getproductkeydatafromSpire['udf']['note']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['screws'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'screws', 'value' => (string) $getproductkeydatafromSpire['udf']['screws']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['srnote'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'srnote', 'value' => $getproductkeydatafromSpire['udf']['srnote']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['size'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'size', 'value' => $getproductkeydatafromSpire['udf']['size']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['ref1'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ref1', 'value' => $getproductkeydatafromSpire['udf']['ref1']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['ref2'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ref2', 'value' => $getproductkeydatafromSpire['udf']['ref2']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['ref3'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ref3', 'value' => $getproductkeydatafromSpire['udf']['ref3']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['addons'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'addons', 'value' => $getproductkeydatafromSpire['udf']['addons']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['holes'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'holes', 'value' => (string) $getproductkeydatafromSpire['udf']['holes']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['SpecShip'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'SpecShip', 'value' => (string) $getproductkeydatafromSpire['udf']['SpecShip']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['flexible'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'flexible', 'value' => (string) $getproductkeydatafromSpire['udf']['flexible']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['includes'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'includes', 'value' => $getproductkeydatafromSpire['udf']['includes']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['material'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'material', 'value' => $getproductkeydatafromSpire['udf']['material']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['ShipReady'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ShipReady', 'value' => (string) $getproductkeydatafromSpire['udf']['ShipReady']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['madebysmi'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'madebysmi', 'value' => (string) $getproductkeydatafromSpire['udf']['madebysmi']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['slotholes'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'slotholes', 'value' => (string) $getproductkeydatafromSpire['udf']['slotholes']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['2sidedtape'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => '2sidedtape', 'value' => (string) $getproductkeydatafromSpire['udf']['2sidedtape']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['OfflinShip'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'OfflinShip', 'value' => (string)$getproductkeydatafromSpire['udf']['OfflinShip']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['incremental'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'incremental', 'value' => (string) $getproductkeydatafromSpire['udf']['incremental']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['selectorref'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'selectorref', 'value' => $getproductkeydatafromSpire['udf']['selectorref']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['outsidedurable'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'outsidedurable', 'value' => (string) $getproductkeydatafromSpire['udf']['outsidedurable']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['selfadhesivesticker'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'selfadhesivesticker', 'value' => (string) $getproductkeydatafromSpire['udf']['selfadhesivesticker']);
					}
					if (!empty($getproductkeydatafromSpire['udf']['primaryselector'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'primaryselector', 'value' => $getproductkeydatafromSpire['udf']['primaryselector']);
					}
					if (isset($getproductkeydatafromSpire['udf']['seourl'])) {
						$seoUrl = cleanUrl($getproductkeydatafromSpire['udf']['seourl']);
					}
					if ($seoUrl) {
						$newProductSchema[$key]['custom_url'] =
							[
								"is_customized" => true,
								"url" => '/' . ltrim($getproductkeydatafromSpire['partNo'], '+') . '/' . $seoUrl,
							];
					}
					if (!empty($getproductkeydatafromSpire['udf']['Length'])) {
						$newProductSchema[$key]['depth'] = $getproductkeydatafromSpire['udf']['Length'];
					}
					if (isset($getproductkeydatafromSpire['udf']['prodhtml'])) {
						$newProductSchema[$key]['description'] = str_replace("nbsp;", "", htmlspecialchars_decode($getproductkeydatafromSpire['udf']['prodhtml']));
					}
					$newProductSchema[$key]['fixed_cost_shipping_price'] = 0;
					$newProductSchema[$key]['gift_wrapping_options_list'] = [];
					$newProductSchema[$key]['gift_wrapping_options_type'] = 'any';
					$newProductSchema[$key]['gtin'] = '';
					if (isset($getproductkeydatafromSpire['udf']['Height'])) {
						$newProductSchema[$key]['height'] = $getproductkeydatafromSpire['udf']['Height'];
					}
					if ($getproductkeydatafromSpire['links']['images'] . $getproductkeydatafromSpire['id'] . '/data' != '') {
						if (isset($getproductkeydatafromSpire['images']['path'])) {
							$imagepath = $getproductkeydatafromSpire['images']['path'];
						} else {
							$imagepath = '';
						}
						if (isset($getproductkeydatafromSpire['udf']['imagealttext'])) {
							$imagealttext = $getproductkeydatafromSpire['udf']['imagealttext'];
						} else {
							$imagealttext = '';
						}
						$newProductSchema[$key]['images'] = [
							[
								"description" => $imagealttext,
								"image_file" => $imagepath,
								"image_url" => 'https://agdhpmnben.cloudimg.io/fit/500x500/none/https://safetymedia.com/images/default/thumbnails/products/67cad30d666a8623a83516beda25ef36.jpg',
								"is_thumbnail" => true,
								"sort_order" => '',
								"date_modified" => '',
								"id" => '',
								"product_id" => $getproductkeydatafromSpire['id'],
								"url_standard" => '',
								"url_thumbnail" => 'https://agdhpmnben.cloudimg.io/fit/500x500/none/https://safetymedia.com/images/default/thumbnails/products/67cad30d666a8623a83516beda25ef36.jpg',
								"url_tiny" => '',
								"url_zoom" => '',
							],
						];
						//} 
					}
					$newProductSchema[$key]['inventory_level'] = 0;
					$newProductSchema[$key]['inventory_tracking'] = 'none';
					$newProductSchema[$key]['inventory_warning_level'] = 0;
					$newProductSchema[$key]['is_condition_shown'] = false;
					if (isset($getproductkeydatafromSpire['udf']['isfeatured'])) {
						$newProductSchema[$key]['is_featured'] = $getproductkeydatafromSpire['udf']['isfeatured'];
					}
					$newProductSchema[$key]['is_free_shipping'] = false;
					$newProductSchema[$key]['is_preorder_only'] =  false;
					if (isset($getproductkeydatafromSpire['udf']['ispricehidden'])) {
						$newProductSchema[$key]['is_price_hidden'] =  $getproductkeydatafromSpire['udf']['ispricehidden'];
					}
					$newProductSchema[$key]['is_visible'] =  true;
					$newProductSchema[$key]['layout_file'] = '';
					if (isset($getproductkeydatafromSpire['udf']['seodescription'])) {
						$newProductSchema[$key]['meta_description'] = $getproductkeydatafromSpire['udf']['seodescription'];
					}
					if (isset($getproductkeydatafromSpire['udf']['primarysearch'])) {
						$newProductSchema[$key]['meta_keywords'] = [$getproductkeydatafromSpire['udf']['primarysearch']];
					}
					$newProductSchema[$key]['type'] = 'physical';
					if (isset($getproductkeydatafromSpire['weight'])) {
						$newProductSchema[$key]['weight'] = (float)$getproductkeydatafromSpire['weight'];
					}
					$newProductSchema[$key]['mpn'] = '';
					if (isset($getproductkeydatafromSpire['udf']['prodlongdescrip'])) {
						$newProductSchema[$key]['name'] = $getproductkeydatafromSpire['udf']['prodlongdescrip'];
					}
					$newProductSchema[$key]['open_graph_description'] = '';
					$newProductSchema[$key]['open_graph_title'] = '';
					$newProductSchema[$key]['open_graph_type'] = 'product';
					$newProductSchema[$key]['open_graph_use_image'] = true;
					$newProductSchema[$key]['open_graph_use_meta_description'] = true;
					$newProductSchema[$key]['open_graph_use_product_name'] = true;
					$newProductSchema[$key]['order_quantity_maximum'] = 0;
					$newProductSchema[$key]['order_quantity_minimum'] = 1;
					if (isset($getproductkeydatafromSpire['udf']['seotitle'])) {
						$newProductSchema[$key]['page_title'] = $getproductkeydatafromSpire['udf']['seotitle'];
					}
					$newProductSchema[$key]['preorder_message'] = '';
					$newProductSchema[$key]['price'] = (float)$getproductkeydatafromSpire['pricing'][$getproductkeydatafromSpire['buyMeasureCode']]['sellPrices'][0];
					$newProductSchema[$key]['price_hidden_label'] = '';
					if (isset($getproductkeydatafromSpire['udf']['productCode'])) {
						$newProductSchema[$key]['product_tax_code'] = $getproductkeydatafromSpire['udf']['productCode'];
					}
					if (isset($getproductkeydatafromSpire['udf']['relatedproducts'])) {
						$newProductSchema[$key]['related_products'] = [$getproductkeydatafromSpire['udf']['relatedproducts']];
					}
					$newProductSchema[$key]['retail_price'] = 0;
					$newProductSchema[$key]['reviews_count'] = 0;
					$newProductSchema[$key]['reviews_rating_sum'] = 0;
					if (isset($getproductkeydatafromSpire['udf']['primarysearch'])) {
						$newProductSchema[$key]['search_keywords'] = $getproductkeydatafromSpire['udf']['primarysearch'];
					}
					$newProductSchema[$key]['sku'] = ltrim($getproductkeydatafromSpire['partNo'], '+');
					if (isset($getproductkeydatafromSpire['udf']['sortorder'])) {
						$newProductSchema[$key]['sort_order'] = $getproductkeydatafromSpire['udf']['sortorder'];
					}
					$newProductSchema[$key]['tax_class_id'] = 0;
					$newProductSchema[$key]['total_sold'] = 0;
					$newProductSchema[$key]['upc'] = '';
					$newProductSchema[$key]['videos'] = [];
					$newProductSchema[$key]['view_count'] = 0;
					$newProductSchema[$key]['warranty'] = '';
					if (isset($getproductkeydatafromSpire['udf']['Width'])) {
						$newProductSchema[$key]['width'] = $getproductkeydatafromSpire['udf']['Width'];
					}
					if ($getproductkeydatafromSpire['variants']) {
						foreach ($getproductkeydatafromSpire['variants'] as $variantKay => $variantProducts) {
							$newProductSchema[$key]['variants'][$variantKay] = [
								'bin_picking_number' => '',
								'cost_price' => 0,
								'depth' => isset($variantProducts['udf']['Length']) ? $variantProducts['udf']['Length'] : '',
								'fixed_cost_shipping_price' => 0,
								'height' => isset($variantProducts['udf']['Height']) ? $variantProducts['udf']['Height'] : '',
								'inventory_level' => '0',
								'inventory_warning_level' => '0',
								'is_free_shipping' => false,
								'price' => isset($variantProducts['pricing'][$variantProducts['buyMeasureCode']]['sellPrices'][0]) ? (float)$variantProducts['pricing'][$variantProducts['buyMeasureCode']]['sellPrices'][0] : '',
								'purchasing_disabled' => false,
								'retail_price' => 0,
								'upc' => '',
								'weight' => isset($variantProducts['weight']) ? (float)$variantProducts['weight'] : '',
								'width' => isset($variantProducts['udf']['Width']) ? $variantProducts['udf']['Width'] : '',
								'option_values' => [
									[
										'label' => isset($variantProducts['udf']['material']) ? $variantProducts['udf']['material'] : '',
										'option_display_name' => 'Material',
									],
								],
								'sku' => isset($variantProducts['partNo']) ? $variantProducts['partNo'] : '',
							];
						}
					}
				}
			}
			return $newProductSchema;
		} else {
			return $newProductSchema;
		}
	}
}
if (!function_exists('saveImage')) {
	/**
	 * Create a custom image with spire image url and returns a custom image url 
	 *
	 * @param string $url
	 * url contains the spire image  url
	 *
	 * @return string image url
	 *
	 * */
	function saveImage($url)
	{
		$Imageurl = $url;
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
				"postman-token: d34ee56d-cd10-85b8-1154-755973733b6a",
				"authorization: $authorization",
			),
		));
		$img = config('Base_Url') . 'images/' . '/product' . strtotime('now') . '.png';
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			if (file_exists($img)) {
				unlink($img);
			}
			$fp = fopen($img, 'x');
			$len = fwrite($fp, $response);
			return $img;
		}
	}
}
if (!function_exists('download_image1')) {
	/**
	 * create a function to download image and save in app directory
	 *
	 * @param string $url
	 * url contains the spire image url
	 *
	 * */
	function download_image1($image_url, $image_file)
	{
		$fp = fopen(public_path('images/') . $image_file, 'w+');              // open file handle
		$fp = fopen(config('APP_URL') . '/images/' . $image_file, 'w+');              // open file handle
		$authorization = config('config.Spire_Api_Auth'); //get spire api authorization key
		$ch = curl_init($image_url);
		$header = array(
			"cache-control: no-cache",
			"postman-token: d34ee56d-cd10-85b8-1154-755973733b6a",
			"x-auth-token: $authorization",
		);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // enable if you want
		curl_setopt($ch, CURLOPT_FILE, $fp);          // output to file
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, $header);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1000);      // some large value to allow curl to run for a long time
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_setopt($ch, CURLOPT_VERBOSE, true);   // Enable this line to see debug prints
		curl_exec($ch);

		curl_close($ch);                              // closing curl handle
		fclose($fp);                                  // closing file handle
	}
}
if (!function_exists('cleanUrl')) {
	/**
	 * Returns a clean seo url
	 *
	 * @param string $url
	 * url contains the spire seo url
	 *
	 * */
	function cleanUrl($string)
	{
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

		return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
	}
}
if (!function_exists('saveImageRequest')) {
	/**
	 * Returns a human readable array data
	 *
	 * @param string $url
	 * url contains the spire api url
	 *
	 * @param string $authorization
	 * curl header authorozation 
	 *
	 * @return string a array data in human readable format
	 *
	 * */
	function saveImageRequest($imageBinaryData = null,  $contentType = null, $sequence = null, $id = null)
	{

		$urls = 'http://192.168.6.2:8000/api/saveImage'; // EAS function url 
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
			//CURLOPT_POSTFIELDS => json_encode($payload), // send payload to EAS
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: application/json",
				"contentType: $contentType", // send big commerce auth token
				"imageBinaryData: $imageBinaryData", // send big commerce API url to EAS
				"sequence: $sequence",
				"id: $id"
			),

		));
		$response = curl_exec($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$err = curl_error($curl);
		//echo "<pre>";print_r($response);die;
		curl_close($curl);
		$mydata = json_decode($response, TRUE);
		if ($err) {
			return array( // return response
				"status" => 400,
				"response" => $err,
			);
		} else {
			return array( // return response
				"status" => $mydata['status'],
				"response" => $mydata['responseData'],
				"contentType" => $mydata['contentType']
			);
		}
	}
}
