@extends('layouts.app')

@section('content')
    <div class="">
        <div class="card">
            <div class="card-header">Pagos registrados
                <div class="float-right">
                    <button class="btn-shadow btn btn-outline-danger"><i class="fa fa-money-bill-wave-alt"></i> Metodos de pago</button>
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
                             <th scope="col"> Fecha ultima actualización</th>
                             <th scope="col"> Monto</th>
                             <th scope="col"> Estado</th>
                             <th scope="col"> Acción</th>
                        </thead>
                        <tr>
                            <td>Samuel Trias</td>
                            <td>17/09/2020</td>
                            <td>55</td>
                            <td><span class="badge badge-success">Pagado</span></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary" title="Actualizar estado"><i class="fa fa-edit"></i></a>
                                <a href="#" class="btn btn-sm btn-primary" title="Ver factura"><i class="fa fa-eye"></i></a>
                                <a href="#" class="btn btn-sm btn-primary" title="Imprimir factura"><i class="fa fa-print"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>Samuel Trias</td>
                            <td>17/09/2020</td>
                            <td>55</td>
                            <td><span class="badge badge-warning">Pagos pendientes</span></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary" title="Actualizar estado"><i class="fa fa-edit"></i></a>
                                <a href="#" class="btn btn-sm btn-primary" title="Ver factura"><i class="fa fa-eye"></i></a>
                                <a href="#" class="btn btn-sm btn-primary" title="Imprimir factura"><i class="fa fa-print"></i></a>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                   LINKS
                </div>
            </div>
            
        </div>
    </div>

@endsection

