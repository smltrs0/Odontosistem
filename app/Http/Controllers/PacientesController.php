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
        // Instanciamos y pasamos un paciente para que no de error al reutilizar el formulario
        return view('pacientes.agregar', [
            'paciente' => new Pacientes
        ]);
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'registered_by'=> 'required',
        //     'name' => 'required',
        //     'last_name' => 'required',
        //     'dni' => 'required |unique:pacientes,dni',
        //     'address'=> 'required',
        //     'phone' => 'required',

        // ]);

        Pacientes::create($request->all());

        return redirect()->route('pacientes.create')
                        ->with('success', 'Paciente creado correctamente.');
    }

    public function show($id)
    {
        //solicitamos los datos de este paciente
        $paciente = Pacientes::findOrFail($id);
        //return dd($paciente->user); // Paciente contiene los datos del usuario al cual pertenece
    
        if ($paciente) {
            echo json_encode($paciente);
        } else {
            return 'no ningún usuario asignado';
        }
    }

    public function edit(Pacientes $paciente)
    {
        return view('pacientes.editar', compact('paciente'));
    }


    public function update(Request $request, Pacientes $paciente)
    {
        // Validamos los datos
        $validatedData = $request->validate([
            'name' => 'required',
            'second_last_name' => 'required',
            'dni' => 'required',
            'birth_date' => 'required',
            'sex' => 'required',
            'phone' => 'required',
            'address' => 'required',
        ]);
        
        if ($paciente->id == auth()->user()->id) {
            $id_profile = array('user_id'=>auth()->user()->id);
        // Estas editando tu perfil
        } else {
            $id_profile= '';
            //Estas editando otro perfil
        }
       
        $newPaciente = Pacientes::updateOrCreate([
            // Añadimos un elemento único que buscara si concuerda si no se creara uno
            //Por ejemplo, el dni que solo lo puede tener un usuario...
            //'dni' => $request->get('dni'),
            'id'=> $paciente->id,

        ], [
            'name'     => $request->get('name'),
            'second_name' => $request->get('second_name'),
            'last_name'    => $request->get('last_name'),
            'second_last_name'   => $request->get('second_last_name'),
            'phone'       => $request->get('phone'),
            'address'   => $request->get('address'),
            'sex'    => $request->get('sex'),
            'dni'    => $request->get('dni'),
            'birth_date' => $request->get('birth_date'),
            'registered_by'=> auth()->user()->id,
            $id_profile,
            // Aquí se puede continuar si se le agregan mas campos al formulario
            ]);
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
