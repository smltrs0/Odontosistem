<?php

namespace App\Http\Controllers;

use App\Citas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminCitasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        Gate::authorize('haveaccess','citas.view');
        $citas = Citas::paginate(15);

        return view('calendar.index', compact('citas'));
    }

    public function create(){
        //
    }

    public function store(Request $request){
        $request->validate([
            'fecha' => 'required',
        ]);
        $cita= new Citas();
        $cita->fecha=$request->fecha;
        $cita->user_id=auth()->user()->id;
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
            'user_id'=>'auth()->user()->id',

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
