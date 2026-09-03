<div role="tablist" class="tabs tabs-boxed mb-4">
    <a role="tab" href="{{ route('financial-statements.neraca-saldo') }}" class="tab {{ request()->routeIs('financial-statements.neraca-saldo') ? 'tab-active' : '' }}">
        <i class="fa-solid fa-scale-balanced"></i>&nbsp;Neraca Saldo
    </a>
    <a role="tab" href="{{ route('financial-statements.buku-besar') }}" class="tab {{ request()->routeIs('financial-statements.buku-besar') ? 'tab-active' : '' }}">
        <i class="fa-solid fa-book"></i>&nbsp;Buku Besar
    </a>
    <a role="tab" href="{{ route('financial-statements.laba-rugi') }}" class="tab {{ request()->routeIs('financial-statements.laba-rugi') ? 'tab-active' : '' }}">
        <i class="fa-solid fa-chart-line"></i>&nbsp;Laba Rugi
    </a>
    <a role="tab" href="{{ route('financial-statements.neraca') }}" class="tab {{ request()->routeIs('financial-statements.neraca') ? 'tab-active' : '' }}">
        <i class="fa-solid fa-building-columns"></i>&nbsp;Neraca
    </a>
</div>
