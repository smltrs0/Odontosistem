<div class="mt-4">
    <label>{{__('Full name')}}</label>
    <div class="row form-group">
        <div class="col">
            <input type="text" class="form-control" name="name" placeholder="{{__('First name')}}"
                   value="{{old('name', $paciente->name)}}">
        </div>
        <div class="col">
            <input type="text" class="form-control" name="second_name" placeholder="{{__('Second name')}}"
                   value="{{old('second_name', $paciente->second_name)}}">
        </div>
        <div class="col">
            <input type="text" class="form-control" name="last_name" placeholder="{{__('Last name')}}"
                   value="{{old('second_name', $paciente->last_name)}}">
        </div>
        <div class="col">
            <input type="text" class="form-control" name="second_last_name" placeholder="{{__('Second last name')}}"
                   value="{{old('second_last_name', $paciente->second_last_name)}}">
        </div>
    </div>
    <label> Documento nacional de identificacion</label>
    <div class="row form-group">
        <div class="col-4">
            <select name="dni_type" class="form-control">
                <option value="">Selecciona un tipo de dni</option>
            </select>
        </div>
        <div class="col-8">
            <input class="form-control" type="text" name="dni" placeholder="{{__('DNI')}}"
                   value="{{old('dni', $paciente->dni)}}">
        </div>
    </div>
    <label for="sex">Sexo</label>
    <div class="form-group" id="sex">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sex" id="hombre" disable value="1" @if(old('sex', $paciente->sex== 1))
            checked
                   @endif required>
            <label class="form-check-label" for="hombre">
                Hombre
            </label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" disable type="radio" name="sex" id="mujer" value="0" @if(old('sex', $paciente->sex==0))
            checked
                   @endif required>
            <label class="form-check-label" for="mujer">
                Mujer
            </label>
        </div>
    </div>
    <label for="birth_date">Fecha de nacimiento</label>
    <div class="form-group">
        <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{old('birth_date', $paciente->birth_date)}}">
    </div>
    <div class="form-group">
        <label for="inputPhone">Telefono de contacto</label>
        <input class="form-control" type="text" name="phone" id="inputPhone" value="{{old('phone', $paciente->phone)}}">
    </div>
    <div class="form-group">
        <label for="inputCorreo">Correo electronico</label>
        <input class="form-control" type="email" name="email" id="inputCorreo" value="{{old('email', $paciente->email)}}">
    </div>
    <label>Dirección</label>
    <div class="form-group">
        <textarea class="form-control" name="address">{{old('address', $paciente->address)}}</textarea>
    </div>

</div>
