
<table width="100%" border="1" cellpadding="5" cellspacing="0">
    <tr>
        <td colspan="2" align="center" style="font-size:18px"><b>FACTURA ODONTOSISTEMS</b> <img width="20px"
                                                                                                src="{{asset('assets/logo.png')}}"
                                                                                                alt=""></td>
    </tr>
    <tr>
        <td colspan="2">
            <table width="100%" cellpadding="5">
                <tr>
                    <td width="65%">
                        Para,<br/>
                        <b>RECEPTOR (FACTURA A)</b><br/>
                        Nombres : {{ ucfirst($paciente->name)."". ucfirst($paciente->last_name) }}<br/>
                        Documento de identificación: {{ $paciente->dni }}  <br/>
                        Dirección de facturación : {{  $paciente->address}}<br/>
                    </td>
                    <td width="35%">
                        Factura No. : {{ $numero_de_factura }}<br/>
                        Factura Fecha : {{ date("d-m-Y H:i:s") }}<br/>
                    </td>
                </tr>
            </table>
            <br/>
            <table width="100%" border="1" cellpadding="5" cellspacing="0">
                <tr>
                    <th align="left">No.</th>
                    <th align="left">Codigo</th>
                    <th align="left">Nombre Producto</th>
                    <th align="left">Cantidad</th>
                    <th align="left">Precio</th>
                    <th align="left">Actual Amt.</th>
                </tr>

                <?php
                $count = 1;
                $subTotal= 0;
                ?>
                @foreach($cita->procedimientos as $procedimiento)
                <tr>
                    <td align="left">{{ $count++ }}</td>
                    <td align="left">{{ $procedimiento->code}}</td>
                    <td align="left">{{ $procedimiento->title}}</td>
                    <td align="left">{{ $procedimiento->pivot->cantidad}}</td>
                    <td align="left">{{ $procedimiento->price }}</td>
                    <?php $subTotal = $subTotal + ($procedimiento->price * $procedimiento->pivot->cantidad) ?>
                    <td align="left">{{ $procedimiento->price * $procedimiento->pivot->cantidad }}</td>
                </tr>
                @endforeach

                <tr>
                    <td align="right" colspan="5"><b>Sub Total</b></td>
                    <td align="left"><b>{{ $subTotal }}</b></td>
                </tr>
                <tr>
                    <td align="right" colspan="5"><b>Tasa Impuesto :</b></td>
                    <td align="left">{{ sprintf("%01.2f", $subTotal*0.12) }}</td>
                </tr>
                <tr>
                    <td align="right" colspan="5">Total:</td>
                    <td align="left">{{ sprintf("%01.2f",($subTotal*0.12) + $subTotal) }}</td>
                </tr>
                <tr>
                    <td align="right" colspan="5">Monto Pagado:</td>
                    <td align="left">{{$abonado}}</td>
                </tr>
                <tr>
                    <td align="right" colspan="5"><b>Monto adeudado:</b></td>
                    <td align="left">{{sprintf("%01.2f",($subTotal*0.12) + $subTotal) - $abonado}}</td>
                </tr>

            </table>
        </td>
    </tr>
</table>
