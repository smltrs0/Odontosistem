@extends('layouts.app')

@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Todas las citas</div>
            <div class="card-body">
                    <table class="table">
                        <tr>
                                <th width="50%">Nombre</th>
                                <th width="30%">Estado</th>
                                <th width="20%">accion</th>
                            </tr>


                            @foreach($citas as $cita)
                            <tr>
                                <td>
                                   {{ $fechaCita = date( 'd-m-Y' ,strtotime($cita->fecha)) }}
                                </td>
                                <td>
                                    <a href="#" title="Ver cita cita">Atendido </a>
                                    @if(date('d-m-Y',strtotime(now())) == $fechaCita)
                                        <span class="badge badge-primary">
                                            Hoy
                                        </span>
                                    @endif()
                                </td>
                                   <td>
                                       <a class="align-content-end" href="{{route('pacientes.show',$cita->paciente_id)}}">Ver paciente</a>
                                   </td>
                            </tr>
                        @endforeach
                    </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                {{ $citas->links() }}
            </div>
    </div>

@endsection


