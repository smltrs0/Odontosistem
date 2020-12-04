<?php
namespace App\Http\Controllers;

use App\citas_medicas;
use App\Pacientes;
use Illuminate\Http\Request;
use PDF;

class PDFController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePDF(citas_medicas $id)
    {
        $cita = $id;
        $paciente= Pacientes::find($cita->pacientes_id);

        $pdf = PDF::loadView('myPDF', array('cita'=> $cita, 'paciente'=> $paciente));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream("dompdf_out.pdf", array("Attachment" => false));


    }
}
