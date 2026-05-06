<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReporteController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/registro', [AuthController::class, 'registrar']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/reportar', [ReporteController::class, 'enviarReporte']);

Route::get('/reportes/activos', [App\Http\Controllers\Api\ReporteController::class, 'casosActivos']);
Route::get('/reportes/todos', [App\Http\Controllers\Api\ReporteController::class, 'todosLosCasos']);
Route::post('/reportes/{id}/tomar', [App\Http\Controllers\Api\ReporteController::class, 'tomarCaso']);

Route::post('/cambiar-password', [App\Http\Controllers\Api\AuthController::class, 'cambiarPassword']);
Route::get('/directorio', [App\Http\Controllers\Api\AuthController::class, 'directorio']);
Route::get('/reportes/vigilante/{id}', [ReporteController::class, 'casosVigilante']);