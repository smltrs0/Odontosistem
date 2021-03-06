@extends('layouts.app')
@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Crear nuevo procedimiento</div>
            <div class="card-body">
               <form method="POST" action="{{  route('procedures.store') }}">
                @csrf
                   <div class="form-group">
                       <label for="">Nombre</label>
                    <input class="form-control" type="text" name="title">
                   </div>
                   <div class="form-group">
                       <label for="">Codigo</label>
                    <input class="form-control" type="text" name="code">
                   </div>
                   <div class="form-group">
                    <label for="">Costo</label>
                 <input class="form-control" type="number" name="price">
                </div>
                   <div class="form-group">
                       <label for="">Estilo a aplicar</label>
                    <input class="form-control" type="text" name="className">
                   </div>
               
             
            <div class="card-footer">
                <a href="#" onclick="history.back()" class="btn btn-danger btn-sm">Cancelar</a>
                <input class="btn btn-success" type="submit">
            </div>
            </form>
           
        </div>
        
    </div>


@endsection

