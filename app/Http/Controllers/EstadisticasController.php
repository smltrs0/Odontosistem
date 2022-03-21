<?php

namespace App\Http\Controllers;

use App\Abonos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadisticasController extends Controller
{
    
    public function EstadisticasPacientes(Request $request){
        // dd($request->year);
        $meses = [
            0 => 'Enero',
            1 => 'Febrero',
            2 => 'Marzo',
            3 => 'Abril',
            4 => 'Mayo',
            5 => 'Junio',
            6 => 'Julio',
            7 => 'Agosto',
            8 => 'Septiembre',
            9 => 'Octubre',
            10 => 'Noviembre',
            11 => 'Diciembre',
            12 => 'Diciembre'
        ];

        if(is_null($request->year) || $request->year == 0) $fecha = date("Y");
        else $fecha = $request->year;

        
        $ingresos = DB::table('abonos')
        ->whereRaw("DATE_FORMAT(abonos.created_at,'%Y') = $fecha")
        ->select(
            DB::raw('sum(abonos.abonado) as sums'),
            DB::raw("DATE_FORMAT(abonos.created_at,'%M') as months")
        )
        ->groupBy('months')
        ->get()
        ->map(function($item, $key) use ($meses) {
            $item->mes = $meses[date_parse($item->months)['month']];
            return $item;
        })
        // ->toArray()
        ;
        // dd($ingresos);
        return view('finanzas.Estadisticas', compact('ingresos'));
    }
  

}
