<?php
   
namespace App\Console\Commands;
   
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class DemoCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cron';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	 \Log::info("Cron is working fine!");	
		
 $todayDate = date("Y-m-d H:i:s");   
$prev_date = date('Y-m-d\%20H:i:s', strtotime($todayDate . ' -10 minutes'));

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => config('config.Spire_Api_Url').'sales/orders/?filter={"modified":{"$gte":"' . $prev_date . '"}}',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Basic QmlnYzpDaGV0dUAxMjM='
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);
\Log::info($response);
if($response)
{	
$arrayresponse = json_decode($response, true);

foreach ($arrayresponse['records'] as $getdata) {
	
	if($getdata['phaseId']!=''){
		
	$id=$getdata['id'];	




   $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://209.151.135.27:10880/api/v2/companies/smi/sales/orders/'. $id .'',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Basic QmlnYzpDaGV0dUAxMjM='
  ),
));

$orderdata = curl_exec($curl);

curl_close($curl);
$dataorders = json_decode($orderdata, true);
   
	// \Log::info($dataorders);	
if($dataorders['udf']['bigcommerce_order_id']!='')
{	
$bigorderid=$dataorders['udf']['bigcommerce_order_id'];
$phasedata=$getdata['phaseId'];
\Log::info($getdata['phaseId']); 
\Log::info($bigorderid);

	
if($phasedata=="SHIPPED")
{
$statusid=2;
}
if($phasedata=="Partially Shipped")
{
$statusid=3;
}

if($phasedata=="Awaiting Payment")
{
$statusid=7;
}
if($phasedata=="PICKUP")
{
$statusid=8;
}
if($phasedata=="SHIP")
{
$statusid=9;
}
if($phasedata=="Completed")
{
$statusid=10;
}
if($phasedata=="FULFILL")
{
$statusid=11;
}



  \Log::info($statusid);
 
	


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.bigcommerce.com/stores/z84xkjcnbz/v2/orders/'.$bigorderid.'',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false),
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'PUT',
  CURLOPT_POSTFIELDS =>'{
  "status_id":'.$statusid.'
}',
  CURLOPT_HTTPHEADER => array(
    'X-Auth-Token: tqhvp7fmyqr438pewjwtcwi1vggxpky',
    'content-type: application/json'
  ),
));


$orderidresponse = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
     \Log::info($err);
} else {
   \Log::info($orderidresponse);
}	

} else {
   \Log::info("big id not available");
}
 
 }
}
}else
{
return 0;
\Log::info($err);
}	


// \Log::info($response);
   // \Log::info($prev_date); 


 
  
		
		
		// \Log::info(json_encode($response));
		
		
        \Log::info("After Sales sync");
		
	
        /*
           Write your database logic we bellow:
           Item::create(['name'=>'hello new']);
        */
      
        $this->info('Demo:Cron Cummand Run successfully!');
    }
}