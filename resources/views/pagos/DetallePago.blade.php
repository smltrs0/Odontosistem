@extends('layouts.app')
@section('content')
<div class="mb-3">
    <div class="card">
        <div class="card-header">Abono correspondiente a la factura #{{ $abono->factura_id }}</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label>Datos del paciente</label>
                    <p><b>Nombre: </b>{{ ucfirst($abono->paciente->name) . " " .ucfirst($abono->paciente->last_name) }}</p>
                    <p><b>DNI: </b>{{ $abono->paciente->dni }}</p>
                    <p><b>Dirección: </b>{{ $abono->paciente->address }}</p>
                </div>
                <div class="col-md-6">
                    <label>Detalles de la factura</label>
                    <p><b>Fecha creación: </b>{{ $abono->created_at }}</p>
                    <p><b>Número de la factura:</b>{{ $abono->factura_id }} <a href="{{ route('cuenta-por-cobrar', $factura->id) }}" title="Ver detalles factura"> <i class="fa fa-eye"></i></a></p>
                    <p><b>Valor total de la factura: </b>{{ $abono->total_factura }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label>Detalles del abono</label>
                    <p><b>Fecha: </b>{{ $abono->created_at }}</p>
                    <p><b>Monto abonado: </b> {{ $abono->abonado }}</p>
                    <p><b>Referencia: </b> {{ $abono->referencia }}</p>
                    <p><b>Nota: </b> {{ ($abono->nota == '' || $abono->nota == null ? 'Ninguna nota agregada': $abono->nota) }}</p>
                    <p><b>Adjunto: </b> 
                    @if($abono->adjunto != null)
                        <a href="{{ $abono->adjunto }}" download>Ver archivo adjuntado</a>
                    </p>
                    @else
                        No se adjunto ningún archivo
                    @endif
                
                </div>
            </div>
        </div>
    </div>

    @endsection