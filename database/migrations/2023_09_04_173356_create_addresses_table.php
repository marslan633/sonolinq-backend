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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->string('address_name')->nullable();
            $table->text('address_line_one')->nullable();
            $table->text('address_line_two')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('county')->nullable();
            $table->string('post_code')->nullable();
            $table->enum('type', ['Personal', 'Return'])->default('Personal');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
