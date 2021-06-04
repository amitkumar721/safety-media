<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User; 
use Illuminate\Support\Facades\Auth; 

class CartController extends Controller
{
    public function testConnection()
    {
        $url = 'http://192.168.6.2:8000/api/testData';
        //echo $url;die;
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
                "content-type: application/json",
            ),

        ));
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        print_r($statusCode);
    }
    public function testRequest()
    {
        $auth_user =  config('config.Auth_User');
        $auth_pass =  config('config.Auth_Pass');
        if (Auth::attempt(['email' => $auth_user, 'password' => $auth_pass])) {
            $user = Auth::user();
            $token = openssl_random_pseudo_bytes(16);

            //Convert the binary data into hexadecimal representation.
            $token = bin2hex($token);

            $update = User::where('id', $user['id'])
                ->update([
                    'usertoken' => $token
                ]);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
        $url = 'http://192.168.6.2:8000/api/callCurl';
        //echo $url;die;
        $authorization = config('config.BigCommerce_Api_Auth');
        $urls = 'https://api.bigcommerce.com/stores/z84xkjcnbz/v3/customers?id:in=2';
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
                "Accept: application/json",
                "x-auth-token: $authorization",
                "url: $urls",
                "method: GET",
                "token: $token"
            ),

        ));
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);
        $userdata = User::all();
        $mydata = json_decode($response, TRUE);
        if ($userdata[0]['usertoken'] == "ggsgsghdvsghdvdshgdvgdhv") {

            print_r($mydata['bigcommercedata']);
        } else {


            echo "Unauthorized request";
        }
    }
    public function createCart(Request $request)
    {

        //header("Access-Control-Allow-Origin: *");
        /**
         * Update a cart 
         *
         * @param $cartData array
         * contains the cart data
         *
         * @param $updatePrice string
         * contains the product price
         *
         * @param $cartId string
         * contains the cart id
         *
         * @param $itemId string
         * contains the item id
         *
         * */
        $cartData = $request->jsonData;
        $updatePriceProduct = $request->updatePrice;
        $cartId = $request->cartID;
        $itemId = $request->itemID;
        //$cartArray = json_decode($cartData,true);
        $updatePrice = ltrim($updatePriceProduct, '$');
        $updatePriceValue = rtrim($updatePrice, '/each');
        //echo "<pre>";print_r($updatePrice);die;
        $line_items = [
            'quantity' => $cartData[0]['quantity'],
            'product_id' => $cartData[0]['productId'],
            'list_price' => $updatePriceValue,
        ];
        $cartarray = ['line_item' => $line_items];
        //echo "<pre>";print_r(json_encode($cartarray));die;
        $url =  config('config.BigCommerce_Api_Url'); //get Big Commerce api url
        $bigcommerce_updateCart_url = $url . 'carts/' . $cartId . '/items/' . $itemId;
        $authorization = config('config.BigCommerce_Api_Auth'); //get big commerce api authorization key
        if (!empty($cartData)) {
            $createCart = call_curl($bigcommerce_updateCart_url, $method = "PUT",  $payload = $cartarray, $authorization); //call curl
            $createCartResponse = json_decode($createCart['response'], true);
            //  echo "<pre>";
            //  print_r($createCart);
            //  die;
            if ($createCart['status'] == 200) {

                if ($createCart['status'] == 200) {
                    $result['Status'] = 'True';
                    $result['Message'] = 'Product add to cart update successfully!';
                    $result['Result'] = array_values($createCartResponse);
                    return json_encode($result);
                    die;
                } else {
                    $result['Status'] = 'False';
                    $result['Message'] = 'Product add to cart update failed!';
                    $result['Result'] = $createCartResponse;
                    return json_encode($result);
                    die;
                }
            } else {
                $result['Status'] = 'False';
                $result['Message'] = 'Product add to cart update falied!';
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
}
