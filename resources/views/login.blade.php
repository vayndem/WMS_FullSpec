<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ERP - Modul WMS</title>

    <script>
        (() => {
            const saved = localStorage.getItem('inventory-theme');
            const theme = saved || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme === 'dark' ? 'wms-dark' : 'wms');
        })();
    </script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/icon/favicon.svg') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-base-200">
    <div id="loading">
        <div id="loading-center"></div>
    </div>

    <section class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-2xl bg-base-100 p-8 shadow-xl border border-base-300">
            <div class="mb-6 text-center">
                <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary text-primary-content text-3xl">
                    <i class="fa-solid fa-warehouse"></i>
                </span>
                <h1 class="text-xl font-bold">ERP <span class="text-base-content/40">-</span> Modul WMS</h1>
                <h2 class="mt-2 text-2xl font-bold">Masuk</h2>
                <p class="mt-1 text-sm text-base-content/60">Gunakan akun perusahaan untuk mengakses ERP - Modul WMS.</p>
            </div>

            <form action="{{ url('/login') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <div class="form-control">
                    <label class="label" for="email">
                        <span class="label-text">Email</span>
                    </label>
                    <input class="input input-bordered w-full" type="email" name="email" id="email"
                        value="{{ old('email') }}" placeholder="nama@perusahaan.com" autocomplete="email" autofocus
                        required>
                </div>

                <div class="form-control">
                    <label class="label" for="password">
                        <span class="label-text">Password</span>
                    </label>
                    <input class="input input-bordered w-full" type="password" name="password" id="password"
                        placeholder="Masukkan password" autocomplete="current-password" required>
                </div>

                <label class="label cursor-pointer justify-start gap-2">
                    <input type="checkbox" class="checkbox checkbox-sm" id="remember" name="remember">
                    <span class="label-text">Ingat saya</span>
                </label>

                @if (session('status'))
                    <div role="alert" class="alert alert-success text-sm">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="alert alert-error text-sm">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-col gap-2">
                    <button type="submit" class="btn btn-primary w-full">
                        <i class="fa-solid fa-right-to-bracket"></i> Masuk
                    </button>
                    <a href="{{ route('password.request') }}" class="btn btn-link btn-sm no-underline">
                        Lupa password?
                    </a>
                </div>
            </form>
        </div>
    </section>

</body>

</html>
