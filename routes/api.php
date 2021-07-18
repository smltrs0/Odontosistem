<?php

use App\Facturas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('obtener-facturas', function () {

    $facturas = Facturas::with('abonos');
    // dd($facturas);
    return datatables()
        ->eloquent($facturas)
        ->addColumn('nombre_paciente', function ($factura) {
            return $factura->nombre_paciente = ucfirst($factura->cita->paciente->name)." ".ucfirst($factura->cita->paciente->last_name);
        })
        ->addColumn('dni', function ($factura) {
           return $factura->dni = $factura->cita->paciente->dni;
        })
        ->addColumn('total_abonado', function ($factura) {
            return $factura->total_abonado = $factura->abonos->sum('abonado');
        })
        ->addColumn('valor_factura', function ($factura) {
            return $factura->valor_factura = $factura->cita->procedimientos->sum('price');
            })
        ->addColumn('btn', function ($facturas) {
            return '<a href="' . route('generar-pago', $facturas->id) . '" class="btn btn-sm btn-success" title="Registrar pago"><i class="fa fa-money"></i></a>';
        })
        ->rawColumns(['btn'])
        ->toJson();
});
        
