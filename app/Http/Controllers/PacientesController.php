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
            'paciente' => new Pacientes
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

        ]);

        Pacientes::create($request->all());

        return redirect()->route('pacientes.create')
            ->with('success', 'Paciente creado correctamente.');
    }

    public function show(Pacientes $paciente)
    {

        //solicitamos los datos de este paciente
        return view('pacientes.ver', compact('paciente'));
    }

    public function edit(Pacientes $paciente)
    {
        return view('pacientes.editar', compact('paciente'));
    }


    public function update(Request $request,Pacientes $paciente)
    {

        $validatedData = $request->validate([
            'name' => 'required',
            'last_name' => 'required',
            'dni' => 'required',
            'birth_date' => 'required',
            'sex' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'email'=> 'required'
        ]);

             $paciente->update([
                'name' => $request->get('name'),
                'second_name' => $request->get('second_name'),
                'last_name' => $request->get('last_name'),
                'second_last_name' => $request->get('second_last_name'),
                'phone' => $request->get('phone'),
                'address' => $request->get('address'),
                'sex' => $request->get('sex'),
                'dni' => $request->get('dni'),
                'birth_date' => $request->get('birth_date'),
                'registered_by' => auth()->user()->id,
                'id' => $paciente,
            ]);

            if ($paciente->user_id == !null){
                $user = User::find($paciente->user_id);
                $user->update([
                    'email' => $request->get('email')
                        ]);
            }

//        $newPaciente = Pacientes::updateOrCreate(['id' => $paciente],
//            ['name' => $request->get('name'), 'second_name' => $request->get('second_name'), 'last_name' => $request->get('last_name'), 'second_last_name' => $request->get('second_last_name'), 'phone' => $request->get('phone'), 'address' => $request->get('address'), 'sex' => $request->get('sex'), 'dni' => $request->get('dni'), 'birth_date' => $request->get('birth_date'), 'registered_by' => auth()->user()->id,]);
        return redirect()->route('pacientes.index')
            ->with('success', 'Datos actualizados correctamente');
    }


    public function destroy(Pacientes $paciente)
    {
        $paciente->delete();
        return redirect()->route('pacientes.index')
            ->with('success', 'Paciente eliminado correctamente');
    }

}
