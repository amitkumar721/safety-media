<?php

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
	function call_curl($url, $method, $payload = NULL, $authorization)
	{
		$curl = curl_init();

		if ($method == 'POST') {
			//echo json_encode($payload);die;
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
					"content-type: multipart/form-data",
					"postman-token: d34ee56d-cd10-85b8-1154-755973733b6a",
					"x-auth-token: $authorization",
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
					"postman-token: d34ee56d-cd10-85b8-1154-755973733b6a",
					"authorization: $authorization",
				),
			));
		}
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			return $response;
		}
	}
}
if (!function_exists('makeAndmappshcema')) {
	function makeAndmappshcema($getProductpayload = NULL)
	{
		$newProductSchema = array();
		if ($getProductpayload) {

			foreach ($getProductpayload as $key => $getproductkeydatafromSpire) {
				//echo "<pre>";print_r($getproductkeydatafromSpire);die;

				//$newProductSchema[$key]['id'] = $getproductkeydatafromSpire['id'];
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
						//echo $getproductkeydatafromSpire['udf']['prodelrange'];die;
						if ($getproductkeydatafromSpire['udf']['RegProdDel'] >= 1 && isset($getproductkeydatafromSpire['udf']['prodelrange'])) {
							$maxrange = $getproductkeydatafromSpire['udf']['RegProdDel'] + max($getproductkeydatafromSpire['udf']['prodelrange'], 1);
							//echo $maxrange;die;
							$newProductSchema[$key]['availability_description'] = 'Ships within ' . $getproductkeydatafromSpire['udf']['RegProdDel'] . '-' . $maxrange.' Bussiness days';
						} else {
							$newProductSchema[$key]['availability_description'] = '';
						}
					}
					$newProductSchema[$key]['bin_picking_number'] = '';
					$newProductSchema[$key]['brand_id'] = 0;
					if (isset($getproductkeydatafromSpire['udf']['brandname']) && !empty($getproductkeydatafromSpire['udf']['brandname'])) {
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
					if (isset($getproductkeydatafromSpire['udf']['note']) && !empty($getproductkeydatafromSpire['udf']['note'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'note', 'value' => $getproductkeydatafromSpire['udf']['note']);
					}
					if (isset($getproductkeydatafromSpire['udf']['screws']) && !empty($getproductkeydatafromSpire['udf']['screws'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'screws', 'value' => (string) $getproductkeydatafromSpire['udf']['screws']);
					}
					if (isset($getproductkeydatafromSpire['udf']['srnote']) && !empty($getproductkeydatafromSpire['udf']['srnote'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'srnote', 'value' => $getproductkeydatafromSpire['udf']['srnote']);
					}
					if (isset($getproductkeydatafromSpire['udf']['size']) && !empty($getproductkeydatafromSpire['udf']['size'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'size', 'value' => $getproductkeydatafromSpire['udf']['size']);
					}
					if (isset($getproductkeydatafromSpire['udf']['ref1']) && !empty($getproductkeydatafromSpire['udf']['ref1'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ref1', 'value' => $getproductkeydatafromSpire['udf']['ref1']);
					}
					if (isset($getproductkeydatafromSpire['udf']['ref2']) && !empty($getproductkeydatafromSpire['udf']['ref2'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ref2', 'value' => $getproductkeydatafromSpire['udf']['ref2']);
					}
					if (isset($getproductkeydatafromSpire['udf']['ref3']) && !empty($getproductkeydatafromSpire['udf']['ref3'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ref3', 'value' => $getproductkeydatafromSpire['udf']['ref3']);
					}
					if (isset($getproductkeydatafromSpire['udf']['addons']) && !empty($getproductkeydatafromSpire['udf']['addons'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'addons', 'value' => $getproductkeydatafromSpire['udf']['addons']);
					}
					if (isset($getproductkeydatafromSpire['udf']['holes']) && !empty($getproductkeydatafromSpire['udf']['holes'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'holes', 'value' => (string) $getproductkeydatafromSpire['udf']['holes']);
					}
					if (isset($getproductkeydatafromSpire['udf']['SpecShip']) && !empty($getproductkeydatafromSpire['udf']['SpecShip'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'SpecShip', 'value' => (string) $getproductkeydatafromSpire['udf']['SpecShip']);
					}
					if (isset($getproductkeydatafromSpire['udf']['flexible']) && !empty($getproductkeydatafromSpire['udf']['flexible'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'flexible', 'value' => (string) $getproductkeydatafromSpire['udf']['flexible']);
					}
					if (isset($getproductkeydatafromSpire['udf']['includes']) && !empty($getproductkeydatafromSpire['udf']['includes'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'includes', 'value' => $getproductkeydatafromSpire['udf']['includes']);
					}
					if (isset($getproductkeydatafromSpire['udf']['material']) && !empty($getproductkeydatafromSpire['udf']['material'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'material', 'value' => $getproductkeydatafromSpire['udf']['material']);
					}
					if (isset($getproductkeydatafromSpire['udf']['ShipReady']) && !empty($getproductkeydatafromSpire['udf']['ShipReady'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'ShipReady', 'value' => (string) $getproductkeydatafromSpire['udf']['ShipReady']);
					}
					if (isset($getproductkeydatafromSpire['udf']['madebysmi']) && !empty($getproductkeydatafromSpire['udf']['madebysmi'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'madebysmi', 'value' => (string) $getproductkeydatafromSpire['udf']['madebysmi']);
					}
					if (isset($getproductkeydatafromSpire['udf']['slotholes']) && !empty($getproductkeydatafromSpire['udf']['slotholes'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'slotholes', 'value' => (string) $getproductkeydatafromSpire['udf']['slotholes']);
					}
					if (isset($getproductkeydatafromSpire['udf']['2sidedtape']) && !empty($getproductkeydatafromSpire['udf']['2sidedtape'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => '2sidedtape', 'value' => (string) $getproductkeydatafromSpire['udf']['2sidedtape']);
					}
					if (isset($getproductkeydatafromSpire['udf']['OfflinShip']) && !empty($getproductkeydatafromSpire['udf']['OfflinShip'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'OfflinShip', 'value' => (string)$getproductkeydatafromSpire['udf']['OfflinShip']);
					}
					if (isset($getproductkeydatafromSpire['udf']['incremental']) && !empty($getproductkeydatafromSpire['udf']['incremental'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'incremental', 'value' => (string) $getproductkeydatafromSpire['udf']['incremental']);
					}
					if (isset($getproductkeydatafromSpire['udf']['selectorref']) && !empty($getproductkeydatafromSpire['udf']['selectorref'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'selectorref', 'value' => $getproductkeydatafromSpire['udf']['selectorref']);
					}
					if (isset($getproductkeydatafromSpire['udf']['outsidedurable']) && !empty($getproductkeydatafromSpire['udf']['outsidedurable'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'outsidedurable', 'value' => (string) $getproductkeydatafromSpire['udf']['outsidedurable']);
					}
					if (isset($getproductkeydatafromSpire['udf']['selfadhesivesticker']) && !empty($getproductkeydatafromSpire['udf']['selfadhesivesticker'])) {
						$newProductSchema[$key]['custom_fields'][] = array('name' => 'selfadhesivesticker', 'value' => (string) $getproductkeydatafromSpire['udf']['selfadhesivesticker']);
					}
					if (isset($getproductkeydatafromSpire['udf']['primaryselector']) && !empty($getproductkeydatafromSpire['udf']['primaryselector'])) {
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
						$newProductSchema[$key]['description'] = $getproductkeydatafromSpire['udf']['prodhtml'];
					}
					$newProductSchema[$key]['fixed_cost_shipping_price'] = 0;
					$newProductSchema[$key]['gift_wrapping_options_list'] = [];
					$newProductSchema[$key]['gift_wrapping_options_type'] = 'any';
					$newProductSchema[$key]['gtin'] = '';
					if (isset($getproductkeydatafromSpire['udf']['Height'])) {
						$newProductSchema[$key]['height'] = $getproductkeydatafromSpire['udf']['Height'];
					}
					if ($getproductkeydatafromSpire['links']['images'] . $getproductkeydatafromSpire['id'] . '/data' != '') {
						//if (file_exists($getproductkeydatafromSpire['links']['images'] . $getproductkeydatafromSpire['id'] . '/data')) {
						$getImageUrl = saveImage($getproductkeydatafromSpire['links']['images'] . $getproductkeydatafromSpire['id'] . '/data');
						$getImageUrl = 'https://localhost/'.$getImageUrl;
						//die;
						if(isset($getproductkeydatafromSpire['images']['path'])){
							$imagepath = $getproductkeydatafromSpire['images']['path'];

						} else {
							$imagepath = '';
						}
						if(isset($getproductkeydatafromSpire['udf']['imagealttext'])){
							$imagealttext = $getproductkeydatafromSpire['udf']['imagealttext'];

						} else {
							$imagealttext = '';
						}
						$newProductSchema[$key]['images'] = [
							[
								"description" => $imagealttext,
								"image_file" => $imagepath,
								"image_url" => $getImageUrl,
								"is_thumbnail" => true,
								"sort_order" => '',
								"date_modified" => '',
								"id" => '',
								"product_id" => '',
								"url_standard" => '',
								"url_thumbnail" => $getImageUrl,
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
					//$newProductSchema[$key]['preorder_release_date'] = '';
					$newProductSchema[$key]['price'] = (float)$getproductkeydatafromSpire['pricing'][$getproductkeydatafromSpire['buyMeasureCode']]['sellPrices'][0];
					$newProductSchema[$key]['price_hidden_label'] = '';
					if (isset($getproductkeydatafromSpire['udf']['productCode'])) {
						$newProductSchema[$key]['product_tax_code'] = $getproductkeydatafromSpire['udf']['productCode'];
					}
					$newProductSchema[$key]['related_products'] = [];
					$newProductSchema[$key]['retail_price'] = 0;
					$newProductSchema[$key]['reviews_count'] = 0;
					$newProductSchema[$key]['reviews_rating_sum'] = 0;
					//$newProductSchema[$key]['sale_price'] = '';
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
					$newProductSchema[$key]['variants'] = [];
				}
			}
			return $newProductSchema;
		} else {
			return $newProductSchema;
		}
	}
}
if (!function_exists('saveImage')) {
	function saveImage($url)
	{
		// $url = 'http:\/\/209.151.135.27:10880\/api\/v2\/companies\/smi\/inventory\/items\/163\/images\/163\/data';
		// $filepath = public_path('uploads/image/')."abc.jpg";
		//return Response::download($url);
		$Imageurl = $url;
		//$headers  = pathinfo($Imageurl, PATHINFO_EXTENSION);//($Imageurl, null);
		//$mime_type = $headers['Content-Type'];
		//echo "<pre>";print_r($headers);die;
		//$img = public_path('images') . '\\test.png';
		//if (file_put_contents($img, file_get_contents($Imageurl))) {;
		//return $img;
		//}
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
		$img = config('BASE_URL').'images/' . '/product'.strtotime('now').'.png';
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			//return $response;
			if(file_exists($img)){
				unlink($img);
			}
			$fp = fopen($img,'x');
			$len = fwrite($fp, $response);
			return $img;
			//fclose($fp);
		}

		/*$ch = curl_init ($url);
		$header = array(
			"cache-control: no-cache",
			"postman-token: d34ee56d-cd10-85b8-1154-755973733b6a",
			"x-auth-token: $authorization",
		);
		$img = public_path('images') . '\\test3.png';
		curl_setopt($ch, CURLOPT_HEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$raw=curl_exec($ch);
		curl_close ($ch);
		if(file_exists($img)){
			unlink($img);
		}
		$fp = fopen($img,'x');
		$len = fwrite($fp, $raw);
		return $img;
		//fclose($fp);*/
	}
}
if (!function_exists('download_image1')) {
	function download_image1($image_url, $image_file)
	{
		$fp = fopen(public_path('images/') . $image_file, 'w+');              // open file handle
		$fp = fopen(config('APP_URL').'/images/' . $image_file, 'w+');              // open file handle
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
	function cleanUrl($string)
	{
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

		return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
	}
}
