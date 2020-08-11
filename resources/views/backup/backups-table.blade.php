@if (count($backups))
    <table class="table table-striped table-bordered">
        <thead class="thead-dark">
        <tr>
            <th>Nombre del archivo</th>
            <th>Tamaño</th>
            <th>Fecha realizado</th>

            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($backups as $backup)
            <tr>
                <td>{{ $backup['file_name'] }}</td>
                <td>{{ $backup['file_size'] }}</td>
                <td>
                    {{ date('d/M/Y, g:ia', strtotime($backup['last_modified']))." ".diff_date_for_humans
                    ($backup['last_modified']) }}
                </td>

                <td class="text-right">
                    <a class="btn btn-sm btn-primary" href="{{ url('backup/download/'.$backup['file_name']) }}">
                        <i class="fa fa-cloud-download"></i> Descargar</a>
                    <a class="btn btn-sm btn-danger" data-button-type="delete"
                       href="{{ url('backup/delete/'.$backup['file_name']) }}">
                        <i class="fa fa-trash"></i>
                        Eliminar
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="text-center py-5">
        <h1 class="text-muted">No existen backups</h1>
    </div>
@endif
