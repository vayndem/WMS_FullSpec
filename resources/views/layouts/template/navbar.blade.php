<div class="mm-top-navbar">
    <div class="mm-navbar-custom">
        <div class="flex items-center gap-3">
            <i class="ri-menu-line wrapper-menu"></i>
        </div>

        <div class="app-context-pill">
            <span class="app-context-icon"><i class="fa-solid fa-cubes-stacked"></i></span>
            <span class="app-context-copy">
                <strong>Warehouse Management</strong>
                <small>Inventory &amp; Accounting Control</small>
            </span>
        </div>

        <div class="flex items-center gap-1">
            <div class="change-mode">
                <i class="fa-solid fa-sun theme-icon theme-icon-light" aria-hidden="true"></i>
                <input type="checkbox" class="toggle toggle-sm" id="dark-mode" aria-label="Aktifkan mode gelap">
                <i class="fa-solid fa-moon theme-icon theme-icon-dark" aria-hidden="true"></i>
            </div>

            <button type="button" class="btn btn-ghost btn-sm btn-circle" id="btnFullscreen" title="Layar penuh">
                <i class="fa-solid fa-expand"></i>
            </button>

            <div class="dropdown dropdown-end">
                <button type="button" tabindex="0" class="profile-circle">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', auth()->user()->name)[1] ?? '', 0, 1)) }}
                </button>
                @auth
                    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-40 mt-2 w-56 p-2 shadow-lg border border-base-300">
                        <li class="menu-title px-2 py-1">
                            <span class="font-semibold text-base-content">{{ auth()->user()->name }}</span>
                            <span class="text-xs text-base-content/50">{{ auth()->user()->email }}</span>
                        </li>
                        <div class="divider my-1"></div>
                        <li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                            <a href="#" onclick="event.preventDefault(); confirmLogout();" class="flex items-center gap-2">
                                <i class="fa-solid fa-right-from-bracket"></i> Logout
                            </a>
                        </li>
                    </ul>
                @endauth
            </div>
        </div>
    </div>
</div>
