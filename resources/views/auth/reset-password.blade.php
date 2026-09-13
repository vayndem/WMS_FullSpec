<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - ERP Modul WMS</title>

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
    <section class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-2xl border border-base-300 bg-base-100 p-8 shadow-xl">
            <div class="mb-6 text-center">
                <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary text-3xl text-primary-content">
                    <i class="fa-solid fa-lock-open"></i>
                </span>
                <h2 class="text-2xl font-bold">Atur Password Baru</h2>
                <p class="mt-1 text-sm text-base-content/60">Minimal 8 karakter, memuat huruf dan angka.</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="alert alert-error mb-4 text-sm">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-control">
                    <label class="label" for="email"><span class="label-text">Email</span></label>
                    <input class="input input-bordered w-full" type="email" name="email" id="email"
                        value="{{ old('email', $email) }}" autocomplete="email" required>
                </div>

                <div class="form-control">
                    <label class="label" for="password"><span class="label-text">Password Baru</span></label>
                    <input class="input input-bordered w-full" type="password" name="password" id="password"
                        autocomplete="new-password" required autofocus>
                </div>

                <div class="form-control">
                    <label class="label" for="password_confirmation"><span class="label-text">Ulangi Password Baru</span></label>
                    <input class="input input-bordered w-full" type="password" name="password_confirmation"
                        id="password_confirmation" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Password Baru
                </button>
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke halaman masuk
                </a>
            </form>
        </div>
    </section>
</body>

</html>
