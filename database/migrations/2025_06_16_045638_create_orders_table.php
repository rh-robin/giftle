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
            $table->integer('number_of_boxes')->nullable();
            $table->integer('estimated_budget')->nullable();
            $table->boolean('products_in_bag')->default(false);
            $table->unsignedBigInteger('gift_box_id')->nullable();
            $table->enum('gift_box_type', ['giftle_branded', 'custom_branding', 'plain'])->nullable();
            $table->enum('status', ['pending', 'action', 'completed', 'cancelled', 'processing'])->default('pending');
            $table->enum('campaign_type', ['microsite', 'gift_redemption'])->nullable();
            $table->string('campaign_name')->nullable();
            $table->integer('gift_redeem_quantity')->nullable();
            $table->boolean('multiple_delivery_address')->default(false);
            $table->string('slug')->unique();
            $table->decimal('price_usd', 10, 2);
            $table->string('user_currency')->nullable();
            $table->decimal('exchange_rate', 10, 2)->nullable();
            $table->decimal('price_in_currency', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'cancelled', 'hold'])->default('pending')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('gift_box_id')->references('id')->on('gift_boxes')->onDelete('set null');
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
