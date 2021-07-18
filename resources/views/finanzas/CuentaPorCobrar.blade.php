@extends('layouts.app')
@section('content')
<div class="">
    <div class="card">
        <div class="card-header">Cuenta por cobrar
            <div class="float-right">
                <a href="{{ route('registrar-pago')}}" class="btn btn-sm btn-success">Registrar abono</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="dl-horizontal">
                        <dt>Número de factura</dt>
                        <dd>{{ $factura->id }}</dd>
                        <dt>Fecha de la factura</dt>
                        <dd>{{ $factura->created_at }}</dd>
                        <dt>Total neto factura</dt>
                        <dd>{{ $factura->total_procedimientos }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="dl-horizontal">
                        <dt>Nombre del paciente</dt>
                        <dd>{{ ucfirst($factura->cita->paciente->name)." ".ucfirst($factura->cita->paciente->last_name) }}</dd>
                        <dt>Estado</dt>
                        <dd class="<?= ($factura->total_procedimientos - $factura->total_abonado <= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($factura->total_procedimientos - $factura->total_abonado <= 0) ? 'Pagado' : 'Pendiente pagos' ?>
                        </dd>
                        <dt>Cantidad adeudad</dt>
                        <dd>{{ $factura->total_procedimientos - $factura->total_abonado }}</dd>
                    </dl>
                </div>
            </div>
            <label>Abonos</label>
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cantidad abonada</th>
                        <th>Referencia</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($factura->abonos as $numero => $abono)
                    <tr>
                        <td>{{ $numero+1 }}</td>
                        <td>{{ $abono->created_at }}</td>
                        <td>{{ $abono->abonado }}</td>
                        <td>{{ ($abono->referencia == null ? 'Ninguna': $abono->referencia) }}</td>
                        <td><a href="" class="btn btn-light bnt-sm"> <i class="fa fa-eye"></i></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection