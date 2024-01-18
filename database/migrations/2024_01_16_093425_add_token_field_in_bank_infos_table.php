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
        Schema::table('bank_infos', function (Blueprint $table) {
            $table->string('country')->nullable();
            $table->string('currency')->nullable();
            $table->string('stripe_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_infos', function (Blueprint $table) {
            //
        });
    }
};