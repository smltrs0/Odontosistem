<?php

namespace App\Http\Controllers;

use App\Citas;
use Illuminate\Http\Request;

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
    public function index()
    {
        $date = now()->format('Y-m-d');
//
        $citas= Citas::select('citas.fecha', 'citas.hora', 'citas.id as id_cita', 'pacientes.id as id_paciente', 'citas.atendido', 'pacientes.name', 'pacientes.last_name' )
            ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')->where('citas.fecha', '=', $date)->orderBy('citas.created_at', 'ASC')->get();
        return view('home', compact('citas'));
    }
}
