<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_products', function (Blueprint $table) {
            $table->id();
            // ID in the external product source, so deliberately not a foreign key.
            $table->unsignedBigInteger('product_id')->unique();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_products');
    }
};
