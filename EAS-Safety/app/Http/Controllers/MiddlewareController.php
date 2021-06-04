<?php

namespace App\Http\Controllers;

use Sabre\DAV\Client;

require '../vendor/autoload.php';

class MiddlewareController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}

	function call_curl()
	{
		$headers = apache_request_headers();
		$authorization = $headers['x-auth-token'];
		$method = $headers['method'];
		$url = $headers['url'];
		$str = $headers['token'];
		$data = json_decode(file_get_contents('php://input'), true);
		$payload = $data;
		$curl = curl_init();
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
					"x-auth-token: $authorization",
				),
			));
		}
		$response = curl_exec($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			return response()->json([
				'bigcommercedata' => json_decode($err),
				"status" => $statusCode,
				'token' => $str
			]);
			// return array( 
			// 	"status" => $statusCode,
			// 	"response" => $err
			// );
		} else {
			return response()->json([
				'bigcommercedata' => json_decode($response),
				"status" => $statusCode,
				'token' => $str
			]);
			// return array(
			// 	"status" => $statusCode,
			// 	"response" => $response
			// );
		}
	}

	//

	public function getCurlRequest()
	{
		$headers = apache_request_headers();
		$authorization = $headers['x-auth-token'];
		$url = $headers['url'];
		$method = $headers['method'];
		$token = $headers['token'];
		$payload = json_decode(file_get_contents('php://input'), true);

		$curl = curl_init();
		if ($method == 'POST' || $method == 'PUT') {
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
					"x-auth-token: $authorization",
				),
			));
		} else {
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_HTTPHEADER => array(
					"accept: application/json",
					"content-type: application/json",
					"x-auth-token: $authorization",
				),
			));
		}

		$response = curl_exec($curl);
		$err = curl_error($curl);

		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$response = json_decode($response, true);

		curl_close($curl);
		if ($err) {
			return array(
				"status" => $statusCode,
				"responseData" => $err,
				'token' => $token
			);
		} else {
			if (!empty($response)) {
				return array(
					"status" => $statusCode,
					"responseData" => $response,
					'token' => $token
				);
			} else {
				return array(
					"status" => $statusCode,
					"responseData" => $response,
					'token' => $token
				);
			}
		}
	}
	public function uploadImageWebdev() {
		$settings = array(
			'baseUri' => "https://store-z84xkjcnbz.mybigcommerce.com/dav/content/images/",
			'userName' => 'amitk10@chetu.com',
			'password' => '7ea5038d4b224d84328fd13719531d903861f396'
		);
		
		$client = new Client($settings);
		//$response = $client->request('GET');
		
		//$features = $client->options();
		
		$upload_result = $client->request('PUT', "C:\Users\Chetu\Desktop/AA64.png", file_get_contents("C:\Users\Chetu\Desktop/AA64.png"));
		//var_dump($upload_result);
		// List a folder's contents
		$folder_content = $client->propFind('/dav/content/images/', array(
			'{DAV:}getlastmodified',
			'{DAV:}getcontenttype',
		), 1);
		var_dump($folder_content);
	}
	public function saveImage()
	{
		/**
		 * Create a custom image with spire image url and returns a custom image url 
		 *
		 * @param string $url
		 * url contains the spire image  url
		 *
		 * @return string image url
		 *
		 * */
		$headers = apache_request_headers();
		$imageBinaryData = $headers['imageBinaryData'];
		$contentType = $headers['contentType'];
		$sequence = $headers['sequence'];
		$id = $headers['id'];



		$img =  base_path(). '/BigC/' . 'product' . $sequence.$id . '.' . $contentType;
		$imgPath =  'http://safetymediainc.net/BigC/product' . $sequence.$id . '.' . $contentType;
		//echo $imageBinaryData;die;
		if (!$imageBinaryData) {
			// echo "cURL Error #:" . $err;
			return array(
				"status" => 400,
			);
		} else {
			if (file_exists($img)) {
				unlink($img);
			}
			$image_parts = explode(";base64,", $imageBinaryData);
			$image_type_aux = explode("image/", $image_parts[0]);
			$image_type = $image_type_aux[1];
			$image_base64 = base64_decode($image_parts[1]);
			$fp = fopen($img, 'x');
			$len = fwrite($fp, $image_base64);
			//return $img;
			return array(
				"status" => 200,
				"responseData" => $imgPath,
				'contentType' => $contentType
			);
		}
	}
}
