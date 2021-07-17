<?php

namespace App\Http\Controllers;

use App\Citas;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
   
    public function confirmar($cita){
        $cita = Citas::find($cita);
        $cita->asistencia_confirmada = 1;
        $cita->save();

        return redirect()->route('citas-hoy.index')->with('success', 'Cita confirmada exitosamente.');

    }
    
    public function cancelar($cita){

        $cita = Citas::find($cita);
        $cita->asistencia_confirmada = 0;
        $cita->save();
        return redirect()->route('citas-hoy.index')->with('success', 'Cita cancelada exitosamente.');
        
    }

}
