<?php

namespace App;
use DB;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';
    
    //
    // public function orderData(){
    //     return $this->hasOne('App\Customer', 'bigc_customer_id', 'customer_id');
        
    // }

    function orderBillingAddress () {
        return $this->hasOne('App\Orderaddress', 'bigc_order_id', 'bigc_orderid')->where('type', 'B');
     }

     function orderShippingAddress () {
        return $this->hasOne('App\Orderaddress', 'bigc_order_id', 'bigc_orderid')->where('type', 'S');
     }

     function orderCustomer () {
         return $this->hasOne('App\Customer', 'bigc_customer_id', 'customer_id');
         //return $this->belongsTo('App\Customer', 'customer_id', 'bigc_customer_id');
     }

     function orderItems () {
        return $this->hasMany('App\Orderitem', 'order_id', 'bigc_orderid');
     }

     
}
