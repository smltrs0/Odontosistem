@extends('layouts.app')
@section('content')
<div class="mb-3">
    <div class="card">
        <div class="card-header">Registrar abono correspondiente a la factura #{{ $factura_id}}</div>
        <div class="card-body">
            <form method="POST" action="{{route('registrar-pago-factura', $factura_id )}}" accept-charset="UTF-8" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="monto_abonado">Monto abonado</label>
                    <input id="monto_abonado" class="form-control" type="text" name="monto_abonado">
                </div>
                <div class="form-group">
                    <label for="method_pay">Método de pago</label>
                    <select id="method_pay" class="form-control" name="metodo_pago">
                        <option value="" selected disabled>-Seleccione-</option>
                        <option value="1">Efectivo</option>
                        <option value="2">Transferencia</option>
                        <option value="3">Pago movil</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="referencia">Referencia</label>
                    <input id="referencia" class="form-control" type="text" name="referencia">
                </div>
                <div class="form-group">
                    <label for="nota">Nota</label>
                    <textarea class="form-control" id="nota" name="nota" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="adjunto">Adjunto</label>
                    <input id="adjunto" class="form-control-file" type="file" name="adjunto">
                </div>
                <button type="submit" class="btn btn-primary">Registrar abono</button>
            </form>

        </div>
    </div>

    @endsection