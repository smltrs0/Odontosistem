@extends('layouts.app')

@section('content')
    <div class="">
        <div class="card">
            <div class="card-header">Cuentas por cobrar
                <div class="float-right">
                    <a href="#" class="btn btn-sm btn-success">Registrar abono</a>
                    <button class="btn-shadow btn btn-outline-danger btn-sm"><i class="fa fa-money-bill-wave-alt"></i> Metodos de pago</button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row container">
                    <div class="col-4">
                        <label for="">Desde:</label>
                        <input class="form-control" type="date">
                    </div>
                     <div class="col-4">
                         <label for="">Hasta:</label>
                         <input class="form-control" type="date">
                     </div>
                     <div class="col-4">
                        <label for="">Estados:</label>
                        <select name="" class="form-control">
                            <option value="">Todos</option>
                            <option value="">Pagados</option>
                            <option value="">Pendientes</option>
                        </select>
                     </div>
                 </div>
              </div>
                <div class="container">
                    <table class="table">
                        <thead class="thead-dark">
                             <th scope="col"> D.N.I</th>
                             <th scope="col"> Factura ID</th>
                             <th scope="col"> Abonado</th>
                             <th scope="col"> Saldo</th>
                             <th scope="col"> Acción</th>
                        </thead>
                        @foreach ($abonos as $abono)
                        <tr>
                            <td>{{ $abono->paciente->dni }}</td>
                            <td>{{ $abono->factura->id }}</td>
                            <td>{{ $abono->abonado }}</td>
                            <td>{{ $abono->factura->total_neto }} <span class="badge badge-success">Pagado</span></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary" title="Actualizar estado"><i class="fa fa-edit"></i></a>
                                <a href="#" class="btn btn-sm btn-primary" title="Ver factura"><i class="fa fa-eye"></i></a>
                                <a href="#" class="btn btn-sm btn-primary" title="Imprimir factura"><i class="fa fa-print"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
                <div class="card-footer">
                  {{ $abonos->links() }}
                </div>
            </div>
            
        </div>
    </div>

@endsection

