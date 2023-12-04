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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('reg_no')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('referrer_id')->nullable();
            $table->string('password');
            $table->boolean('is_verified')->default(false);
            $table->boolean('terms')->default(false);
            $table->enum('status', ['Pending', 'Active', 'Deactive', 'Rejected'])->default('Pending');
            // $table->enum('role', ['Client'])->default('Client');
            $table->enum('role', ['Doctor/Facility', 'Sonographer'])->default('Doctor/Facility');
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};