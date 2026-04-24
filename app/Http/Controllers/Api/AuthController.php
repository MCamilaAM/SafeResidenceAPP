<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registrar(Request $request)
    {
        // 1. Validación Condicional
        $reglas = [
            'nombre' => 'required',
            'apellidos' => 'required',
            'correo' => 'required|email|unique:users',
            'celular' => 'required',
            'cedula' => 'required',
            'rol' => 'required',
            'password' => 'required|min:6'
        ];

        // Solo obligatorios si es Residente
        if ($request->rol === 'Residente') {
            $reglas['torre'] = 'required|integer|min:1|max:20';
            $reglas['apartamento'] = 'required';
        }

        $request->validate($reglas);

        // 2. Crear el usuario
        $user = User::create([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'correo' => $request->correo,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'celular' => $request->celular,
            'cedula' => $request->cedula,
            'torre' => $request->torre,
            'apartamento' => $request->apartamento,
        ]);

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

        $user = \App\Models\User::find($request->user_id);

        // 1. Verificamos que la contraseña antigua sea la correcta
        if (!Hash::check($request->password_actual, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.'
            ], 400); // 400 significa "Bad Request" (Error del usuario)
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
        $residentes = \App\Models\User::where('rol', 'residente')->get()->map(function ($residente) {
            
            // 1. ¿Cuántos casos ha reportado este residente?
            $residente->casos_reportados = \App\Models\Reporte::where('user_id', $residente->id)->count();

            // 2. ¿Cuántas quejas hay contra su torre y apartamento? (La magia que pediste)
            // Solo contamos si la torre y apto coinciden, y el estado NO es 'Descartado'
            if ($residente->torre && $residente->apartamento) {
                $residente->quejas_recibidas = \App\Models\Reporte::where('torre_incidente', $residente->torre)
                    ->where('apartamento_incidente', $residente->apartamento)
                    ->where('estado', '!=', 'Descartado') 
                    ->count();
            } else {
                $residente->quejas_recibidas = 0;
            }

            return $residente;
        });

        return response()->json($residentes);
    }
}