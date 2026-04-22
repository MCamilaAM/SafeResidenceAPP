<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    use HasFactory;

    // Le decimos a Laravel qué campos puede llenar el usuario desde la App
    // Esto es vital para que el controlador funcione
    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'categoria',
        'estado',
        'torre_incidente',
        'apartamento_incidente',
        'area_comun',
        'fecha',
        'vigilante_id',
    ];

    /**
     * Relación: Un reporte pertenece a un usuario (el residente que lo crea)
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Un reporte puede tener un vigilante asignado
     */
    public function vigilante()
    {
        return $this->belongsTo(User::class, 'vigilante_id');
    }
}