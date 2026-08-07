<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory MO</title>

    <link rel="shortcut icon" href="../assets/images/icon/favicon.ico" />
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap5-modern.css') }}?v=6">
</head>

<body>
    <div id="loading">
        <div id="loading-center">
        </div>
    </div>

    <div class="wrapper">
        <section class="login-content">
            <div class="container h-100">
                <div class="row align-items-center justify-content-center h-100">
                    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                        <div class="card border-0 shadow-lg">
                            <div class="card-body p-4 p-md-5">
                                <div class="auth-logo text-center mb-4">
                                    <img src="../assets/images/logo.png" class="img-fluid rounded-normal"
                                        alt="Logo Inventory" style="max-height: 72px;">
                                </div>
                                <h2 class="mb-2 text-center">Masuk</h2>
                                <p class="text-muted text-center mb-4">Gunakan akun perusahaan untuk mengakses Inventory
                                    MO.</p>

                                <!-- Form Login -->
                                <form action="{{ url('/login') }}" method="POST">
                                    @csrf

                                    <!-- Email Input -->
                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input class="form-control" type="email" name="email" id="email"
                                            placeholder="nama@perusahaan.com" autocomplete="email" required>
                                    </div>

                                    <!-- Password Input -->
                                    <div class="mb-3">
                                        <label class="form-label" for="password">Password</label>
                                        <input class="form-control" type="password" name="password" id="password"
                                            placeholder="Masukkan password" autocomplete="current-password" required>
                                    </div>

                                    <!-- Remember Me -->
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="remember"
                                                name="remember">
                                            <label class="form-check-label" for="remember">Ingat saya</label>
                                        </div>
                                    </div>

                                    <!-- Error Messages -->
                                    @if ($errors->any())
                                        <div class="alert alert-danger mt-3">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <!-- Login Button -->
                                    <div class="d-grid gap-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
                                        </button>
                                        <button type="button" id="forgot-password"
                                            class="btn btn-link text-decoration-none shadow-none">
                                            Lupa password?
                                        </button>
                                    </div>
                                </form>
                                <!-- End Form Login -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/ui-foundation.js') }}"></script>
    <script src="{{ asset('assets/js/app-shell.js') }}"></script>
    <script>
        // SweetAlert2 for Forgot Password
        document.getElementById('forgot-password').addEventListener('click', function() {
            Swal.fire({
                icon: 'info',
                title: 'Hubungi IT',
                text: 'Silakan hubungi tim IT untuk reset password Anda.',
            });
        });
    </script>
</body>

</html>
