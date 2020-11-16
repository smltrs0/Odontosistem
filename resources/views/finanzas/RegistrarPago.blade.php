@extends('layouts.app')
@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Registrar pago</div>
            <div class="card-body">
               <div class="form-group">
                  <label for=""> Introduce DNI o el numero de la factura para buscar:</label>
                    <input class="form-control" type="text" name="buscar">
                    <input class="btn btn-block" type="submit" value="Buscar">
               </div>

              <table class="table table-light">
                  <thead class="thead-light">
                      <tr>
                          <th>Factura ID</th>
                          <th>DNI</th>
                          <th></th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr>
                          <td>98a7sdas</td>
                          <td>515161848</td>
                          <td>
                              <!-- Button trigger modal -->
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                                Pagar
                              </button></td>
                      </tr>
                  </tbody>
              </table>
              <div class="alert alert-warning text-center">
                <p>No se han encontrado concidencias en nuestros registros</p>
              </div>
        </div>
    </div>


  
  <!-- Modal -->
  <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Registrar pago</h5>
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
@endsection

