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
        Schema::create('llamados_atencions', function (Blueprint $table) {
            $table->id();
            
            // NUEVA LÍNEA: Vincula la multa al reporte original. Si el reporte se borra, la multa también.
            $table->foreignId('reporte_id')->nullable()->constrained('reportes')->onDelete('cascade');
            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('torre');
            $table->integer('apartamento');
            $table->string('motivo');
            $table->date('fecha');
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llamados_atencions');
    }
};
