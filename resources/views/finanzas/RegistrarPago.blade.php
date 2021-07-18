@extends('layouts.app')
@section('content')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.25/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.25/datatables.min.js"></script>
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Registrar abono</div>
            <div class="card-body">
              <table class="table table-light table-sm" id="registrarPagos">
                  <thead class="thead-light">
                      <tr>
                          <th>Factura ID</th>
                          <th>DNI</th>
                          <th>Nombre</th>
                          <th>Total abonado</th>
                          <th>Valor factura</th>
                          <th>&nbsp;</th>
                      </tr>
                  </thead>
              </table>
        </div>
    </div>


  
  <!-- Modal -->
  <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Registrar abono</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
              <label for="monto">Monto abonado</label>
              <input id="monto" class="form-control" type="text" name="">
          </div>
          <div class="form-group">
              <label for="method_pay">Metodo de pago</label>
              <select id="method_pay" class="form-control" name="">
                  <option>Text</option>
                  <option>Text</option>
                  <option>Text</option>
              </select>
          </div>
          <div class="form-group">
              <label for="adjunto">Adjunto</label>
              <input id="adjunto" class="form-control-file" type="file" name="">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary">Registar pago</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    let idioma = {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "NingÃºn dato disponible en esta tabla",
                "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar por número de factura:",
                "sUrl":            "",
                "sInfoThousands":  ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":     "Ultimo",
                    "sNext":     "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                },
                "buttons": {
                    "copyTitle": 'Informacion copiada',
                    "copyKeys": 'Use your keyboard or menu to select the copy command',
                    "copySuccess": {
                        "_": '%d filas copiadas al portapapeles',
                        "1": '1 fila copiada al portapapeles'
                    },

                    "pageLength": {
                    "_": "Mostrar %d filas",
                    "-1": "Mostrar Todo"
                    }
                }
            };
    $(document).ready(function() {
      $('#registrarPagos').DataTable({
        "serverSide" : true,
        "ajax": "{{url('api/obtener-facturas')}}",
        "columns": [
          { "data": "id" },
          { "data": "dni" },
          { "data": "nombre_paciente" },
          { "data": "total_abonado" },
          { "data": "valor_factura" },
          { "data": "btn" }

        ],
        "language": idioma,
        "pageLength": 5
      });
    });
    </script>
@endsection

