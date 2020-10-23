<div class="mt-3 ml-3 mr-3" style="z-index: 10;">
    @if(session('success'))
        <div class="alert alert-success" role="alert" style="z-index: 10">

            {{ session('success') }}

        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning" role="alert" style="z-index: 10">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error" role="alert" style="z-index: 10">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" style="z-index: 10">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
