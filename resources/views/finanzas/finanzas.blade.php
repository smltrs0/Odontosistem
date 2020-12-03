@extends('layouts.app')

@section('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<div class="mb-3">
        <div class="card">
            <div class="card-header">Estadísticas de asistencia  de pacientes mensual</div>

                <div class="card-body">
                    <div class="chart-container" style="height: 50vh">
                        <canvas id="chart"></canvas>
                    </div>
                        <script>
                            var data = {
                                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov"],
                                datasets: [{
                                    label: "Pacientes",
                                    backgroundColor: "rgba(255,99,132,0.2)",
                                    borderColor: "rgba(255,99,132,1)",
                                    borderWidth: 2,
                                    hoverBackgroundColor: "rgba(255,99,132,0.4)",
                                    hoverBorderColor: "rgba(255,99,132,1)",
                                    data: [65, 59, 20, 81, 56, 55, 40, 20, 25, 32, 12],
                                }]
                            };

                            var options = {
                                maintainAspectRatio: false,
                                scales: {
                                    yAxes: [{
                                        stacked: true,
                                        gridLines: {
                                            display: true,
                                            color: "rgba(255,99,132,0.2)"
                                        }
                                    }],
                                    xAxes: [{
                                        gridLines: {
                                            display: false
                                        }
                                    }]
                                }
                            };

                            Chart.Bar('chart', {
                                options: options,
                                data: data
                            });

                        </script>
                </div>
        </div>
</div>

@endsection


