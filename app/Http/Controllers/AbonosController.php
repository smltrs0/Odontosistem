<?php

namespace App\Http\Controllers;

use App\Abonos;
use App\Facturas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AbonosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){     

        $abonos = Abonos::orderBy('created_at', 'desc')->paginate(10);
        return view('finanzas.CuentasPorCobrar', compact('abonos'));
    }


    public function cuentasPorCobrar(){
        $facturas = Facturas::paginate(10);
        $facturas->transform(function($factura){
                $factura->total_abonado = $factura->abonos->sum('abonado');
                $factura->total_procedimientos = $factura->cita->procedimientos->sum('price');
            return $factura;
        });

        return view('finanzas.cuentasPendientes', compact('facturas'));
    }

    public function registrarPago($id_cita_medica){

        $factura_id = Facturas::where('id', $id_cita_medica)->first()->id;
        return view('pagos.RegistrarPago', compact('factura_id'));
    }
    
    public function registrarPagoFactura(Request $request, $id){
        
    //    dd($request->file('adjunto'));
        $factura = Facturas::findOrFail($id);
        $paciente_id = $factura->cita->paciente->id;
        $request->validate([
                'monto_abonado' => 'required|numeric',
                'metodo_pago' => 'required',
            ]);

        $abono = new Abonos();
        $abono->factura_id = $factura->cita_medica_id;
        $abono->paciente_id = $paciente_id;
        $abono->abonado = $request->monto_abonado;
        $abono->referencia = $request->referencia;
        $abono->methos_pay_id = $request->metodo_pago;

        if($request->hasFile('adjunto')){
            $abono->adjunto = $request->file('adjunto')->store('facturas-adjuntos', 'public');
        }
        $abono->save();

        return redirect()->route('generar-pago', $id)->with('success', 'Pago registrado exitosamente');
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id){
        $factura = Facturas::where('id', $id)->first();
        $factura->total_abonado = $factura->abonos->sum('abonado');
        $factura->total_procedimientos = $factura->cita->procedimientos->sum('price');
        return view('finanzas.CuentaPorCobrar', compact('factura'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        //
    }
}
