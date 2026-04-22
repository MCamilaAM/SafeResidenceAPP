<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reporte;

class ReporteController extends Controller
{
    public function enviarReporte(Request $request)
    {
        // 1. Validaciones básicas
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'titulo' => 'required|string|max:100',
            'descripcion' => 'required|string',
            'categoria' => 'required|string',
            'torre_incidente' => 'nullable|integer|min:1|max:20',
            'apartamento_incidente' => 'nullable|integer',
            'area_comun' => 'nullable|string|max:100',
            'fecha' => 'required|date' 
        ]);

        // 2. Validación estricta SOLO si se provee un apartamento
        if ($request->filled('apartamento_incidente')) {
            $apto = $request->apartamento_incidente;
            $piso = floor($apto / 100); 
            $numero = $apto % 100;      

            if ($piso < 1 || $piso > 12 || $numero < 1 || $numero > 8) {
                return response()->json([
                    'message' => 'Apartamento inválido. Debe ser del 101 al 1208.'
                ], 422);
            }
        }

        // 3. Crear el reporte
        $reporte = Reporte::create([
            'user_id' => $request->user_id,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'categoria' => $request->categoria,
            'torre_incidente' => $request->torre_incidente,
            'apartamento_incidente' => $request->apartamento_incidente,
            'area_comun' => $request->area_comun,
            'fecha' => $request->fecha, 
            'estado' => 'Abierto'
        ]);

        return response()->json([
            'mensaje' => '¡Reporte enviado con éxito!',
            'reporte' => $reporte
        ], 201);
    }
}
