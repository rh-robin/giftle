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
        Schema::create('gift_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('giftle_branded_price');
            $table->integer('custom_branding_price');
            $table->integer('plain_price');
            $table->string('image');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_boxes');
    }
};
