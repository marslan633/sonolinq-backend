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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('sonographer_id')->nullable();
            
            $table->foreign('doctor_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('sonographer_id')->references('id')->on('clients')->onDelete('cascade');
            
            // $table->unsignedBigInteger('service_category_id')->nullable();
            // $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('cascade');

            // $table->unsignedBigInteger('service_id')->nullable();
            // $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            

            // $table->string('type')->nullable();
            // $table->string('date')->nullable();
            // $table->string('time')->nullable();
            $table->enum('status', ['Pending', 'Active', 'Deactive', 'Delivered', 'Completed', 'Rejected', 'Cancelled'])->default('Pending');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};