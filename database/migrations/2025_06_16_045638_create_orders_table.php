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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->integer('number_of_boxes');
            $table->integer('estimated_budget');
            $table->string('currency')->default('USD');
            $table->boolean('products_in_bag')->default(false);
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->string('campain_name')->nullable();
            $table->integer('redeem_quantity')->default(0);
            $table->enum('multiple_delivery_address', ['yes', 'no'])->default('no');
            $table->enum('campain_type', ['microsite', 'gift_redeemption']);
            $table->enum('gift_box_type', ['gifte_branded', 'custom_branding', 'plain']);
            $table->string('slug');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
