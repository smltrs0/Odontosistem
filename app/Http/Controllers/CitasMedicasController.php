<?php

namespace App\Http\Controllers;

use App\citas_medicas;
use Illuminate\Http\Request;
use App\Pacientes;
use App\procedure;
use Illuminate\Support\Facades\DB;

class CitasMedicasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return 'coming';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
        $cita_medica = [
            'evaluacion' => $request->evaluacion,
            'medicacion' => $request->medicacion,
            'analisis_solicitados'  => $request->analisis,
            'comentario_paciente'  => $request->comentario_paciente,
            'comentario_doctor'  => $request->comentario_medico,
            'pacientes_id'  => $request->paciente_id,
        ];

        $id_cita_medica = citas_medicas::insertGetId($cita_medica);

        $data = array();
        foreach ($request->procedimientos as $procedimiento_id) {
            array_push($data, [
                'procedure_id' => $procedimiento_id,
                'citas_medicas_id' => $id_cita_medica,
                'cantidad' => 1 // Falta capturar la cantidad
            ]);
        }

        $cita_medica= citas_medicas::find($id_cita_medica);
        $cita_medica->procedimientos()->sync($data);
        // Falta capturar los datos en la visual que el cambio se realizo correctamete
        return redirect("citas-medicas/".$request->paciente_id)->with('success', 'Datos actualizados correctamente');
       
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\citas_medicas  $citas_medicas
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $procedimientos = procedure::all();
        $paciente = Pacientes::find($id);
        return view('citas-medicas.citas_medicas', \compact('paciente', 'procedimientos'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\citas_medicas  $citas_medicas
     * @return \Illuminate\Http\Response
     */
    public function edit(citas_medicas $citas_medicas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\citas_medicas  $citas_medicas
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, citas_medicas $citas_medicas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\citas_medicas  $citas_medicas
     * @return \Illuminate\Http\Response
     */
    public function destroy(citas_medicas $citas_medicas)
    {
        //
    }
}
