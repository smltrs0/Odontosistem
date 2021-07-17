<?php

namespace App\Http\Controllers;

use App\Citas;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(){

        $numero_de_citas = Citas::count();
        $citas_canceladas = Citas::where('asistencia_confirmada', '=', 0)->count();
        $citas_confirmada = Citas::where('asistencia_confirmada', '=', 1)->count();
        $citas_sin_confirmar = Citas::where('asistencia_confirmada', '=', null)->count();

        $paciente_usuario =  DB::table('pacientes')->select('id')->where('user_id', '=', auth()->user()->id)->get();

        $date = now()->format('Y-m-d');
//
        $citas= Citas::select('citas.fecha', 'citas.hora', 'citas.id as id_cita', 'pacientes.id as id_paciente', 'citas.atendido', 'pacientes.name', 'pacientes.last_name' )
            ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
            ->where('citas.fecha', '=', $date)->orderBy('citas.created_at', 'ASC')->get();
        return view('home', compact('citas', 'paciente_usuario', 'numero_de_citas', 'citas_canceladas', 'citas_confirmada', 'citas_sin_confirmar'));
    }
}
