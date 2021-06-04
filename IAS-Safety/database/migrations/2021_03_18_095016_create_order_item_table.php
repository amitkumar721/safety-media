<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_item', function (Blueprint $table) {
            $table->id();
            $table->string('bigc_itemid', 100)->nullable();
            $table->string('order_id', 100)->nullable();
            $table->string('product_id', 100)->nullable();
            $table->string('variant_id', 100)->nullable();
            $table->string('order_address_id', 100)->nullable();
            $table->string('name', 100)->nullable();
            $table->string('name_customer', 100)->nullable();
            $table->string('name_merchant', 100)->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('upc', 100)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('base_price', 100)->nullable();
            $table->string('price_ex_tax', 100)->nullable();
            $table->string('price_inc_tax', 100)->nullable();
            $table->string('price_tax', 100)->nullable();
            $table->string('base_total', 100)->nullable();
            $table->string('total_ex_tax', 100)->nullable();
            $table->string('total_inc_tax', 100)->nullable();
            $table->string('total_tax', 100)->nullable();
            $table->string('weight', 100)->nullable();
            $table->string('width', 100)->nullable();
            $table->string('height', 100)->nullable();
            $table->string('depth', 100)->nullable();
            $table->string('quantity', 100)->nullable();
            $table->string('base_cost_price', 100)->nullable();
            $table->string('cost_price_inc_tax', 100)->nullable();
            $table->string('cost_price_ex_tax', 100)->nullable();
            $table->string('cost_price_tax', 100)->nullable();
            $table->string('is_refunded', 100)->nullable();
            $table->string('quantity_refunded', 100)->nullable();
            $table->string('refund_amount', 100)->nullable();
            $table->string('return_id', 100)->nullable();
            $table->string('wrapping_name', 100)->nullable();
            $table->string('base_wrapping_cost', 100)->nullable();
            $table->string('wrapping_cost_ex_tax', 100)->nullable();
            $table->string('wrapping_cost_inc_tax', 100)->nullable();
            $table->string('wrapping_cost_tax', 100)->nullable();
            $table->string('wrapping_message', 100)->nullable();
            $table->string('quantity_shipped', 100)->nullable();
            $table->string('event_name', 100)->nullable();
            $table->string('event_date', 100)->nullable();
            $table->string('fixed_shipping_cost', 100)->nullable();
            $table->string('ebay_item_id', 100)->nullable();
            $table->string('ebay_transaction_id', 100)->nullable();
            $table->string('option_set_id', 100)->nullable();
            $table->string('parent_order_product_id', 100)->nullable();
            $table->string('is_bundled_product', 100)->nullable();
            $table->string('bin_picking_number', 100)->nullable();
            $table->string('external_id', 100)->nullable();
            $table->string('fulfillment_source', 100)->nullable();
            $table->string('applied_discounts', 100)->nullable();
            $table->text('product_options')->nullable();
            $table->string('configurable_fields', 100)->nullable();
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
        Schema::dropIfExists('order_item');
    }
}
