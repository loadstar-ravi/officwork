<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            // ID in the external product source, so deliberately not a foreign key.
            $table->unsignedBigInteger('product_id')->index();
            // Snapshot of the title at enquiry time, in case the source changes it.
            $table->string('product_title');
            $table->string('name', 100);
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
