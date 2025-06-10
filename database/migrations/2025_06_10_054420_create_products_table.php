<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('giftings_id');
            $table->unsignedBigInteger('catalog_id');
            $table->string('name');
            $table->string('description');
            $table->integer('price');
            $table->integer('quantity');
            $table->integer('minimum_order_quantity');
            $table->string('estimated_delivery_time');
            $table->enum('product_type', ['product', 'bag']);
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreign('catalog_id')->references('id')->on('catalogues')->onDelete('cascade');
            $table->foreign('giftings_id')->references('id')->on('giftings')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
