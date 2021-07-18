@extends('layouts.app')

@section('content')
    <div class="">
        <div class="card">
            <div class="card-header">Cuentas por cobrar
                <div class="float-right">
                    <a href="{{ route('registrar-pago')}}" class="btn btn-sm btn-success">Registrar abono</a>
                </div>
            </div>
            
            <div class="card-body">
                <div class="container">
                    <table class="table table-sm">
                        <thead class="thead-dark">
                             <th scope="col"> D.N.I</th>
                             <th scope="col"> Factura ID</th>
                             <th scope="col"> Abonado</th>
                             <th scope="col"> Valor facturado</th>
                             <th scope="col"> Valor adeudado</th>
                             <th scope="col"> Acción</th>
                        </thead>
                        <tbody>
                            @foreach($facturas as $factura)
                            <tr class="<?= ($factura->total_procedimientos - $factura->total_abonado <= 0) ? 'table-success' : 'table-danger' ?>">
                                <td>{{ $factura->cita->paciente->dni }}</td>
                                <td>{{ $factura->id }}</td>
                                <td>{{ $factura->total_abonado }}</td>
                                <td>{{ $factura->total_procedimientos }}</td>
                                <td>{{ $factura->total_procedimientos - $factura->total_abonado }}</td>
                                <td> <a href="{{ route('cuenta-por-cobrar', ['id' => $factura->id]) }}" class="btn btn-sm btn-light"><i class="fa fa-eye"></i></a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $facturas->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection

