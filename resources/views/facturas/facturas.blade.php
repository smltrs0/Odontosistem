@extends('layouts.app')

@section('content')
<div class="mb-3">
        <div class="card">
            <div class="card-header">Facturas</div>
                <div class="card-body">
                    <table class="table table-light table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>N° Factura</th>
                                <th>Paciente</th>
                                <th>Fecha</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facturas as $index => $factura)
                                <tr>
                                    <td>{{ $index+1 }}</td>
                                    <td>{{ $factura->id }} </td>
                                    <td>{{ ucfirst($factura->cita->paciente->name)." ".ucfirst($factura->cita->paciente->last_name) }}</td>
                                    <td>{{ $factura->created_at }}</td>
                                    <td>
                                        <a href=""><span class="fa fa-eye"></span></a>
                                        <a href=""><span class="fa fa-edit"></span></a>
                                        <a target="_blank" href="./generar-factura/{{$factura->cita->id}}"><span class="fa fa-print"></span></a>
                                    </td>
                                    
                                </tr>
                                @endforeach
                        </tbody>
                    </table>
                    <div class="card-footer">
                        {{ $facturas->links() }}   
                    </div>
                </div>
        </div>
</div>

@endsection

