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
        Schema::table('companies', function (Blueprint $table) {
            $table->text('years_of_experience')->nullable();
            $table->text('type_of_equipment')->nullable();
            $table->text('equipment_availability')->nullable();
            $table->text('pacs_reading')->nullable();
            $table->text('practice_instructions')->nullable();
            $table->text('references')->nullable();
            $table->text('languages_spoken')->nullable();
            $table->text('any_limitation')->nullable();
            $table->text('certifications')->nullable();
            // $table->string('gender')->nullable();
            $table->enum('level', ['Verified', 'UnVerified', 'Verified+'])->default('Verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            //
        });
    }
};