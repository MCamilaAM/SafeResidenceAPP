<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    use HasFactory;

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

    public function residente() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vigilante() {
        return $this->belongsTo(User::class, 'vigilante_id');
    }

    public function novedades() {
        // El ->with('usuario') hace que al pedir la novedad, también traiga el nombre de quien la escribió
        return $this->hasMany(Novedad::class)->with('usuario')->orderBy('created_at', 'asc');
    }
}