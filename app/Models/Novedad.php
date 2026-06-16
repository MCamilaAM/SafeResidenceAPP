<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Novedad extends Model
{
    use HasFactory;

    protected $fillable = ['reporte_id', 'user_id', 'mensaje'];

    // Relación: Esta novedad pertenece a un reporte
    public function reporte() {
        return $this->belongsTo(Reporte::class);
    }

    // Relación: Esta novedad fue escrita por un usuario
    public function usuario() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
