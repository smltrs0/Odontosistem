@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/fullcalendar@5.1.0/main.min.css">
    <script src="https://unpkg.com/fullcalendar@5.1.0/main.min.js"></script>
    <script src="https://unpkg.com/fullcalendar@5.1.0/locales-all.js"></script>
<div class="mb-3">
        <div class="card">
            <div class="card-header">Todas las citas</div>
                <div class="card-body">
                  <ul class="list-group">

                          @foreach($citas as $cita)

                          <li class="list-group-item d-flex justify-content-between align-items-center">{{ $fechaCita = date( 'd-m-Y' ,strtotime($cita->fecha)) }}
                                @if(date('d-m-Y',strtotime(now())) == $fechaCita)
                                     <div class="badge badge-primary">
                                         Hoy
                                     </div>
                                  @endif()
                                  <a class="align-content-end" href="{{route
                          ('pacientes.show',$cita->paciente_id)}}">Ver paciente</a></li>

                          @endforeach
                  </ul>
                </div>
            <div class="card-footer d-flex justify-content-end">
                {{ $citas->links() }}
            </div>
        </div>
</div>

@endsection


