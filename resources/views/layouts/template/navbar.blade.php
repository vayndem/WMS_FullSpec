<div class="mm-top-navbar">
    <div class="mm-navbar-custom">
        <nav class="navbar navbar-expand-lg navbar-light p-0">
            <div class="mm-navbar-logo d-flex align-items-center justify-content-between">
                <i class="ri-menu-line wrapper-menu"></i>
                <a href="{{ url('/') }}" class="header-logo">
                    <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid rounded" alt="Logo">
                    <h4 class="ms-1"><b>MO Inv non Kertas</b></h4>
                </a>
            </div>
            <div class="mm-search-bar device-search m-auto">
                <div class="app-context-pill">
                    <span class="app-context-icon"><i class="fa-solid fa-cubes-stacked"></i></span>
                    <span class="app-context-copy">
                        <strong>Warehouse Management</strong>
                        <small>Inventory &amp; Accounting Control</small>
                    </span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="change-mode">
                    <i class="fa-solid fa-sun theme-icon theme-icon-light" aria-hidden="true"></i>
                    <div class="form-check form-switch m-0">
                        <input type="checkbox" class="form-check-input" id="dark-mode"
                            aria-label="Aktifkan mode gelap">
                    </div>
                    <i class="fa-solid fa-moon theme-icon theme-icon-dark" aria-hidden="true"></i>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-label="Toggle navigation">
                    <i class="ri-menu-3-line"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto navbar-list align-items-center">
                        <li class="nav-item nav-icon dropdown full-screen">
                            <a href="#" class="nav-item nav-icon dropdown" id="btnFullscreen">
                                <i class="max"><svg class="svg-icon text-primary" id="d-3-max" width="20"
                                        height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-maximize">
                                        <path
                                            d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3">
                                        </path>
                                    </svg></i>
                                <i class="min d-none"><svg class="svg-icon text-primary" id="d-3-min" width="20"
                                        height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="feather feather-minimize">
                                        <path
                                            d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3">
                                        </path>
                                    </svg></i>
                            </a>
                        </li>
                        <li class="nav-item nav-icon dropdown">
                            <a href="#" class="nav-item nav-icon dropdown-toggle pe-0 search-toggle"
                                id="dropdownMenuButton" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-haspopup="true"
                                aria-expanded="false">
                                <!-- Profile Circle with Initials -->
                                <div class="profile-circle">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', auth()->user()->name)[1] ?? '', 0, 1)) }}
                                </div>
                            </a>
                            @auth
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                    <li class="dropdown-item-text">
                                        <strong>{{ auth()->user()->name }}</strong><br>
                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li class="dropdown-item d-flex svg-icon">
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                            style="display: none;">
                                            @csrf
                                        </form>
                                        <svg class="svg-icon me-2 text-primary" width="20"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <a href="#"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                    </li>
                                </ul>
                            @endauth
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</div>
