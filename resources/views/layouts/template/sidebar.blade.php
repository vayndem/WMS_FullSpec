<div class="mm-sidebar sidebar-default">
    <div class="mm-sidebar-logo d-flex align-items-center justify-content-between">
        <a href="{{ route('dashboard') }}" class="header-logo">
            <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid rounded light-logo" alt="Logo">
        </a>
        <div class="side-menu-bt-sidebar">
            <i class="fa-solid fa-bars wrapper-menu"></i>
        </div>
    </div>
    <div class="data-scrollbar" data-scroll="1">
        <nav class="mm-sidebar-menu">
            <ul id="mm-sidebar-toggle" class="side-menu">

                <li class="">
                    <a href="{{ route('dashboard') }}" class="svg-icon">
                        <i>
                            <svg class="svg-icon" id="mm-dash" width="20" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    style="stroke-dasharray: 77, 97; stroke-dashoffset: 0;"></path>
                            </svg>
                        </i>
                        <span class="ms-2">Dashboard</span>
                    </a>
                </li>

                @if (Auth::user()->can('viewAny', App\Models\Supplier::class) || Auth::user()->can('viewAny', App\Models\Asset::class))
                    <li
                        class="{{ request()->routeIs('supplier.*') || request()->routeIs('bahan.*') || request()->routeIs('reconciliation.*') ? 'active' : '' }}">
                        <a href="#master" class="collapsed svg-icon" data-bs-toggle="collapse"
                            aria-expanded="{{ request()->routeIs('supplier.*') || request()->routeIs('bahan.*') || request()->routeIs('reconciliation.*') ? 'true' : 'false' }}">
                            <i>
                                <svg class="svg-icon" id="mm-master-1" width="20" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </i>
                            <span class="ms-2">Master</span>
                            <i class="fa-solid fa-chevron-right mm-arrow-right arrow-active"></i>
                            <i class="fa-solid fa-chevron-down mm-arrow-right arrow-hover"></i>
                        </a>
                        <ul id="master"
                            class="submenu collapse {{ request()->routeIs('supplier.*') || request()->routeIs('bahan.*') || request()->routeIs('reconciliation.*') ? 'show' : '' }}"
                            data-bs-parent="#mm-sidebar-toggle">
                            @can('viewAny', App\Models\Supplier::class)
                                <li class="{{ request()->routeIs('supplier.*') ? 'active' : '' }}">
                                    <a href="{{ route('supplier.index') }}" class="svg-icon">
                                        <i>
                                            <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" width="20">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                            </svg>
                                        </i>
                                        <span class="">Supplier</span>
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', App\Models\Bahan::class)
                                <li class="{{ request()->routeIs('bahan.*') ? 'active' : '' }}">
                                    <a href="{{ route('bahan.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-boxes-stacked"></i><span>Master Bahan</span>
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', App\Models\AccountingReconciliation::class)
                                <li class="{{ request()->routeIs('reconciliation.*') ? 'active' : '' }}">
                                    <a href="{{ route('reconciliation.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-scale-balanced"></i><span>Rekonsiliasi WMS</span>
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', App\Models\Asset::class)
                                <li
                                    class="{{ request()->routeIs('assets.*') || request()->routeIs('asset-categories.*') ? 'active' : '' }}">
                                    <a href="{{ route('assets.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-building-columns"></i><span>Asset Tetap</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @if (Auth::user()->can('viewAny', App\Models\TipePembebanan::class) ||
                        Auth::user()->can('viewAny', App\Models\ChartOfAccount::class) ||
                        Auth::user()->can('viewAny', App\Models\Jurnal::class))
                    <li
                        class="{{ request()->routeIs('tipe-pembebanan.*') || request()->routeIs('kategori-bahan.*') || request()->routeIs('chart-of-accounts.*') || request()->routeIs('jurnal.*') || request()->routeIs('period-lock.*') || request()->routeIs('tax-rate.*') ? 'active' : '' }}">
                        <a href="#akuntansi" class="collapsed svg-icon" data-bs-toggle="collapse"
                            aria-expanded="{{ request()->routeIs('tipe-pembebanan.*') || request()->routeIs('kategori-bahan.*') || request()->routeIs('chart-of-accounts.*') || request()->routeIs('jurnal.*') || request()->routeIs('period-lock.*') || request()->routeIs('tax-rate.*') ? 'true' : 'false' }}">
                            <i>
                                <svg class="svg-icon" id="mm-akuntansi-1" width="20"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </i>
                            <span class="ms-2">Akuntansi</span>
                            <i class="fa-solid fa-chevron-right mm-arrow-right arrow-active"></i>
                            <i class="fa-solid fa-chevron-down mm-arrow-right arrow-hover"></i>
                        </a>
                        <ul id="akuntansi"
                            class="submenu collapse {{ request()->routeIs('tipe-pembebanan.*') || request()->routeIs('kategori-bahan.*') || request()->routeIs('chart-of-accounts.*') || request()->routeIs('jurnal.*') || request()->routeIs('period-lock.*') || request()->routeIs('tax-rate.*') ? 'show' : '' }}"
                            data-bs-parent="#mm-sidebar-toggle">

                            @can('viewAny', App\Models\TipePembebanan::class)
                                <li class="{{ request()->routeIs('tipe-pembebanan.*') ? 'active' : '' }}">
                                    <a href="{{ route('tipe-pembebanan.index') }}" class="svg-icon">
                                        <i>
                                            <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" width="20">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 7h10M7 12h10m-8 5h8" />
                                            </svg>
                                        </i>
                                        <span class="">Tipe Pembebanan</span>
                                    </a>
                                </li>
                            @endcan

                            @can('viewAny', App\Models\KategoriBahan::class)
                                <li class="{{ request()->routeIs('kategori-bahan.*') ? 'active' : '' }}">
                                    <a href="{{ route('kategori-bahan.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-layer-group"></i><span class="">Kategori &
                                            Mapping</span>
                                    </a>
                                </li>
                            @endcan

                            @can('viewAny', App\Models\ChartOfAccount::class)
                                <li class="{{ request()->routeIs('chart-of-accounts.*') ? 'active' : '' }}">
                                    <a href="{{ route('chart-of-accounts.index') }}" class="svg-icon">
                                        <i>
                                            <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" width="20">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </i>
                                        <span class="">Chart of Accounts</span>
                                    </a>
                                </li>
                            @endcan

                            @can('viewAny', App\Models\Jurnal::class)
                                <li class="{{ request()->routeIs('jurnal.*') ? 'active' : '' }}">
                                    <a href="{{ route('jurnal.index') }}" class="svg-icon">
                                        <i>
                                            <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" width="20">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </i>
                                        <span class="">Jurnal Umum</span>
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', App\Models\AccountingPeriodLock::class)
                                <li class="{{ request()->routeIs('period-lock.*') ? 'active' : '' }}">
                                    <a href="{{ route('period-lock.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-calendar-xmark"></i><span>Kunci Periode</span>
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', App\Models\TaxRate::class)
                                <li class="{{ request()->routeIs('tax-rate.*') ? 'active' : '' }}">
                                    <a href="{{ route('tax-rate.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-percent"></i><span>Tarif Pajak</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @can('viewAny', App\Models\Request::class)
                    <li class="{{ request()->routeIs('request.*') ? 'active' : '' }}">
                        <a href="{{ route('request.index') }}" class="svg-icon">
                            <i>
                                <svg class="svg-icon" id="mm-request" width="20" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </i>
                            <span class="ms-2">Request</span>
                        </a>
                    </li>
                @endcan

                @if (Auth::user()->can('viewAny', App\Models\Pembelian::class) ||
                        Auth::user()->can('viewAny', App\Models\ServicePurchase::class))
                    <li class="{{ request()->routeIs('pembelian.*', 'service-purchases.*') ? 'active' : '' }}">
                        <a href="#transaksi" class="collapsed svg-icon" data-bs-toggle="collapse"
                            aria-expanded="{{ request()->routeIs('pembelian.*', 'service-purchases.*') ? 'true' : 'false' }}">
                            <i>
                                <svg class="svg-icon" id="mm-transaksi-1" width="20"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                            </i>
                            <span class="ms-2">Transaksi</span>
                            <i class="fa-solid fa-chevron-right mm-arrow-right arrow-active"></i>
                            <i class="fa-solid fa-chevron-down mm-arrow-right arrow-hover"></i>
                        </a>
                        <ul id="transaksi"
                            class="submenu collapse {{ request()->routeIs('pembelian.*', 'service-purchases.*') ? 'show' : '' }}"
                            data-bs-parent="#mm-sidebar-toggle">
                            @can('viewAny', App\Models\Pembelian::class)
                                <li class="{{ request()->routeIs('pembelian.*') ? 'active' : '' }}">
                                    <a href="{{ route('pembelian.index') }}" class="svg-icon">
                                        <i>
                                            <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" width="20">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                            </svg>
                                        </i>
                                        <span class="">Pembelian (PO)</span>
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', App\Models\ServicePurchase::class)
                                <li class="{{ request()->routeIs('service-purchases.*') ? 'active' : '' }}">
                                    <a href="{{ route('service-purchases.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-file-signature"></i><span>PO Jasa</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @if (Auth::user()->can('viewAny', App\Models\Lpb::class) || Auth::user()->can('viewAny', App\Models\ServiceBap::class))
                    <li class="{{ request()->routeIs('lpb.*', 'service-baps.*') ? 'active' : '' }}">
                        <a href="#penerimaan" class="collapsed svg-icon" data-bs-toggle="collapse"
                            aria-expanded="{{ request()->routeIs('lpb.*', 'service-baps.*') ? 'true' : 'false' }}">
                            <i>
                                <svg class="svg-icon" id="mm-penerimaan" width="20"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </i>
                            <span class="ms-2">Penerimaan</span>
                            <i class="fa-solid fa-chevron-right mm-arrow-right arrow-active"></i>
                            <i class="fa-solid fa-chevron-down mm-arrow-right arrow-hover"></i>
                        </a>
                        <ul id="penerimaan"
                            class="submenu collapse {{ request()->routeIs('lpb.*', 'service-baps.*') ? 'show' : '' }}"
                            data-bs-parent="#mm-sidebar-toggle">
                            @can('viewAny', App\Models\Lpb::class)
                                <li class="{{ request()->routeIs('lpb.*') ? 'active' : '' }}">
                                    <a href="{{ route('lpb.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-boxes-stacked"></i><span>LPB Barang</span>
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', App\Models\ServiceBap::class)
                                <li class="{{ request()->routeIs('service-baps.*') ? 'active' : '' }}">
                                    <a href="{{ route('service-baps.index') }}" class="svg-icon">
                                        <i class="fa-solid fa-clipboard-check"></i><span>BAP Jasa</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @can('viewAny', App\Models\Npk::class)
                    <li class="{{ request()->routeIs('npk.*') ? 'active' : '' }}">
                        <a href="{{ route('npk.index') }}" class="svg-icon">
                            <i>
                                <svg class="svg-icon" id="mm-npk" width="20" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </i>
                            <span class="ms-2">NPK</span>
                        </a>
                    </li>
                @endcan

                @if (auth()->check() && (
                    auth()->user()->can('viewAny', App\Models\Gudang::class) ||
                    auth()->user()->can('viewAny', App\Models\StokGudang::class) ||
                    auth()->user()->can('viewAny', App\Models\TransferGudang::class) ||
                    auth()->user()->can('viewAny', App\Models\PemeriksaanConsider::class) ||
                    auth()->user()->can('viewAny', App\Models\MutasiStok::class) ||
                    auth()->user()->can('viewAny', App\Models\PengaturanBahanGudang::class) ||
                    auth()->user()->can('viewAny', App\Models\PembagianGudang::class)
                ))
                    <li class="{{ request()->routeIs('gudangs.*','stok-gudangs.*','pembagian-gudangs.*','pengaturan-bahan-gudangs.*','transfer-gudangs.*','pemeriksaan-considers.*','mutasi-stoks.*','rekonsiliasi-gudangs.*') ? 'active' : '' }}">
                        <a href="#multi-gudang" class="collapsed svg-icon" data-bs-toggle="collapse">
                            <i class="fa-solid fa-warehouse"></i><span class="ms-2">Multi Gudang</span>
                        </a>
                        <ul id="multi-gudang" class="submenu collapse {{ request()->routeIs('gudangs.*','stok-gudangs.*','pembagian-gudangs.*','pengaturan-bahan-gudangs.*','transfer-gudangs.*','pemeriksaan-considers.*','mutasi-stoks.*','rekonsiliasi-gudangs.*') ? 'show' : '' }}" data-bs-parent="#mm-sidebar-toggle">
                            @can('viewAny', App\Models\Gudang::class)
                                <li><a href="{{ route('gudangs.index') }}"><span>Master Gudang</span></a></li>
                            @endcan
                            @can('viewAny', App\Models\StokGudang::class)
                                <li><a href="{{ route('stok-gudangs.index') }}"><span>Saldo & Kartu Stok</span></a></li>
                                <li><a href="{{ route('rekonsiliasi-gudangs.index') }}"><span>Rekonsiliasi</span></a></li>
                            @endcan
                            @can('viewAny', App\Models\TransferGudang::class)
                                <li><a href="{{ route('transfer-gudangs.index') }}"><span>Transfer Gudang</span></a></li>
                            @endcan
                            @can('viewAny', App\Models\PemeriksaanConsider::class)
                                <li><a href="{{ route('pemeriksaan-considers.index') }}"><span>Pemeriksaan Consider</span></a></li>
                            @endcan
                            @can('viewAny', App\Models\MutasiStok::class)
                                <li><a href="{{ route('mutasi-stoks.index') }}"><span>Mutasi Stok</span></a></li>
                            @endcan
                            @can('viewAny', App\Models\PengaturanBahanGudang::class)
                                <li><a href="{{ route('pengaturan-bahan-gudangs.index') }}"><span>Planning Gudang</span></a></li>
                            @endcan
                            @can('viewAny', App\Models\PembagianGudang::class)
                                <li><a href="{{ route('pembagian-gudangs.index') }}"><span>Pembagian Gudang</span></a></li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @can('viewAny', App\Models\StockOpname::class)
                    <li class="{{ request()->routeIs('stock-opname.*') ? 'active' : '' }}">
                        <a href="{{ route('stock-opname.index') }}" class="svg-icon">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <span class="ms-2">Stock Opname</span>
                        </a>
                    </li>
                @endcan

                @can('viewAny', App\Models\Invoicelpb::class)
                    <li class="{{ request()->routeIs('invoice-lpb.*') ? 'active' : '' }}">
                        <a href="{{ route('invoice-lpb.index') }}" class="svg-icon">
                            <i>
                                <svg class="svg-icon" id="mm-invoice-lpb" width="20"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </i>
                            <span class="ms-2">Invoice LPB</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </nav>
    </div>
</div>
