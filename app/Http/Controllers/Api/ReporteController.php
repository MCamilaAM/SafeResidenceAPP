<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reporte;
use Illuminate\Support\Facades\DB;

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

    // Para el Vigilante: Trae solo los casos "Abiertos"
    public function casosActivos()
    {
        // ACTUALIZADO: Agregamos 'novedades' al with()
        $reportes = Reporte::with(['residente', 'novedades'])->where('estado', 'Abierto')->orderBy('fecha', 'desc')->get();
        return response()->json($reportes);
    }

    public function casosVigilante($id)
    {
        // ACTUALIZADO: Agregamos 'novedades' al with()
        $casos = Reporte::with(['residente', 'novedades'])
                        ->where('vigilante_id', $id)
                        ->where('estado', 'En Proceso') // O el estado que manejen
                        ->get();
        return response()->json($casos);
    }

    // Para el Admin: Trae TODOS los casos
    public function todosLosCasos()
    {
        // ACTUALIZADO: Agregamos 'novedades' al with() para ver todo el historial
        $reportes = Reporte::with(['residente', 'vigilante', 'novedades'])->orderBy('fecha', 'desc')->get();
        return response()->json($reportes);
    }

    // Para que el Vigilante asigne el caso a su nombre
    public function tomarCaso(Request $request, $id)
    {
        $reporte = Reporte::findOrFail($id);
        $reporte->vigilante_id = $request->vigilante_id;
        $reporte->estado = 'En Proceso';
        $reporte->save();

        return response()->json(['mensaje' => '¡Caso tomado con éxito!']);
    }

    public function casosResidente($id)
    {
        // ACTUALIZADO: Añadimos with('novedades') para que el residente lea la bitácora
        $reportes = Reporte::with('novedades')
                            ->where('user_id', $id)
                            ->orderBy('fecha', 'desc')
                            ->get();
        return response()->json($reportes);
    }

    public function metricasDashboard()
    {
        // 1. Definir el rango exacto de la semana (Lunes 00:00:00 a Viernes 23:59:59)
        // startOfWeek() por defecto en Laravel toma el Lunes.
        $inicioSemana = now()->startOfWeek()->format('Y-m-d 00:00:00');
        $finSemana = now()->startOfWeek()->addDays(4)->format('Y-m-d 23:59:59');

        // 2. Gráfico de Pastel: Contar incidentes por categoría (solo en este rango)
        $categorias = Reporte::select('categoria as name', DB::raw('count(*) as poblacion'))
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->groupBy('categoria')
            ->get();

        // 3. Gráfico de Barras 1: Las 4 torres con más incidentes (solo en este rango)
        $torres = Reporte::select('torre_incidente', DB::raw('count(*) as total'))
            ->whereNotNull('torre_incidente')
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->groupBy('torre_incidente')
            ->orderBy('total', 'desc')
            ->limit(4)
            ->get();

        // 4. Gráfico de Barras 2: Casos atendidos por cada guardia (solo en este rango)
        $guardias = DB::table('reportes')
            ->join('users', 'reportes.vigilante_id', '=', 'users.id')
            ->select('users.nombre as etiqueta', DB::raw('count(reportes.id) as total'))
            ->whereNotNull('reportes.vigilante_id')
            ->whereBetween('reportes.fecha', [$inicioSemana, $finSemana])
            ->groupBy('users.nombre')
            ->limit(4)
            ->get();

        return response()->json([
            'categorias' => $categorias,
            'torres' => $torres,
            'guardias' => $guardias,
            // Opcional: Mandamos el rango al frontend por si en el futuro quieres mostrar "Semana del X al Y"
            'rango' => [
                'inicio' => $inicioSemana,
                'fin' => $finSemana
            ]
        ]);
    }

    // Método para que Guardias y Admins agreguen actualizaciones
    public function agregarNovedad(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'mensaje' => 'required|string'
        ]);

        $novedad = \App\Models\Novedad::create([
            'reporte_id' => $id,
            'user_id' => $request->user_id,
            'mensaje' => $request->mensaje
        ]);

        return response()->json(['mensaje' => 'Novedad agregada con éxito', 'novedad' => $novedad], 201);
    }

    // Método exclusivo para que el Admin cierre el caso y aplique multas
    public function cerrarCaso(Request $request, $id)
    {
        // 1. Validaciones básicas de lo que envía el frontend
        $request->validate([
            'admin_id' => 'required',
            'solucion' => 'required|string',
            'aplicar_llamado' => 'boolean',
            'torre' => 'nullable|integer|min:1|max:20', // Límite de 20 torres
            'apto' => 'nullable|integer'
        ]);

        // 2. Validación estricta del apartamento SOLO si se va a aplicar sanción
        if ($request->aplicar_llamado) {
            $apto = $request->apto;
            $piso = floor($apto / 100); 
            $numero = $apto % 100;      

            if ($piso < 1 || $piso > 12 || $numero < 1 || $numero > 8) {
                return response()->json([
                    'mensaje' => 'No se puede cerrar: Apartamento inválido. Debe ser del 101 al 1208.'
                ], 422);
            }
        }

        $reporte = Reporte::findOrFail($id);
        $reporte->estado = 'Resuelto';
        $reporte->save();

        // 3. Guardar la solución como la novedad final del caso
        \App\Models\Novedad::create([
            'reporte_id' => $reporte->id,
            'user_id' => $request->admin_id,
            'mensaje' => "🔒 DECISIÓN FINAL: " . $request->solucion
        ]);

        // 4. Lógica para aplicar el llamado de atención
        if ($request->aplicar_llamado) {
            // Buscamos al infractor en la base de datos
            $infractor = \App\Models\User::where('torre', $request->torre)
                                        ->where('apartamento', $request->apto)
                                        ->first();
            
            // Guardamos la multa SIEMPRE. Si $infractor no existe, el id será null.
            \Illuminate\Support\Facades\DB::table('llamados_atencions')->insert([
                'reporte_id' => $reporte->id, // <-- CORRECCIÓN: Conectamos la multa con el reporte
                'user_id' => $infractor ? $infractor->id : null, 
                'torre' => $request->torre,
                'apartamento' => $request->apto,
                'motivo' => "Infracción (Caso #" . $reporte->id . "): " . $request->solucion,
                'fecha' => now()->format('Y-m-d'),
                'estado' => 'Activo'
            ]);
        }

        return response()->json(['mensaje' => 'Caso cerrado exitosamente']);
    }

    // Para el Residente: Trae sus llamados de atención reales
    public function llamadosUsuario($id)
    {
        $llamados = DB::table('llamados_atencions')
            ->where('user_id', $id)
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($llamados);
    }
}