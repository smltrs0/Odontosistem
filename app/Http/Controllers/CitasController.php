<?php

namespace App\Http\Controllers;

use App\User;
use App\Citas;
use App\Pacientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CitasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        
        $paciente_id =  DB::table('pacientes')->select('id')->where('user_id', '=', auth()->user()->id)->get();
        $citas='';
        $usuarios = User::all();
        

        if (!isset($paciente_id[0]->id)){
            // Esto es para saber si el usuario tiene un perfil de paciente creado
           $existe = false;
        }else{
            $existe= true;
            $citas = DB::table('citas')->where('paciente_id', '=', $paciente_id[0]->id)->get();
        }

        return view('calendar.citas', compact('citas', 'existe', 'usuarios'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request){

        if(isset($request->usuario) && !is_null($request->usuario) && $request->usuario != ''){
            $paciente_id = $request->usuario;
        }else{
            $paciente =  DB::table('pacientes')->select('id')->where('user_id', '=', auth()->user()->id)->first();
            $paciente_id = $paciente->id;
        }

        $request->validate([
            'fecha' => 'required',
            'hora'=>'required',
        ]);


        $cita= new Citas();
        $cita->fecha=$request->fecha;
        $cita->hora=$request->hora;
        $cita->paciente_id=$paciente_id;

        if ($cita->save()){
            return redirect()->route('citas.index')->with('success', 'La cita se ha creado correctamente.');
        }

    }

    public function show(Citas $citas)
    {
        //
    }


    public function edit($id)
    {
        $citas = Citas::findOrFail($id);
        return view('calendar.edit', compact('citas'));
    }


    public function update(Request $request, $citas)
    {
        $datosValidados = $request->validate([
            'fecha' => 'required',
            'hora'=> 'required',
            'paciente_id'=>'auth()->user()->id',

        ]);
        Citas::whereId($citas)->update($datosValidados);
        return redirect()->route('citas.index')->with('success','La cita se ha actualizado correctamente');
    }

    public function destroy($id)
    {
        $citas = Citas::findOrFail($id);
        $citas->delete();

        return redirect('/citas')->with('success', 'La cita se ha eliminado correctamente');
    }
}
