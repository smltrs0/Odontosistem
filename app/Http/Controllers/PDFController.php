<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;

class PDFController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePDF($request='')
    {
        $data = ['title' => 'Titulo para el pdf de ejemplo'];
        $pdf = PDF::loadView('myPDF', $data);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream("dompdf_out.pdf", array("Attachment" => false));


    }
}
