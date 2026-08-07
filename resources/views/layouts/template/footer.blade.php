<footer class="mm-footer py-3 bg-white border-top">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item me-3">
                        <a href="#" class="text-secondary text-decoration-none small">Privacy Policy</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="#" class="text-secondary text-decoration-none small">Terms of Use</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-secondary small">
                    Copyright &copy;
                    <script>
                        document.write(new Date().getFullYear())
                    </script>
                    <a href="#" class="text-primary text-decoration-none fw-bold">PT. Muliaoffset
                        Packindo</a>.
                    All Rights Reserved.
                </span>
            </div>
        </div>
    </div>
</footer>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/bootstrap5-compat.js') }}"></script>
<script src="{{ asset('assets/js/autoNumeric.js') }}"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/js/ui-foundation.js') }}"></script>
<script src="{{ asset('assets/js/smart-picker.js') }}?v=1"></script>
<script src="{{ asset('assets/js/form-draft-autosave.js') }}"></script>
<script src="{{ asset('assets/js/table-report-standard.js') }}?v=6"></script>
<script src="{{ asset('assets/js/app-shell.js') }}?v=2"></script>

<script>
    function confirmLogout() {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Anda akan keluar dari sesi aplikasi ini.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal',
            customClass: {
                confirmButton: 'btn btn-primary px-4 me-2',
                cancelButton: 'btn btn-danger px-4'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logout-form').submit();
            }
        });
    }
</script>

@stack('scripts')
</body>

</html>
