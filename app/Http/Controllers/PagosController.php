<?php

namespace App\Http\Controllers;

use App\Abonos;
use App\Facturas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PagosController extends Controller{
    public function detallersAbono($id){
        $abono = Abonos::where('id', $id)->first();
        $factura = Facturas::where('cita_medica_id', $abono->factura_id)->first();
        $abono->total_factura = $factura->cita->procedimientos->sum('price');

        
        if(!is_null($abono->adjunto) && !empty($abono->adjunto)) $abono->adjunto = Storage::url($abono->adjunto);
        // Storage::url($abono->adjunto);
        
        return view('pagos.DetallePago', compact('abono', 'factura'));
    }
}
