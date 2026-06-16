<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // IMPORTANTE: Agregamos la fachada DB para las consultas directas

class AuthController extends Controller
{
    public function registrar(Request $request)
    {
        // 1. Validación Condicional Corregida
        $reglas = [
            'nombre' => 'required|string|max:50',
            'apellidos' => 'required|string|max:50',
            'correo' => 'required|email|unique:users',
            'celular' => 'required|string',
            'cedula' => 'required|string',
            'rol' => 'required|string',
            'password' => 'required|min:6'
        ];

        // Convertimos a minúsculas para evitar fallos si el frontend manda 'Residente' o 'residente'
        $rolFormateado = strtolower($request->rol);

        // Solo obligatorios si es Residente
        if ($rolFormateado === 'residente') {
            $reglas['torre'] = 'required|integer|min:1|max:20';
            $reglas['apartamento'] = 'required|integer';
        }

        $request->validate($reglas);

        // 2. Validación estricta del rango del apartamento físico
        if ($rolFormateado === 'residente') {
            $apto = $request->apartamento;
            $piso = floor($apto / 100); 
            $numero = $apto % 100;      

            if ($piso < 1 || $piso > 12 || $numero < 1 || $numero > 8) {
                return response()->json([
                    'message' => 'Apartamento inválido. Debe ser del 101 al 1208 (Pisos 1-12, Aptos 1-8).'
                ], 422);
            }
        }

        // 3. Crear el usuario
        $user = User::create([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'correo' => $request->correo,
            'password' => Hash::make($request->password),
            'rol' => $rolFormateado, // Lo guardamos estandarizado en minúsculas
            'celular' => $request->celular,
            'cedula' => $request->cedula,
            'torre' => $request->torre,
            'apartamento' => $request->apartamento,
        ]);

        // 4. LA MAGIA: Buscar llamados de atención huérfanos y asignárselos de inmediato
        if ($rolFormateado === 'residente' && $user->torre && $user->apartamento) {
            DB::table('llamados_atencions')
                ->where('torre', $user->torre)
                ->where('apartamento', $user->apartamento)
                ->whereNull('user_id') // Solo las que no tienen dueño aún
                ->update(['user_id' => $user->id]); // Les anclamos el ID del nuevo usuario
        }

        return response()->json([
            'mensaje' => '¡Usuario registrado con éxito!',
            'usuario' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('correo', $request->correo)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        return response()->json(['mensaje' => '¡Bienvenido!', 'usuario' => $user], 200);
    }

    public function cambiarPassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'password_actual' => 'required',
            'password_nueva' => 'required|min:6'
        ]);

        $user = User::find($request->user_id);

        // 1. Verificamos que la contraseña antigua sea la correcta
        if (!Hash::check($request->password_actual, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.'
            ], 400); 
        }

        // 2. Si es correcta, la cambiamos y guardamos
        $user->password = Hash::make($request->password_nueva);
        $user->save();

        return response()->json([
            'mensaje' => '¡Contraseña actualizada con éxito!'
        ], 200);
    }

    public function directorio()
    {
        // Traemos solo a los usuarios que son residentes
        $residentes = User::where('rol', 'residente')->get()->map(function ($residente) {
            
            // 1. ¿Cuántos casos ha reportado este residente al sistema?
            $residente->casos_reportados = \App\Models\Reporte::where('user_id', $residente->id)->count();

            // 2. CORRECCIÓN: Llamados de atención CONFIRMADOS
            // Ahora consultamos directamente la tabla de multas/sanciones reales
            if ($residente->torre && $residente->apartamento) {
                $residente->quejas_recibidas = \Illuminate\Support\Facades\DB::table('llamados_atencions')
                    ->where('torre', $residente->torre)
                    ->where('apartamento', $residente->apartamento)
                    ->count();
            } else {
                $residente->quejas_recibidas = 0;
            }

            return $residente;
        });

        return response()->json($residentes);
    }
}