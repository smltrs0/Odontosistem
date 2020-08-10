<?php

namespace App\Http\Controllers;

use App\Pacientes;
use App\User;
use Illuminate\Http\Request;

class PacientesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        //
        $pacientes = Pacientes::latest()->paginate(10);

        return view('pacientes.index', compact('pacientes'))->with('i', (request()->input('page', 1) - 1) * 5);
    }

    public function create()
    {
        return view('pacientes.agregar', [
            'paciente' => new Pacientes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'registered_by' => 'required',
            'name' => 'required',
            'last_name' => 'required',
            'dni' => 'required |unique:pacientes,dni',
            'address' => 'required',
            'phone' => 'required',
            'email' => 'required',
        ]);

        Pacientes::create($request->all());

        return redirect()->route('pacientes.create')
            ->with('success', 'Paciente creado correctamente.');
    }

    public function show(Pacientes $paciente)
    {
        return view('pacientes.ver', compact('paciente'));
    }

    public function edit(Pacientes $paciente)
    {
        return view('pacientes.editar', compact('paciente'));
    }


    public function update(Request $request, Pacientes $paciente)
    {

         $request->validate([
            'name' => 'required',
            'last_name' => 'required',
            'dni' => 'required',
            'birth_date' => 'required',
            'sex' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'email' => 'required',
        ]);

        $paciente->update([
            'id' => $paciente,
            'name' => $request->get('name'),
            'second_name' => $request->get('second_name'),
            'last_name' => $request->get('last_name'),
            'second_last_name' => $request->get('second_last_name'),
            'phone' => $request->get('phone'),
            'address' => $request->get('address'),
            'email' => $request->get('email'),
            'sex' => $request->get('sex'),
            'dni' => $request->get('dni'),
            'birth_date' => $request->get('birth_date'),
            'registered_by' => auth()->user()->id,
            'procedures'=> $request->get('procedures'),
            'otros'=> $request->get('otros'),
            "embarazada" => $request->get('embarazada'),
            "coagulacion" => $request->get('coagulacion'),
            "anestesicos" => $request->get('anestesicos'),
            'antecedentes' => json_encode(explode(',', $request->antecedentes)),
            'habitos' => json_encode(explode(',', $request->habitos)),
            'alergias'=> json_encode(explode(',', $request->alergias)),
        ]);

        return redirect()->route('pacientes.index')->with('success', 'Datos actualizados correctamente');

    }


    public function destroy(Pacientes $paciente)
    {
        $paciente->delete();
        return redirect()->route('pacientes.index')
            ->with('success', 'Paciente eliminado correctamente');
    }

}
