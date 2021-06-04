<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductBulkPrice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_bulk_price', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('product_bulkprice_id')->nullable();
            $table->integer('variant_id')->nullable();
            $table->text('amount')->nullable();
            $table->text('quantity_max')->nullable();
            $table->text('quantity_min')->nullable();
            $table->text('type')->nullable();
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
        Schema::dropIfExists('product_bulk_price');
    }
}
