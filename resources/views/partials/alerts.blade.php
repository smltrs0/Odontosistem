<div class="mt-3 ml-3 mr-3">
    @if(session('success'))
<div class="alert alert-success" role="alert">
    {{ session('success') }}
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning" role="alert">
    {{ session('warning') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-error" role="alert">
    {{ session('error') }}
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
</div>