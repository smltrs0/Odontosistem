@extends('layouts.app')

@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Todas las citas</div>
            <div class="card-body">
                    <table class="table">
                        <tr>
                                <th width="">Nombre</th>
                                <th> Fecha y hora</th>
                                <th width="30%">Estado</th>
                                <th width="20%">accion</th>
                            </tr>


                            @foreach($citas as $cita)
                            <tr>
                               <td>{{ Str::ucfirst( $cita->paciente->name)." ".Str::ucfirst($cita->paciente->last_name) }}</td>
                                <td>
                                   {{ date( 'd-m-Y' ,strtotime($cita->fecha)). " " .$cita->hora }}
                                </td>
                                <td>
                                    @if ($cita->atendido)
                                        <a href="#" title="Ver cita cita">Atendido</a>
                                    @endif
                                    @if ($cita->asistencia_confirmada == '1')
                                    <span class="badge badge-primary">
                                        Asistencia confirmada
                                    </span>
                                    @endif
                                    @if(date('d-m-Y',strtotime(now())) == date( 'd-m-Y' ,strtotime($cita->fecha)))
                                        <span class="badge badge-primary">
                                            Hoy
                                        </span>
                                    @endif()
                                </td>
                                   <td>
                                       <a href="{{ route('confirmar-asistencia', $cita->id)  }}" class="btn btn-sm btn-light" title="Confirmar asistencia"><i class="fa fa-check text-success"></i></a>
                                       <a href="{{ route('cancelar-asistencia', $cita->id)  }}" class="btn btn-sm btn-light" title="Cancelar asistencia"><i class="fa fa-times text-danger"></i></a>
                                       <a class="align-content-end btn btn-sm btn-light" title="Ver paciente" href="{{route('pacientes.show',$cita->paciente_id)}}"><i class="fa fa-eye"></i></a>
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


