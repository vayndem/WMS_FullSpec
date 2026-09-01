@if (session('success'))
    <div role="alert" class="alert alert-success mb-4">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if ($errors->any())
    <div role="alert" class="alert alert-error mb-4">
        <i class="fa-solid fa-circle-exclamation"></i>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
