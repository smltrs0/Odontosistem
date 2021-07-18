@extends('layouts.app')

@section('content')
    <div class="">
        <div class="card">
            <div class="card-header">Abonos realizados
                <div class="float-right">
                    <a href="{{ route('registrar-pago')}}" class="btn btn-sm btn-success">Registrar abono</a>
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
                         <label for="">Buscar</label>
                         <button class="btn form-control btn-info">Buscar <i class="fa fa-search"></i></button>
                     </div>
                 </div>
              </div>
                <div class="container">
                    <table class="table table-sm">
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
                                <td>{{ $abono->factura_id }}</td>
                                <td>{{ $abono->abonado }}</td>
                                <td>{{ $abono->factura_id }} <span class="badge badge-success">Pagado</span></td>
                                <td>
                                    <a href="{{ route('detalle-pago',$abono->id) }}" class="btn btn-sm btn-primary" title="Ver detalles del pago"><i class="fa fa-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-primary" title="Imprimir factura con los pagos correspondientes"><i class="fa fa-print"></i></a>
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

