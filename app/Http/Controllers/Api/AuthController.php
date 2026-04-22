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
}