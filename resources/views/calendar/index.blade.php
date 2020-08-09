@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/fullcalendar@5.1.0/main.min.css">
    <script src="https://unpkg.com/fullcalendar@5.1.0/main.min.js"></script>
    <script src="https://unpkg.com/fullcalendar@5.1.0/locales-all.js"></script>
<div class="mb-3">
        <div class="card">
            <div class="card-header">Citas</div>
                <div class="card-body">
                  <ul class="list-group">

                          @foreach($citas as $cita)

                              {{dd($cita)}}
                          <li class="list-group-item">{{$cita->fecha}}  <a class="align-content-end" href="{{route
                          ('pacientes.show',2)}}">Ver paciente</a></li>

                          @endforeach
                  </ul>
                </div>
        </div>
</div>

@endsection


