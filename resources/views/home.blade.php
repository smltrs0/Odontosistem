<?php
    $admin = false;
    foreach (Auth::user()->roles as $rol) {
        if ($rol->slug == "admin") $admin = true;
    }
?>
@extends('layouts.app')

@section('content')
<!--Contenido de la pagina -->
<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="pe-7s-home icon-gradient bg-mean-fruit">
                </i>
            </div>
            <div>{{__('Dashboard')}}
                <div class="page-title-subheading">{{__('Welcome')}}
                </div>
            </div>
        </div>
    </div>
</div>

@if($admin)
    <div class="row">
        <div class="col-md-6 col-lg-3">
            <div class="card-shadow-danger mb-3 widget-chart widget-chart2 text-left card">
                <div class="widget-content">
                    <div class="widget-content-outer">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left pr-2 fsize-1">
                                <div class="widget-numbers mt-0 fsize-3 text-danger">{{ $citas_canceladas }}</div>
                            </div>
                            <div class="widget-content-right w-100">
                                <div class="progress-bar-xs progress">
                                    <div class="progress-bar bg-danger" role="progressbar" aria-valuenow="{{$citas_canceladas}}" aria-valuemin="0" aria-valuemax="{{$numero_de_citas}}" style="width: {{round($citas_canceladas/$numero_de_citas*100)}}%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="widget-content-left fsize-1">
                            <div class="text-muted opacity-6">Citas canceladas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card-shadow-success mb-3 widget-chart widget-chart2 text-left card">
                <div class="widget-content">
                    <div class="widget-content-outer">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left pr-2 fsize-1">
                                <div class="widget-numbers mt-0 fsize-3 text-success">{{ $citas_confirmada}}</div>
                            </div>
                            <div class="widget-content-right w-100">
                                <div class="progress-bar-xs progress">
                                    <div class="progress-bar bg-success" role="progressbar" aria-valuenow="{{$citas_confirmada}}" aria-valuemin="0" aria-valuemax="100" style="width: {{round($citas_confirmada/$numero_de_citas*100)}}%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="widget-content-left fsize-1">
                            <div class="text-muted opacity-6">Citas confirmadas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card-shadow-warning mb-3 widget-chart widget-chart2 text-left card">
                <div class="widget-content">
                    <div class="widget-content-outer">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left pr-2 fsize-1">
                                <div class="widget-numbers mt-0 fsize-3 text-warning">{{ $citas_sin_confirmar}}</div>
                            </div>
                            <div class="widget-content-right w-100">
                                <div class="progress-bar-xs progress">
                                    <div class="progress-bar bg-warning" role="progressbar" aria-valuenow="{{$citas_sin_confirmar}}" aria-valuemin="0" aria-valuemax="100" style="width: {{round($citas_sin_confirmar/$numero_de_citas*100)}}%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="widget-content-left fsize-1">
                            <div class="text-muted opacity-6">Citas sin confirmar</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card-shadow-info mb-3 widget-chart widget-chart2 text-left card">
                <div class="widget-content">
                    <div class="widget-content-outer">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left pr-2 fsize-1">
                                <div class="widget-numbers mt-0 fsize-3 text-info">{{ $numero_de_citas }}</div>
                            </div>
                            <div class="widget-content-right w-100">
                                <div class="progress-bar-xs progress">
                                    <div class="progress-bar bg-info" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="widget-content-left fsize-1">
                            <div class="text-muted opacity-6">Citas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-shadow-primary mb-5">

        <div class="card-body">
            @if(count($citas)>0)
                <div id="pacientes" class="list-group list-group-flush">
                    <div class="d-flex justify-content-between align-items-center"> 
                        <strong>Turno</strong>  <strong> Nombre del paciente</strong>
                        <strong>Estado del paciente</strong>
                    </div>
                    <?php $contador=1; ?>
                    @foreach($citas as $cita)
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                           href="{{route('pacientes.show', $cita->id_paciente)}}"> {{$contador++.".º"}} <div>{{ucfirst
                   ($cita->name)." "
                   .ucfirst($cita->last_name)}}</div>
                            @if($cita->atendido)
                                <span class="badge badge-pill badge-success">atendido</span>
                            @else
                                <span class="badge badge-pill badge-warning text-white">pendiente</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center">
                    No se ha registrado nínguna cita para el día de hoy.
                </div>
            @endif
        </div>
    </div>

@else
    <div class="container text-center">
        <p class="h2 text-dark">Bienvenid@ {{Auth::user()->name}}</p>
        <div>
            @if(!is_null($paciente_usuario))
                <a href="{{route('citas.index')}}">Crea una cita</a>
            @else
                <div>
                    Termina de rellenar tus datos personales para poder seguir avanzando.
                    <div>
                        <a href="{{route('mi-cuenta.index')}}">Configurar cuenta</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

<!-- <div class="app-wrapper-footer">
            <div class="app-footer">
                <div class="app-footer__inner">
                    <div class="app-footer-left">
                        <ul class="nav">
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link">
                                    Footer Link 1
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="app-footer-right">
                        <ul class="nav">
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link">
                                    Footer Link 2
                                </a>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </div>     -->

<!--/ Final contenido de la pagina-->
@endsection
