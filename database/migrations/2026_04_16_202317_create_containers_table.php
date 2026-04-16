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
    Schema::create('containers', function (Blueprint $table) {
        $table->id();
        $table->string('container_id')->unique();
        $table->string('waste_type');
        $table->integer('weight_kg');
        $table->string('status')->default('Active');
        $table->json('tracking_logs')->nullable(); // Untuk menyimpan data log
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
};
