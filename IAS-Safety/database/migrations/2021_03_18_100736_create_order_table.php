<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->string('bigc_orderid', 100)->nullable();
            $table->string('base_handling_cost', 100)->nullable();
            $table->string('base_shipping_cost', 100)->nullable();
            $table->string('base_wrapping_cost', 100)->nullable();
            $table->string('cart_id', 100)->nullable();
            $table->string('channel_id', 100)->nullable();
            $table->string('coupon_discount', 100)->nullable();
            $table->string('coupons', 100)->nullable();
            $table->string('currency_code', 100)->nullable();
            $table->string('currency_exchange_rate', 100)->nullable();
            $table->string('currency_id', 100)->nullable();
            $table->string('custom_status', 100)->nullable();
            $table->string('customer_id', 100)->nullable();
            $table->string('customer_locale', 100)->nullable();
            $table->string('customer_message', 100)->nullable();
            $table->string('date_created', 100)->nullable();
            $table->string('date_modified', 100)->nullable();
            $table->string('date_shipped', 100)->nullable();
            $table->string('default_currency_code', 100)->nullable();
            $table->string('default_currency_id', 100)->nullable();
            $table->string('discount_amount', 100)->nullable();
            $table->string('ebay_order_id', 100)->nullable();
            $table->string('geoip_country', 100)->nullable();
            $table->string('geoip_country_iso2', 100)->nullable();
            $table->string('gift_certificate_amount', 100)->nullable();
            $table->string('handling_cost_ex_tax', 100)->nullable();
            $table->string('handling_cost_inc_tax', 100)->nullable();
            $table->string('handling_cost_tax', 100)->nullable();
            $table->string('handling_cost_tax_class_id', 100)->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_provider_id', 100)->nullable();
            $table->string('payment_status', 100)->nullable();
            $table->string('shipping_cost_ex_tax', 100)->nullable();
            $table->string('shipping_cost_inc_tax', 100)->nullable();
            $table->string('shipping_cost_tax', 100)->nullable();
            $table->string('shipping_cost_tax_class_id', 100)->nullable();
            $table->string('staff_notes', 100)->nullable();
            $table->string('status', 100)->nullable();
            $table->string('status_id', 100)->nullable();
            $table->string('store_default_currency_code', 100)->nullable();
            $table->string('store_default_to_transactional_exchange_rate', 100)->nullable();
            $table->string('subtotal_ex_tax', 100)->nullable();
            $table->string('subtotal_inc_tax', 100)->nullable();
            $table->string('subtotal_tax', 100)->nullable();
            $table->string('tax_provider_id', 100)->nullable();
            $table->string('total_ex_tax', 100)->nullable();
            $table->string('total_inc_tax', 100)->nullable();
            $table->string('total_tax', 100)->nullable();
            $table->string('wrapping_cost_ex_tax', 100)->nullable();
            $table->string('wrapping_cost_inc_tax', 100)->nullable();
            $table->string('wrapping_cost_tax', 100)->nullable();
            $table->boolean('direct_align_with_spire')->comment('0=Not Direct Align, 1= Direct Align');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order');
    }
}
