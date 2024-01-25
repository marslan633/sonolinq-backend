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
        Schema::create('eligible_sonographers', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('sonographer_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            
            $table->foreign('sonographer_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            // $table->enum('status', ['Pending', 'Active', 'Deactive', 'Rejected'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eligible_sonographers');
    }
};