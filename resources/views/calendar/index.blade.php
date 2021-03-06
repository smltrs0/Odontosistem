@extends('layouts.app')

@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Todas las citas</div>
            <div class="card-body">
                    <table class="table">
                        <tr>
                                <th width="">Nombre</th>
                                <th> Fecha</th>
                                <th width="30%">Estado</th>
                                <th width="20%">accion</th>
                            </tr>


                            @foreach($citas as $cita)
                            <tr>
                                <td>{{ Str::ucfirst( $cita->paciente->name)." ".Str::ucfirst($cita->paciente->last_name) }}</td>
                                <td>
                                   {{ $fechaCita = date( 'd-m-Y' ,strtotime($cita->fecha)) }}
                                </td>
                                <td>
                                    @if ($cita->atendido)
                                    <a href="#" title="Ver cita cita">Atendido </a>
                                    @endif
                                    @if ($cita->asistencia_confirmada)
                                    <span class="badge badge-primary">
                                        Asistencia confirmada
                                    </span>
                                    @endif
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


