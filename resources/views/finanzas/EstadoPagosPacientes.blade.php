@extends('layouts.app')

@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Pagos registrados
                <div class="float-right">
                    <button class="btn-shadow btn btn-outline-danger"><i class="fa fa-print"></i> Imprimir</button>
                </div>
            </div>
            <ul class="nav nav-pills mb-3 mt-1 mx-auto" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#todos-los-pagos" role="tab"
                       aria-controls="pills-home" aria-selected="true">Todos los pacientes</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="pills-profile-tab" data-toggle="pill" href="#pagos-pendientes" role="tab"
                       aria-controls="pills-profile" aria-selected="false">Pacientes con pagos pendientes</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="pills-contact-tab" data-toggle="pill" href="#pills-contact" role="tab"
                       aria-controls="pills-contact" aria-selected="false">Pacientes con pagos cancelados</a>
                </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="todos-los-pagos" role="tabpanel"
                     aria-labelledby="pills-home-tab">
                  <div class="mx-auto">
                     <div class="row container">
                        <div class="col-6">
                            <label for="">Desde:</label>
                            <input class="form-control" type="date">
                        </div>
                         <div class="col-6">
                             <label for="">Hasta:</label>
                             <input class="form-control" type="date">
                         </div>
                     </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="pagos-pendientes" role="tabpanel" aria-labelledby="pills-profile-tab">
                    pagos pendientes
                </div>
                <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
                    pagos cancelados
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Nombre del paciente</td>
                        <td>Fecha ultimo pago</td>
                        <td>Estado</td>
                        <td>Acción</td>
                    </tr>
                    <tr>
                        <td>Samuel Trias</td>
                        <td>17/09/2020</td>
                        <td><span class="badge badge-success">Pagado</span></td>
                        <td><a href="#" class="btn btn-sm btn-primary">Actualizar estado</a></td>
                    </tr>
                    <tr>
                        <td>Samuel Trias</td>
                        <td>17/09/2020</td>
                        <td><span class="badge badge-warning">Pagos pendientes</span></td>
                        <td><a href="#" class="btn btn-sm btn-primary">Actualizar estado</a></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

@endsection

