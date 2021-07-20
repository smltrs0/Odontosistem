@extends('layouts.app')
@section('content')
<script src="{{asset('assets/chart.js/dist/chart.js')}}"></script>
<div class="mb-3">
    <div class="card">
        <div class="card-header">Estadísticas de asistencia de pacientes mensual</div>
        <div class="card-body">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <label class="mr-2">Año:</label>
                        <form action="{{ route('estadisticas-pacientes')}}">
                            <select class="form-control" id="year" name="year">
                                <option value="0">Selecciona el año a mostrar</option>
                                <option value="2021">2021</option>
                                <option value="2020">2020</option>
                                <option value="2019">2019</option>
                                <option value="2018">2018</option>
                                <option value="2017">2017</option>
                                <option value="2016">2016</option>
                            </select>
                            <div class="form-group mt-1">
                                <input type="submit" class="btn btn-primary btn-block" value="buscar">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!--Selecciona el año a mostrar-->
            <div class="container">
                <canvas id="myChart"style="height:50vh; width:70vw" ></canvas>
            </div>
            <script>
                var ctx = document.getElementById('myChart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [
                            @foreach ($ingresos as $ingreso)
                                    "{{$ingreso->mes}}",
                            @endforeach
                        ]
                        ,
                        datasets: [{
                            label: '#Mes',
                            data: [
                                @foreach ($ingresos as $ingreso)
                                    "{{$ingreso->sums}}",
                                @endforeach
                        ],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(255, 159, 64, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        </div>
    </div>
</div>

@endsection