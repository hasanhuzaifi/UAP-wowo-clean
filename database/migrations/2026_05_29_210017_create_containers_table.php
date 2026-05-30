<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Jalankan: php artisan make:migration create_containers_table
     * Kemudian isi method up() dengan kode ini
     */
    public function up(): void
    {
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('container_id')->unique(); // Format: 2 huruf kapital + 5 angka, contoh: AB12345
            $table->string('waste_type');
            $table->float('weight_kg');              // 10 - 5000 kg, Chemical max 1000 kg
            $table->enum('status', ['Active', 'Archived'])->default('Active');
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
