<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_details_universal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('galaxy_order_id')->nullable()->index();
            $table->string('external_order_id')->nullable()->index();
            $table->longText('booking_data')->nullable();
            $table->string('voucher')->nullable();
            $table->string('confirmation_number')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->string('supplier_reference')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Add indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['galaxy_order_id', 'external_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details_universal');
    }
};
