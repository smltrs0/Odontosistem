@extends('layouts.app')

@section('content')
<div class="mb-3">
        <div class="card">
            <div class="card-header">Facturas</div>
                <div class="card-body">
                    <table class="table table-light">
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
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>
                                    <a href=""><span class="fa fa-eye"></span></a>
                                    <a href=""><span class="fa fa-edit"></span></a>
                                    <a href=""><span class="fa fa-print"></span></a>
                                </td>
                                
                            </tr>
                        </tbody>
                    </table>
                </div>
        </div>
</div>

@endsection

