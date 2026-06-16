<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('novedads', function (Blueprint $table) {
            $table->id();
            
            // Relación con el reporte
            $table->foreignId('reporte_id')->constrained('reportes')->onDelete('cascade');
            
            // Relación con el usuario (quien escribe la novedad: admin o guardia)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->text('mensaje');
            $table->timestamps(); // Crea automáticamente created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('novedads');
    }
};
