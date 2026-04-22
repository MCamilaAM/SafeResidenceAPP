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
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            // Información de quién reporta:
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('categoria'); 
            $table->string('estado')->default('Abierto'); 
            
            // Información de dónde ocurre el incidente:
            $table->integer('torre_incidente')->nullable(); // Opcional
            $table->integer('apartamento_incidente')->nullable(); // Opcional
            $table->string('area_comun')->nullable(); // Ej: "Piscina", "Lobby"
            $table->dateTime('fecha');
            $table->foreignId('vigilante_id')->nullable()->constrained('users'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
