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
            $table->unsignedBigInteger('gifting_id')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('description');
            $table->string('thumbnail');
            $table->integer('quantity');
            $table->integer('minimum_order_quantity')->nullable();
            $table->string('estimated_delivery_time')->nullable();
            $table->enum('product_type', ['product', 'bag']);
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('gifting_id')->references('id')->on('giftings')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
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
