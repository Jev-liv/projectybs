{{--
Component: Sidebar
Navigasi utama aplikasi YBS.
Setiap menu item dijaga dengan @can agar hanya tampil sesuai hak akses role.
--}}

<style>
    #sidebar {
        transition: transform 0.3s ease;
    }

    .sidebar-item-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Hide scrollbar untuk sidebar nav */
    .sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-nav::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .sidebar-nav::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<aside id="sidebar"
    class="fixed lg:sticky top-0 left-0 z-40 w-64 h-screen flex-shrink-0 bg-white border-r border-gray-200 flex flex-col shadow-lg lg:shadow-sm transition-transform -translate-x-full lg:translate-x-0">

    {{-- ── Brand / Logo ──────────────────────────────────────── --}}
    <div class="sidebar-header h-16 flex items-center justify-between px-4 md:px-6 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-indigo-600 tracking-tight">YBS</span>
            <span class="text-base md:text-lg text-gray-500 font-medium">Management</span>
        </div>
        {{-- Close button untuk mobile --}}
        <button id="closeSidebarBtn" class="lg:hidden p-2 hover:bg-gray-200 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- ── Navigasi ──────────────────────────────────────────── --}}
    <nav class="sidebar-nav flex-1 px-3 py-4 space-y-1 overflow-y-auto">

        {{-- Dashboard: semua role bisa akses --}}
        @can('view dashboard')
            <x-sidebar-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')"
                icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>'>
                <span class="sidebar-item-text">Dashboard</span>
            </x-sidebar-item>
        @endcan

        {{-- ── Oil Losses ────────────────────────────────── --}}
        @canany(['view oil losses', 'create oil losses', 'view olwb', 'view performance oil losses'])
            <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
                Oil Losses
            </p>

            {{-- Data Oil Losses --}}
            @can('view oil losses')
                <x-sidebar-item href="{{ route('oil.index') }}" :active="request()->routeIs('oil.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
                    <span class="sidebar-item-text">Data Oil Losses</span>
                </x-sidebar-item>
            @endcan

            {{-- Input Data Baru --}}
            @can('create oil losses')
                <x-sidebar-item href="{{ route('oil.create') }}" :active="request()->routeIs('oil.create')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                    <span class="sidebar-item-text">Input Data Baru</span>
                </x-sidebar-item>
            @endcan

            @can('view olwb')
                <x-sidebar-item href="{{ route('oil.olwb') }}" :active="request()->routeIs('oil.olwb')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>'>
                    <span class="sidebar-item-text">OLWB</span>
                </x-sidebar-item>
            @endcan

            @can('view performance oil losses')
                <x-sidebar-item href="{{ route('oil.report') }}" :active="request()->routeIs('oil.report')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Performance</span>
                </x-sidebar-item>
            @endcan
        @endcanany

        {{-- ── Kernel Losses ───────────────────────────────── --}}
        @canany(['view kernel losses', 'create kernel losses', 'view rekap kernel losses', 'view performance kernel losses'])
            <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
                Kernel Losses
            </p>

            {{-- Data Kernel Losses --}}
            @can('view kernel losses')
                <x-sidebar-item href="{{ route('kernel.index') }}" :active="request()->routeIs('kernel.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
                    <span class="sidebar-item-text">Data Kernel Losses</span>
                </x-sidebar-item>
            @endcan

            {{-- Input Data Kernel Baru --}}
            @can('create kernel losses')
                <x-sidebar-item href="{{ route('kernel.create') }}" :active="request()->routeIs('kernel.create')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                    <span class="sidebar-item-text">Input Data Kernel Baru</span>
                </x-sidebar-item>
            @endcan

            @can('view kernel losses')
                <x-sidebar-item href="{{ route('kernel.qwt.index') }}" :active="request()->routeIs('kernel.qwt.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M12 11v6m-3-3h6"/></svg>'>
                    <span class="sidebar-item-text">Data QWT Fibre Press</span>
                </x-sidebar-item>
            @endcan

            @can('create kernel losses')
                <x-sidebar-item href="{{ route('kernel.qwt.create') }}" :active="request()->routeIs('kernel.qwt.create')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                    <span class="sidebar-item-text">Input Data QWT Fibre Press</span>
                </x-sidebar-item>
            @endcan

            @can('view kernel losses')
                <x-sidebar-item href="{{ route('kernel.dirt-moist.index') }}"
                    :active="request()->routeIs('kernel.dirt-moist.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>'>
                    <span class="sidebar-item-text">Data Dirt & Moist</span>
                </x-sidebar-item>
            @endcan

            @can('create kernel losses')
                <x-sidebar-item href="{{ route('kernel.dirt-moist.create') }}"
                    :active="request()->routeIs('kernel.dirt-moist.create')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                    <span class="sidebar-item-text">Input Dirt & Moist</span>
                </x-sidebar-item>
            @endcan

            @can('view kernel losses')
                <x-sidebar-item href="{{ route('kernel.ripple-mill.index') }}"
                    :active="request()->routeIs('kernel.ripple-mill.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>'>
                    <span class="sidebar-item-text">Data Ripple Mill</span>
                </x-sidebar-item>
            @endcan

            @can('create kernel losses')
                <x-sidebar-item href="{{ route('kernel.ripple-mill.create') }}"
                    :active="request()->routeIs('kernel.ripple-mill.create')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                    <span class="sidebar-item-text">Input Ripple Mill</span>
                </x-sidebar-item>
            @endcan

            @can('view kernel losses')
                <x-sidebar-item href="{{ route('kernel.destoner.index') }}"
                    :active="request()->routeIs('kernel.destoner.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>'>
                    <span class="sidebar-item-text">Data Destoner</span>
                </x-sidebar-item>
            @endcan

            @can('create kernel losses')
                <x-sidebar-item href="{{ route('kernel.destoner.create') }}"
                    :active="request()->routeIs('kernel.destoner.create')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                    <span class="sidebar-item-text">Input Destoner</span>
                </x-sidebar-item>
            @endcan

            {{-- Rekap Data --}}
            @can('view rekap kernel losses')
                <x-sidebar-item href="{{ route('kernel.rekap') }}" :active="request()->routeIs('kernel.rekap')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>'>
                    <span class="sidebar-item-text">Rekap Data</span>
                </x-sidebar-item>
            @endcan

            {{-- Performance --}}
            @can('view performance kernel losses')
                <x-sidebar-item href="{{ route('kernel.performance') }}" :active="request()->routeIs('kernel.performance')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Performance</span>
                </x-sidebar-item>
            @endcan
        @endcanany

        {{-- ── Boiler & Softener ───────────────────────────── --}}
        @canany(['view kernel losses', 'create kernel losses', 'view rekap kernel losses'])
            <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
                Boiler &amp; Softener
            </p>

            @can('view kernel losses')
                <x-sidebar-item href="{{ route('kernel.boiler-softener.boiler.index') }}"
                    :active="request()->routeIs('kernel.boiler-softener.boiler.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h16M6 16V8m4 8V5m4 11v-6m4 6V3"/></svg>'>
                    <span class="sidebar-item-text">Data Boiler</span>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('kernel.boiler-softener.softener.index') }}"
                    :active="request()->routeIs('kernel.boiler-softener.softener.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h16M6 16V8m4 8V5m4 11v-6m4 6V3"/></svg>'>
                    <span class="sidebar-item-text">Data Softener</span>
                </x-sidebar-item>
            @endcan

            @can('create kernel losses')
                <x-sidebar-item href="{{ route('kernel.boiler-softener.create') }}"
                    :active="request()->routeIs('kernel.boiler-softener.create')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                    <span class="sidebar-item-text">Input Data Boiler &amp; Softener</span>
                </x-sidebar-item>
            @endcan

            @can('view rekap kernel losses')
                <x-sidebar-item href="{{ route('kernel.boiler-softener.rekap') }}"
                    :active="request()->routeIs('kernel.boiler-softener.rekap')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>'>
                    <span class="sidebar-item-text">Rekap Boiler &amp; Softener</span>
                </x-sidebar-item>
            @endcan
        @endcanany

        {{-- ── Laporan ─────────────────────────────────────── --}}
        @canany(['view laporan oil losses', 'view laporan kernel losses'])
            <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
                Laporan
            </p>

            @can('view laporan oil losses')
                <x-sidebar-item href="{{ route('reports.index') }}" :active="request()->routeIs('reports.index')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Laporan Oil Losses</span>
                </x-sidebar-item>
            @endcan

            @can('view laporan kernel losses')
                <x-sidebar-item href="{{ route('reports.kernel') }}" :active="request()->routeIs('reports.kernel')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Laporan Kernel Losses</span>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('reports.kernel.dirt-moist') }}"
                    :active="request()->routeIs('reports.kernel.dirt-moist')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Laporan Dirt &amp; Moist</span>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('reports.kernel.qwt') }}" :active="request()->routeIs('reports.kernel.qwt')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Laporan QWT Fibre Press</span>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('reports.kernel.ripple-mill') }}"
                    :active="request()->routeIs('reports.kernel.ripple-mill')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Laporan Ripple Mill</span>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('reports.kernel.destoner') }}"
                    :active="request()->routeIs('reports.kernel.destoner')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Laporan Destoner</span>
                </x-sidebar-item>
            @endcan
        @endcanany

        {{-- ── Process ─────────────────────────────────────── --}}
        <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
            Process
        </p>

        @can('view informasi proses mesin')
            <x-sidebar-item href="{{ route('process.index') }}" :active="request()->routeIs('process.index') || request()->routeIs('process.show') || request()->routeIs('process.edit') || request()->routeIs('process.machines.*')"
                icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                <span class="sidebar-item-text">Informasi Proses Mesin</span>
            </x-sidebar-item>

            {{-- Spintest link removed per request: handled via main process views and machine groups now --}}
        @endcan

        @can('view performance sampel boy')
            <x-sidebar-item href="{{ route('process.performance-sampel-boy') }}"
                :active="request()->routeIs('process.performance-sampel-boy')"
                icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z"/></svg>'>
                <span class="sidebar-item-text">Performance Sampel Boy</span>
            </x-sidebar-item>

            <x-sidebar-item href="{{ route('process.rekap-breakdown') }}"
                :active="request()->routeIs('process.rekap-breakdown')"
                icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'>
                <span class="sidebar-item-text">Rekap Breakdown</span>
            </x-sidebar-item>
        @endcan

        {{-- ── Analisa Moisture & Spintes ──────────────────── --}}
        <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
            Analisa Moisture & Spintes
        </p>

        <x-sidebar-item href="{{ route('analisa-moisture.input') }}"
            :active="request()->routeIs('analisa-moisture.input')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
            <span class="sidebar-item-text">Inputan Analisa</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('analisa-moisture.ffa-moisture') }}"
            :active="request()->routeIs('analisa-moisture.ffa-moisture')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
            <span class="sidebar-item-text">Data FFA & Moisture</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('analisa-moisture.spintest-cot') }}"
            :active="request()->routeIs('analisa-moisture.spintest-cot')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
            <span class="sidebar-item-text">Spintest COT</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('analisa-moisture.spintest-underflow-cst') }}"
            :active="request()->routeIs('analisa-moisture.spintest-underflow-cst')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
            <span class="sidebar-item-text">Spintest Underflow CST</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('analisa-moisture.spintest-feed-decanter') }}"
            :active="request()->routeIs('analisa-moisture.spintest-feed-decanter')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
            <span class="sidebar-item-text">Spintest Feed Decanter</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('analisa-moisture.spintest-light-phase') }}"
            :active="request()->routeIs('analisa-moisture.spintest-light-phase')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
            <span class="sidebar-item-text">Spintest Light Phase</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('analisa-moisture.rekap') }}"
            :active="request()->routeIs('analisa-moisture.rekap')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>'>
            <span class="sidebar-item-text">Rekap Analisa Moisture &amp; Spintest</span>
        </x-sidebar-item>

        {{-- ── Lap Jangkos ──────────────────────────────────── --}}
        <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
            Lap Jangkos
        </p>

        <x-sidebar-item href="{{ route('lap-jangkos.input-usb') }}"
            :active="request()->routeIs('lap-jangkos.input-usb')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
            <span class="sidebar-item-text">Inputan USB</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('lap-jangkos.data-usb') }}"
            :active="request()->routeIs('lap-jangkos.data-usb')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
            <span class="sidebar-item-text">Data USB</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('lap-jangkos.rekap-usb') }}"
            :active="request()->routeIs('lap-jangkos.rekap-usb')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z"/></svg>'>
            <span class="sidebar-item-text">Rekap USB</span>
        </x-sidebar-item>

        @php($currentOffice = strtoupper(auth()->user()->office ?? ''))
        @if ($currentOffice !== 'SUN')
        {{-- ── Oil Loss Foss ──────────────────────────────────── --}}
        <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
            Oil Loss Foss
        </p>

        <x-sidebar-item href="{{ route('oil-loss-foss.input') }}"
            :active="request()->routeIs('oil-loss-foss.input')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
            <span class="sidebar-item-text">Input Oil Loss Foss</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('oil-loss-foss.data') }}"
            :active="request()->routeIs('oil-loss-foss.data')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'>
            <span class="sidebar-item-text">Data Oil Loss Foss</span>
        </x-sidebar-item>

        <x-sidebar-item href="{{ route('oil-loss-foss.rekap') }}"
            :active="request()->routeIs('oil-loss-foss.rekap')"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z"/></svg>'>
            <span class="sidebar-item-text">Rekap Oil Loss Foss</span>
        </x-sidebar-item>
        @endif

        {{-- ── Admin ───────────────────────────────────────── --}}
        @canany(['view users', 'view user activity log'])
            <p class="px-3 pt-5 pb-1 mt-4 text-[11px] uppercase tracking-widest text-gray-400 font-semibold">
                Administrasi
            </p>

            @can('view users')
                <x-sidebar-item href="{{ route('users.index') }}" :active="request()->routeIs('users.*')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>'>
                    <span class="sidebar-item-text">Kelola Pengguna</span>
                </x-sidebar-item>
            @endcan

            @can('view user activity log')
                <x-sidebar-item href="{{ route('activity-logs.index') }}" :active="request()->routeIs('activity-logs.*')"
                    icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                    <span class="sidebar-item-text">Activity Log</span>
                </x-sidebar-item>
            @endcan

            @role('Super Admin')
            <x-sidebar-item href="{{ route('permissions.index') }}" :active="request()->routeIs('permissions.*')"
                icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>'>
                <span class="sidebar-item-text">Permission Control</span>
            </x-sidebar-item>
            @endrole

        @endcanany

    </nav>

    {{-- ── Info User (bawah sidebar) ──────────────────────── --}}

    {{-- ── Info User & Logout ──────────────────────────────────── --}}
    <div class="border-t border-gray-200 bg-gray-50">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit"
                class="w-full px-4 py-3 flex items-center gap-3 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors group"
                title="Logout">
                <div
                    class="w-9 h-9 rounded-full bg-red-100 group-hover:bg-red-200 flex items-center justify-center text-red-600 transition-colors shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </div>
                <div class="flex-1 text-left min-w-0">
                    <p class="text-sm font-medium truncate">Logout</p>
                    <p class="text-xs text-gray-500 group-hover:text-red-500 truncate">
                        {{ Auth::user()->name }}
                        @if(Auth::user()->office)
                            • <span class="font-semibold text-green-600">{{ Auth::user()->office }}</span>
                        @endif
                    </p>
                </div>
            </button>
        </form>
    </div>

</aside>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const closeSidebarBtn = document.getElementById('closeSidebarBtn');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        // Close sidebar dari tombol X di mobile
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', function () {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            });
        }
    });
</script>