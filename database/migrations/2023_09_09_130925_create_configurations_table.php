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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->boolean('enable_return_address')->default(false);
            $table->string('default_length_unit')->nullable();
            $table->string('default_volume_unit')->nullable();
            $table->string('address_product_reference_id')->nullable();
            $table->string('address_product_reference')->nullable();
            $table->boolean('enable_packing_list')->default(false);
            $table->boolean('enable_balance_alert')->default(false);
            $table->boolean('enable_low_stock_alert')->default(false);
            $table->integer('consignment_count_exceed_notice')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
