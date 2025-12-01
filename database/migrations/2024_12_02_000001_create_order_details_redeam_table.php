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
        Schema::create('order_details_redeam', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('reference_number')->nullable()->index();
            $table->string('hold_id', 150)->nullable()->index();
            $table->timestamp('hold_expires_at')->nullable();
            $table->string('booking_id', 150)->nullable()->index();
            $table->longText('booking_data')->nullable();
            $table->string('voucher')->nullable();
            $table->string('supplier_type')->default('redeam')->index(); // disney, united_parks, etc.
            $table->string('supplier_reference')->nullable()->index();
            $table->string('confirmation_number')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Add indexes for performance
            $table->index(['order_id', 'supplier_type']);
            $table->index(['status', 'created_at']);
            $table->index(['hold_expires_at', 'hold_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details_redeam');
    }
};
