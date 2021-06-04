<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Orderaddress extends Model
{
    //
    protected $table = 'order_address';

    function orderData () {
        return $this->hasOne('App\Order', 'bigc_orderid', 'bigc_order_id');
     }


   
}
