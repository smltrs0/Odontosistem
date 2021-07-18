<?php
namespace App\Http\Controllers;

use PDF;
use App\Abonos;
use App\Facturas;
use App\Pacientes;
use App\citas_medicas;
use Illuminate\Http\Request;

class PDFController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePDF(citas_medicas $id){
        $cita = $id;
        $numero_de_factura = Facturas::where('cita_medica_id', $cita->id)->first()->id;
        $abonado = Abonos::where('factura_id', $cita->id)->get()->sum('abonado');
        $paciente = Pacientes::find($cita->pacientes_id);
        $pdf = PDF::loadView('Factura_evaluacion', array('cita'=> $cita, 'paciente'=> $paciente, 'abonado'=> $abonado, 'numero_de_factura' => $numero_de_factura));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream("dompdf_out.pdf", array("Attachment" => true));
    }

    public function registrarPagoCreacionFactura(Request $request){
        
        $paciente_id = citas_medicas::where('id', $request->cita_id)->select('pacientes_id')->first()->pacientes_id;

        if(Facturas::where('cita_medica_id',$request->cita_id)->count() > 0){
           $message = [ 
            'res' =>'error', 
            'msg' =>'Ya existe una factura generada para esta cita'
        ];
            echo json_encode(compact('message'));
            die;
        }

        $factura = new Facturas();
        $factura->cita_medica_id = $request->cita_id;
        $factura->abono_creacion = $request->cantidad;
        $factura->save();
        
        $abono = new Abonos();
        $abono->paciente_id = $paciente_id;
        $abono->abonado = $request->cantidad;
        $abono->factura_id = $request->cita_id;
        $abono->save();

       

        return redirect('generar-factura/'.$request->cita_id);
    }
}
