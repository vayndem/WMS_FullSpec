<footer class="mm-top-navbar mt-auto border-t border-base-300 bg-base-100 py-3">
    <div class="mm-navbar-custom flex-col gap-2 py-2 sm:flex-row">
        <ul class="flex list-none items-center gap-3 m-0 p-0 text-sm text-base-content/50">
            <li><a href="#" class="no-underline hover:text-base-content">Privacy Policy</a></li>
            <li><a href="#" class="no-underline hover:text-base-content">Terms of Use</a></li>
        </ul>
        <span class="text-sm text-base-content/50">
            Made by <span class="font-bold text-primary">vayndem</span> with <span class="text-error">&hearts;</span>
        </span>
    </div>
</footer>

<script>
    function confirmLogout() {
        window.AppAlert.confirm('Anda akan keluar dari sesi aplikasi ini.', {
            title: 'Apakah Anda yakin?',
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logout-form').submit();
            }
        });
    }
</script>

@stack('scripts')
