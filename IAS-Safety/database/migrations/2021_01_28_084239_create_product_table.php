<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->id('product_id');
            $table->string('product_name');
            $table->string('sku');
            $table->string('description');
            $table->string('price');
            $table->string('categories');
            $table->string('is_featured');
            $table->string('availability');
            $table->string('availability_description');
            $table->string('images');
            $table->text('custom_fields');
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
        Schema::dropIfExists('product');
    }
}
