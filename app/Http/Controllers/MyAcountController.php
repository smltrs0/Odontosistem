<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Pacientes;
use App\User;

class MyAcountController extends Controller
{
    function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        $paciente = User::find(auth()->user()->id);
        return view('auth.acount', compact('paciente'));
    }

    public function update(Request $request,  $paciente){

            $request->validate([
            'name' => 'required',
            'last_name' => 'required',
            'dni' => 'required',
            'birth_date' => 'required',
            'sex' => 'required',
            'phone' => 'required',
            'address' => 'required',
                ]);

                Pacientes::updateOrCreate(['user_id' => $paciente],
                ['name' => $request->get('name'),
                'second_name' => $request->get('second_name'),
                'last_name' => $request->get('last_name'),
                'second_last_name' => $request->get('second_last_name'),
                'phone' => $request->get('phone'),
                'address' => $request->get('address'),
                'sex' => $request->get('sex'),
                'dni' => $request->get('dni'),
                'birth_date' => $request->get('birth_date'),
                'registered_by' => auth()->user()->id,]);




        return redirect()->route('mi-cuenta.index')
            ->with('success', 'Datos actualizados correctamente');
    }



}
