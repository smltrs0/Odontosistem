<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Citas medicas, paciente: {{ $paciente->name." ".$paciente->last_name }}</title>

    <link href="{{ asset('css/base.css') }}" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.5.4/css/simple-line-icons.css">
    <link rel="stylesheet" href="{{asset('css/bootstrap-mod.css')}}">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
        integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"
        integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/jquery"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"
        integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous">
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.17-beta.0/vue.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">Citas medicas del paciente
                {{ Str::ucfirst($paciente->name)." ".Str::ucfirst($paciente->last_name)  }}
                <div class="float-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#evaluacionModal">
                        <i class="fa fa-file-medical"></i> Realizar nueva evaluación
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="">
                    <div class="row">
                        @if (count($paciente->citas_medicas))
                        <div class="col-4">
                            <div class="list-group" id="list-tab" role="tablist">
                                @foreach ($paciente->citas_medicas as $cita)
                                <a class="list-group-item list-group-item-action" id="list-home-list" data-toggle="list"
                                    href="#list-{{ $cita->id }}" role="tab"
                                    aria-controls="home">{{ $cita->created_at }}</a>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="tab-content" id="nav-tabContent">
                                @foreach ($paciente->citas_medicas as $cita)
                                <div class="tab-pane fade" id="list-{{ $cita->id }}" role="tabpanel"
                                    aria-labelledby="list-profile-list">
                                    <div class="row">
                                        <div class="form-group col-md-6 col-sm-12">
                                            <label for="evaluacion">Evaluación</label>
                                            <p>
                                                {{ $cita->evaluacion }}
                                            </p>
                                        </div>
                                        <div class="form-group col-md-6 col-sm-12">
                                            <label for="medicacion">Medicacion</label>
                                            <p>
                                                {{ $cita->medicacion }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-sm-12">
                                            <label for="analisis">Análisis clínico solicitados</label>
                                            <p>
                                                {{ $cita->analisis_solicitados }}
                                            </p>
                                        </div>
                                        <div class="form-group col-md-6 col-sm-12">
                                            <label for="comentario-paciente">Comentario (Visible para el
                                                paciente)</label>
                                            <p>
                                                {{ $cita->comentario_paciente }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="comentario-medico">Comentario (Solo visible para el
                                            médico)</label>
                                        <p>
                                            {{ $cita->comentario_doctor }}
                                        </p>
                                    </div>
                                    <div class="row">
                                        <div class="container">
                                            <label for="">Procedimientos:</label>
                                            @if (count($cita->procedimientos))
                                            <table class="table table-bordered table-light table-sm w-100">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Cantidad</th>
                                                        <th>Costo unitario</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($cita->procedimientos as $procedimiento)
                                                    <tr>
                                                        <td> {{ $procedimiento->title }}</td>
                                                        <td>{{  $procedimiento->pivot->cantidad }}</td>
                                                        <td> {{ $procedimiento->price }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @else
                                            <p class="text-muted">Sin procedimientos registrados</p>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="row mt-5">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                                data-target="#exampleModal">
                                                Generar factura
                                            </button>
                                        </div>
                                        <!-- <div class="col-6">
                                            <a href="#" class="btn btn-block btn-primary"><i class="fa fa-edit"></i>
                                                Modificar datos</a>
                                        </div> -->
                                    </div>
                                </div>
                                <!-- Button trigger modal -->

                                <!-- Modal -->
                                <div class="modal fade" id="exampleModal" tabindex="-1"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Cantidad pagada</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="">Introduzca la cantidad pagada</label>
                                                    <input class="form-control" type="number" name="cantidad"
                                                        id="cantidad{{$cita->id}}">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <div class="row">
                                                <a onClick="generarFactura({{$cita->id}})"
                                                            class="btn btn-block btn-success"><i class="fa fa-print"></i>
                                                            Generar factura</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach

                            </div>
                        </div>
                        @else
                        <div class="alert alert-info col text-center">
                            Este paciente no tiene ninguna cita medica registrada
                        </div>
                        @endif
                    </div>

                </div>
                <!--Final de citas medicas-->
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="evaluacionModal" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div id="app">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="ModalCitaLabel">Evaluación del paciente</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="evaluacion">Evaluacion</label>
                                    <textarea class="form-control" v-model="evaluacion" name="evaluacion" id="evaluacion">
                                                </textarea>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="medicacion">Medicación</label>
                                    <textarea v-model="medicacion" class="form-control" name="medicacion" id="medicacion"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="analisis">Análisis clínico solicitados</label>
                                    <textarea v-model="analisis" class="form-control" name="analisis" id="analisis"></textarea>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="comentario-paciente">Comentario (Visible para el paciente)</label>
                                    <textarea class="form-control" v-model="comentario_paciente" name="comentario_paciente" id="comentario-paciente"></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comentario-medico">Comentario (Solo visible para el médico)</label>
                                <textarea class="form-control" v-model="comentario_medico" name="comentario_medico"
                                    id="comentario_medico"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="input-text" for="procedimientos">Procedimientos a aplicar</label>
                                <br>
                                <div class="col">
                                    <div>
                                            <div>
                                                <div class="row">
                                                    <div class="col-sm-8">
                                                        <select-2 class="form-control" :options="options" name="test"
                                                            v-model="selected"></select-2>
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <input class="form-control form-control-sm" type="number"
                                                            name="cantidad" v-model="cantidad">
                                                    </div>
                                                    <div class="col-sm-1">
                                                        <a href="#" class="btn btn-primary btn-sm"
                                                            v-on:click="agregar()">Agregar</a>
                                                    </div>
                                                </div>
                                                <div class="mt-5">
                                                    <label>Procedimientos seleccionados:</label>
                                                    <ul class="list-group">
                                                        <li v-for=" (procedimiento, index) of procedimientos"
                                                            v-on:click="remove($event, index)" class="list-group-item">

                                                            @{{ castearNombreProcedimiento(procedimiento.id) }}
                                                            <span class="badge badge-primary badge-pill" title="Cantidad">@{{ procedimiento.cantidad }}</span>                                                            
                                                            <button type="button" class="close" data-dismiss="alert"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </li>
                                                        <li v-if="procedimientos.length == 0" class="list-group-item text-center">
                                                            Ningún procedimiento seleccionado
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        <div class="modal-footer">
                                            <button @click="guardarEvaluacion()" class="btn btn-primary">Registrar evaluación
                                                médica
                                            </button>
                                        </div>

                                </div>

                            </div>
                        </div>
                    </div>
            </div>
        </div>
        <form action="">
            @csrf
        </form>
    </div>
    </div>
    <!--Final modal Registrar cita medica-->

    <script>
        Vue.component('select-2', {
        template: '<select v-bind:name="name" class="form-control"></select>',
        props: {
            name: '',
            options: {
                Object
            },
            value: null,
            multiple: {
                Boolean,
                default: false
            }
        },
        data() {
            return {
                select2data: []
            }
        },
        mounted() {
            this.formatOptions()
            let vm = this
            let select = $(this.$el)
            select.select2({
                placeholder: 'Buscar procedimiento',
                width: '100%',
                allowClear: true,
                data: this.select2data
            })
                .on('change', function () {
                    vm.$emit('input', select.val())
                })
            select.val(this.value).trigger('change')
        },
        methods: {
            formatOptions() {
                this.select2data.push({id: '', text: 'Select'})
                for (let key in this.options) {
                    this.select2data.push({id: key, text: this.options[key]})
                }
            }
        },
        destroyed: function () {
            $(this.$el).off().select2('destroy')
        }
    })

    let options = {
    @foreach ($procedimientos as $procedimiento)
    {{ $procedimiento->id }} :
    '{{ $procedimiento->title }}',
    @endforeach
    }

    const app = new Vue({
        el: '#app',
        data:{
            errors: [],
            paciente_id: '{{ $paciente->id }}',
            evaluacion: '',
            analisis: '',
            comentario_paciente: '',
            comentario_medico: '',
            medicacion:'',
            selected: '',
            cantidad: '1',
            options,
            procedimientos: [],
            text: '',
        },
        methods:{
            guardarEvaluacion(){
                this.validarCampos();
                if(this.errors.length == 0){
                    let data = {
                        evaluacion: this.evaluacion,
                        paciente_id: this.paciente_id,
                        analisis: this.analisis,
                        comentario_paciente: this.comentario_paciente,
                        comentario_medico: this.comentario_medico,
                        medicacion: this.medicacion,
                        procedimientos: this.procedimientos,
                    }

                   fetch('{{ route('citas-medicas.store', $paciente->id) }}', {
                    headers:{
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method:'POST',
                    body: JSON.stringify(data)
                   }).then(response => {
                        console.log(response)
                        if(response.status == 200){
                            alert('Se ha guardado exitosamente la evaluación medica.')
                            location.reload();
                        } 
                        else console.error(response.msg);
                   })
                    .catch(function (error) {
                        console.error(error);
                    });
                }else{
                    this.errors.forEach(function(error, index){
                        alert(error);
                    })
                }
                
            },
            validarCampos(){
                this.errors = [];
                if(this.paciente_id == '' || this.paciente_id == null){
                    this.errors.push('El paciente no puede estar vacío, por favor recarga la página');
                }

                if(this.evaluacion == '' || this.evaluacion == null){
                    this.errors.push('La evaluación no puede estar en blanco');
                } 
            },
            castearNombreProcedimiento(id){
               return this.options[id];
            },
            agregar: function () {
                if (this.selected == '') {
                    alert('Selecciona un procedimiento')
                }else {
                    this.procedimientos.push({
                        id: this.selected,
                        cantidad: this.cantidad,
                        text: this.text
                    });
                    this.cantidad = '1';
                    this.selected = '';
                    console.log(procedimientos)
                }


            },
            remove: function (event, index) {
                var result = confirm("¿Estás seguro que deseas borrar? ");
                if (result) {
                    this.procedimientos.splice(index, 1);
                }
            },
        }

    })




    const generarFactura = (cita_id) => {
        let monto = document.getElementById(`cantidad${cita_id}`)

        let data = {
            cita_id: cita_id,
            cantidad: monto.value
        }

        fetch("{{ route('crear-factura')}}", {
                    headers:{
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method:'POST',
                    body: JSON.stringify(data)
                   })
                   .then(response => response.json())
                   .then(result => {
                        console.log(result.message)
                        if(result.message.res == 'error'){
                            alert(result.message.msg);
                            location.reload();
                        }
                   })
                    .catch(function (error) {
                        console.error(error);
                    });
    }

    </script>

</body>

</html>