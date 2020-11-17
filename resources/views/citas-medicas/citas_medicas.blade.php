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
                                            <label for="evaluacion">Evaluacion</label>
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
                                        <label for="comentario-medico">Comentario (Solo visible para el médico)</label>
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
                                            <a href="#" class="btn btn-block btn-success"><i class="fa fa-print"></i>
                                                Generar factura</a>
                                        </div>
                                        <div class="col-6">
                                            <a href="#" class="btn btn-block btn-primary"><i class="fa fa-edit"></i>
                                                Modificar datos</a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach

                            </div>
                        </div>
                        @else
                        <div class="alert alert-info col text-center">
                            Este paciente no tiene nunguna cita medica registrada
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
            <div>
                <form action="#" v-on:submit.prevent="onSubmit">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="ModalCitaLabel">Evaluacion del paciente</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="evaluacion">Evaluacion</label>
                                    <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">
                                    <textarea class="form-control" name="evaluacion" id="evaluacion">
                                                </textarea>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="medicacion">Medicacion</label>
                                    <textarea class="form-control" name="medicacion" id="medicacion"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="analisis">Análisis clínico solicitados</label>
                                    <textarea class="form-control" name="analisis" id="analisis"></textarea>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label for="comentario-paciente">Comentario (Visible para el paciente)</label>
                                    <textarea class="form-control" name="comentario_paciente"
                                        id="comentario-paciente"></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comentario-medico">Comentario (Solo visible para el médico)</label>
                                <textarea class="form-control" name="comentario_medico"
                                    id="comentario_medico"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="input-text" for="procedimientos">Procedimientos a aplicar</label>
                                <br>
                                <div class="col">
                                    <div id="app">

                                        <single-select inline-template>
                                        <div>


                                            <div class="row">
                                                    <div class="col-sm-8">
                                                        <select-2  class="form-control"
                                                            :options="options" name="test" v-model="selected"></select-2>
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
                                                    <ul class="list-group">
                                                        <li  v-for=" (procedimiento, index) of procedimientos" v-on:click="remove($event, index)" class="list-group-item">
                                                              @{{ procedimiento.cantidad }}
                                                              <button type="button" class="close" data-dismiss="alert"
                                                              aria-label="Close">
                                                              <span aria-hidden="true">&times;</span>
                                                          </button>
                                                        </li>
                                                      </ul>
                                                </div>
                                            </div>
                                        </single-select>
                                        {{-- <div>
                                            <ol class="list-unstyled">
                                                <li v-for="(item, index) in procedimientos" v-on:click="remove($event, index)"
                                                    class="alert alert-info ">
                                                    @{{ item.cantidad}}
                                                    <button type="button" class="close" data-dismiss="alert"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </li>
                                            </ol>
                                        </div> --}}
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" id="elSubmit" class="btn btn-primary">Registrar evaluación médica
                            </button>
                        </div>
                    </div>
                </form>
            </div>
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
      this.select2data.push({ id: '', text: 'Select' })
      for (let key in this.options) {
        this.select2data.push({ id: key, text: this.options[key] })
      }
    }
    },
  destroyed: function () {
    $(this.$el).off().select2('destroy')
  }
})

const options = {
     @foreach ($procedimientos as $procedimiento)
      {{ $procedimiento->id }} :'{{ $procedimiento->title }}',
        @endforeach 
}

const singleSelect = Vue.component('single-select', {
  data () {
    return {
      selected: '',
      cantidad: '4',
      options,
      procedimientos:[],
    }
  },
  methods:{
    agregar: function() {
        if (this.selected=='') {
            alert('Selecciona un procedimento')
        }else{
            
                        this.procedimientos.push({
                        id: this.selected,
                        cantidad: this.cantidad
                    });
                    this.cantidad = '1';
                    this.selected= '';

        }
        
        
      },
      remove: function(event, index) {
     var result = confirm("¿Estás seguro que deseas borrar? ");
      if(result) {
        this.procedimientos.splice(index, 1);        
      }
		}
  }
})

const app = new Vue({
  el: '#app',

})

    </script>

</body>

</html>