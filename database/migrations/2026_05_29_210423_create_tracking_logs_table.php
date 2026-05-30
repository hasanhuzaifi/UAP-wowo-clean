<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Jalankan: php artisan make:migration create_tracking_logs_table
     * Kemudian isi method up() dengan kode ini
     */
    public function up(): void
    {
        Schema::create('tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')
                  ->constrained('containers')
                  ->onDelete('cascade');    // Relasi One-to-Many: tracking_logs belongsTo containers
            $table->string('location');
            $table->timestamp('timestamp');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_logs');
    }
};
