<x-layouts.app title="Data Boiler & Softener">

    {{-- =========================================================
        STATISTICS CARDS
    ========================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">

        {{-- TOTAL RECORDS --}}
        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">

                <div class="flex-1 min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">
                        Total Records
                    </p>

                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">
                        {{ $statistics['total_records'] ?? 0 }}
                    </p>
                </div>

                <div class="p-2 md:p-3 bg-indigo-100 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-indigo-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>

                    </svg>
                </div>

            </div>
        </div>


        {{-- RECORDS HARI INI --}}
        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-green-500">

            <div class="flex items-center justify-between">

                <div class="flex-1 min-w-0">

                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">
                        Records Hari Ini
                    </p>

                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">
                        {{ $statistics['records_today'] ?? 0 }}
                    </p>

                </div>

                <div class="p-2 md:p-3 bg-green-100 rounded-full flex-shrink-0">

                    <svg class="w-6 h-6 md:w-8 md:h-8 text-green-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a1 1 0 011-1h2a1 1 0 012 1m-6 9l2 2 4-4"/>

                    </svg>

                </div>

            </div>

        </div>


        {{-- TOTAL DATA --}}
        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-blue-500">

            <div class="flex items-center justify-between">

                <div class="flex-1 min-w-0">

                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">
                        Total Data
                    </p>

                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">
                        {{ $statistics['calculations_count'] ?? 0 }}
                    </p>

                </div>

                <div class="p-2 md:p-3 bg-blue-100 rounded-full flex-shrink-0">

                    <svg class="w-6 h-6 md:w-8 md:h-8 text-blue-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>

                    </svg>

                </div>

            </div>

        </div>


        {{-- DATA DITAMPILKAN --}}
        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-purple-500">

            <div class="flex items-center justify-between">

                <div class="flex-1 min-w-0">

                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">
                        Data Ditampilkan
                    </p>

                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">

                        @if(method_exists($boilerSoftenerCalculations, 'total'))
                            {{ $boilerSoftenerCalculations->total() }}
                        @else
                            {{ $boilerSoftenerCalculations->count() }}
                        @endif

                    </p>

                </div>

                <div class="p-2 md:p-3 bg-purple-100 rounded-full flex-shrink-0">

                    <svg class="w-6 h-6 md:w-8 md:h-8 text-purple-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>

                    </svg>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        MAIN CARD
    ========================================================== --}}
    <x-ui.card title="Data Boiler & Softener">

        {{-- =====================================================
            BUTTON INPUT DATA
        ====================================================== --}}
        <div class="mb-4 md:mb-6 flex flex-col sm:flex-row
                    sm:items-center sm:justify-between gap-3">

            @can('create kernel losses')

                <a href="{{ route('kernel.boiler-softener.create') }}"
                   class="inline-flex items-center justify-center
                          px-3 md:px-4 py-2 md:py-2.5
                          bg-indigo-600 text-white rounded-lg
                          hover:bg-indigo-700 transition
                          font-medium text-sm
                          w-full sm:w-auto">

                    <svg class="w-4 h-4 mr-2"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 4v16m8-8H4"/>

                    </svg>

                    Input Data Boiler &amp; Softener

                </a>

            @endcan

        </div>


        {{-- =====================================================
            FILTER
        ====================================================== --}}
        <div class="mb-4 md:mb-6 bg-gray-50 rounded-lg
                    p-3 md:p-4 border border-gray-200">

            <form method="GET"
                  action="{{ route('kernel.boiler-softener.index') }}"
                  class="flex flex-col sm:flex-row
                         gap-3 md:gap-4 items-end">


                {{-- JENIS --}}
                <div class="flex-1 w-full">

                    <label for="jenis"
                           class="block text-xs md:text-sm
                                  font-medium text-gray-700 mb-1">

                        Jenis

                    </label>

                    <select name="jenis"
                            id="jenis"
                            class="w-full px-3 md:px-4 py-2 text-sm
                                   border border-gray-300 rounded-lg
                                   focus:ring-2 focus:ring-indigo-500
                                   focus:border-transparent">

                        <option value="">
                            -- Semua Jenis --
                        </option>

                        <option value="boiler"
                            @selected(request('jenis') === 'boiler')>
                            Boiler
                        </option>

                        <option value="softener"
                            @selected(request('jenis') === 'softener')>
                            Softener
                        </option>

                    </select>

                </div>


                {{-- OFFICE --}}
                <div class="flex-1 w-full">

                    <label class="block text-xs md:text-sm
                                  font-medium text-gray-700 mb-1">

                        Office/PT

                    </label>

                    @if(auth()->user()->office)

                        <div class="w-full px-3 md:px-4 py-2 text-sm
                                    border border-gray-300 rounded-lg
                                    bg-gray-100 text-gray-700">

                            {{ auth()->user()->office }}

                        </div>

                    @else

                        <select name="office"
                                id="office"
                                class="w-full px-3 md:px-4 py-2 text-sm
                                       border border-gray-300 rounded-lg
                                       focus:ring-2 focus:ring-indigo-500
                                       focus:border-transparent">

                            <option value="all"
                                @selected(($officeFilter ?? 'all') === 'all')>

                                -- Semua Office --

                            </option>

                            @foreach(['YBS', 'SUN', 'SJN'] as $office)

                                <option value="{{ $office }}"
                                    @selected(($officeFilter ?? '') === $office)>

                                    {{ $office }}

                                </option>

                            @endforeach

                        </select>

                    @endif

                </div>


                {{-- TANGGAL MULAI --}}
                <div class="flex-1 w-full">

                    <label for="start_date"
                           class="block text-xs md:text-sm
                                  font-medium text-gray-700 mb-1">

                        Tanggal Mulai

                    </label>

                    <input type="date"
                           id="start_date"
                           name="start_date"
                           value="{{ $startDate }}"
                           max="{{ now()->toDateString() }}"
                           class="w-full px-3 md:px-4 py-2 text-sm
                                  border border-gray-300 rounded-lg
                                  focus:ring-2 focus:ring-indigo-500
                                  focus:border-transparent">

                </div>


                {{-- TANGGAL AKHIR --}}
                <div class="flex-1 w-full">

                    <label for="end_date"
                           class="block text-xs md:text-sm
                                  font-medium text-gray-700 mb-1">

                        Tanggal Akhir

                    </label>

                    <input type="date"
                           id="end_date"
                           name="end_date"
                           value="{{ $endDate }}"
                           max="{{ now()->toDateString() }}"
                           class="w-full px-3 md:px-4 py-2 text-sm
                                  border border-gray-300 rounded-lg
                                  focus:ring-2 focus:ring-indigo-500
                                  focus:border-transparent">

                </div>


                {{-- BUTTON FILTER --}}
                <div class="flex flex-col sm:flex-row gap-2
                            w-full sm:w-auto">

                    <button type="submit"
                            class="inline-flex items-center
                                   justify-center
                                   px-4 md:px-6 py-2
                                   bg-indigo-600 text-white
                                   rounded-lg hover:bg-indigo-700
                                   transition font-medium text-sm
                                   whitespace-nowrap">

                        <svg class="w-4 h-4 mr-2"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>

                        </svg>

                        Filter

                    </button>


                    {{-- RESET --}}
                    @if(
                        request('jenis') ||
                        request('office') ||
                        request('start_date') ||
                        request('end_date')
                    )

                        <a href="{{ route('kernel.boiler-softener.index') }}"
                           class="inline-flex items-center
                                  justify-center
                                  px-4 md:px-6 py-2
                                  bg-gray-500 text-white
                                  rounded-lg hover:bg-gray-600
                                  transition font-medium text-sm
                                  whitespace-nowrap">

                            <svg class="w-4 h-4 mr-2"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>

                            </svg>

                            <span class="hidden sm:inline">
                                Reset ke Hari Ini
                            </span>

                            <span class="sm:hidden">
                                Reset
                            </span>

                        </a>

                    @endif

                </div>

            </form>


            {{-- =================================================
                FILTER INFORMATION
            ================================================== --}}
            <div class="mt-3 text-xs md:text-sm text-gray-600
                        flex flex-wrap items-center gap-2">

                <span class="font-medium">
                    Menampilkan data:
                </span>


                {{-- DATE --}}
                @if($startDate == $endDate)

                    <span class="inline-flex items-center
                                 px-2 md:px-2.5 py-0.5
                                 rounded-full text-xs font-medium
                                 bg-indigo-100 text-indigo-800
                                 whitespace-nowrap">

                        📅
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}

                    </span>

                @else

                    <span class="inline-flex items-center
                                 px-2 md:px-2.5 py-0.5
                                 rounded-full text-xs font-medium
                                 bg-indigo-100 text-indigo-800
                                 whitespace-nowrap">

                        📅
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                        -
                        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}

                    </span>

                @endif


                {{-- JENIS --}}
                @if(request('jenis'))

                    <span class="inline-flex items-center
                                 px-2 md:px-2.5 py-0.5
                                 rounded-full text-xs font-medium
                                 bg-green-100 text-green-800
                                 whitespace-nowrap">

                        ⚙️
                        {{ ucfirst(request('jenis')) }}

                    </span>

                @else

                    <span class="inline-flex items-center
                                 px-2 md:px-2.5 py-0.5
                                 rounded-full text-xs font-medium
                                 bg-gray-100 text-gray-700
                                 whitespace-nowrap">

                        ⚙️ Semua Jenis

                    </span>

                @endif


                {{-- OFFICE --}}
                @if(request('office'))

                    <span class="inline-flex items-center
                                 px-2 md:px-2.5 py-0.5
                                 rounded-full text-xs font-medium
                                 bg-blue-100 text-blue-800
                                 whitespace-nowrap">

                        🏢 Office:
                        {{ $officeFilter }}

                    </span>

                @endif

            </div>

        </div>


        {{-- =====================================================
            DATA TABLE
        ====================================================== --}}
        @if($boilerSoftenerCalculations->isEmpty())

            {{-- EMPTY DATA --}}
            <div class="text-center py-10 md:py-12">

                <svg class="mx-auto h-10 w-10 md:h-12 md:w-12
                            text-gray-400"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>

                </svg>

                <p class="mt-4 text-base md:text-lg text-gray-500">
                    Belum ada data Boiler &amp; Softener
                </p>


                @can('create kernel losses')

                    <a href="{{ route('kernel.boiler-softener.create') }}"
                       class="mt-4 inline-block px-6 py-2
                              bg-indigo-600 text-white rounded-lg
                              hover:bg-indigo-700">

                        Input Data Pertama

                    </a>

                @endcan

            </div>

        @else

            {{-- =================================================
                TABLE
            ================================================== --}}
            <div class="overflow-x-auto">

                <table class="min-w-max divide-y divide-gray-200">

                    {{-- =================================================
                        TABLE HEADER
                    ================================================== --}}
                    <thead class="bg-blue-50">

                        <tr>

                            {{-- 1 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Tanggal Input

                            </th>


                            {{-- 2 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Jam Proses

                            </th>


                            {{-- 3 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Kode

                            </th>


                            {{-- 4 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Nama Sampel

                            </th>


                            {{-- 5 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Jenis

                            </th>


                            {{-- 6 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Operator

                            </th>


                            {{-- 7 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Sampel Boy

                            </th>


                            {{-- 8 --}}
                            <th class="px-4 py-3 text-center text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                No Softener

                            </th>


                            {{-- 9 --}}
                            <th class="px-4 py-3 text-center text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                pH

                            </th>


                            {{-- 10 --}}
                            <th class="px-4 py-3 text-center text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                TDS (ppm)

                            </th>


                            {{-- 11 --}}
                            <th class="px-4 py-3 text-center text-xs
                                       font-medium text-purple-700
                                       uppercase tracking-wider
                                       whitespace-nowrap
                                       bg-purple-100">

                                Total Hardness
                                <br>
                                <span class="normal-case text-[10px]">
                                    (ppm)
                                </span>

                            </th>


                            {{-- 12 --}}
                            <th class="px-4 py-3 text-left text-xs
                                       font-medium text-gray-700
                                       uppercase tracking-wider
                                       whitespace-nowrap">

                                Remarks

                            </th>


                            {{-- 13 --}}
                            @can('delete kernel losses')

                                <th class="px-4 py-3 text-left text-xs
                                           font-medium text-gray-700
                                           uppercase tracking-wider
                                           whitespace-nowrap">

                                    Aksi

                                </th>

                            @endcan

                        </tr>

                    </thead>


                    {{-- =================================================
                        TABLE BODY
                    ================================================== --}}
                    <tbody class="bg-white divide-y divide-gray-200">

                        @foreach($boilerSoftenerCalculations as $row)

                            @php

                                /*
                                |--------------------------------------------------------------------------
                                | Waktu yang ditampilkan
                                |--------------------------------------------------------------------------
                                */
                                $displayAt =
                                    $row->rounded_time ??
                                    $row->created_at;

                            @endphp


                            <tr class="hover:bg-blue-50">


                                {{-- =====================================
                                    TANGGAL INPUT
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-gray-900">

                                    {{ $displayAt
                                        ? $displayAt->format('d/m/Y')
                                        : '-' }}

                                </td>


                                {{-- =====================================
                                    JAM PROSES
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-gray-900">

                                    @if($row->rounded_time)

                                        {{ $row->rounded_time->format('H:i') }}

                                    @elseif($row->created_at)

                                        {{ $row->created_at->format('H:i') }}

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- =====================================
                                    KODE
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm font-semibold text-blue-900">

                                    {{ $row->kode ?? '-' }}

                                </td>


                                {{-- =====================================
                                    NAMA SAMPEL
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-gray-700">

                                    {{ $row->nama_sample
                                        ?? $row->nama_sampel
                                        ?? '-' }}

                                </td>


                                {{-- =====================================
                                    JENIS
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-gray-900">

                                    @if(strtolower($row->jenis ?? '') === 'softener')

                                        <span class="px-2 py-1 rounded-full
                                                     text-xs font-medium
                                                     bg-blue-100
                                                     text-blue-800">

                                            SOFTENER

                                        </span>

                                    @elseif(strtolower($row->jenis ?? '') === 'boiler')

                                        <span class="px-2 py-1 rounded-full
                                                     text-xs font-medium
                                                     bg-green-100
                                                     text-green-800">

                                            BOILER

                                        </span>

                                    @else

                                        <span class="px-2 py-1 rounded-full
                                                     text-xs font-medium
                                                     bg-gray-100
                                                     text-gray-700">

                                            {{ strtoupper($row->jenis ?? '-') }}

                                        </span>

                                    @endif

                                </td>


                                {{-- =====================================
                                    OPERATOR
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-gray-900">

                                    {{ $row->operator ?? '-' }}

                                </td>


                                {{-- =====================================
                                    SAMPEL BOY
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-gray-900">

                                    {{ $row->sampel_boy ?? '-' }}

                                </td>


                                {{-- =====================================
                                    NO SOFTENER
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-center
                                           font-semibold text-gray-900">

                                    {{ $row->no_softener ?? '-' }}

                                </td>


                                {{-- =====================================
                                    pH
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-center
                                           font-semibold text-gray-900">

                                    @if($row->ph !== null)

                                        {{ number_format((float)$row->ph, 2) }}

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- =====================================
                                    TDS
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-center
                                           text-gray-900">

                                    @if($row->tds !== null)

                                        {{ number_format((float)$row->tds, 2) }}

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- =====================================
                                    TOTAL HARDNESS
                                ====================================== --}}
                                <td class="px-4 py-3 whitespace-nowrap
                                           text-sm text-center
                                           font-semibold
                                           bg-purple-50 text-purple-900">

                                    @if($row->total_hardness !== null)

                                        {{ number_format(
                                            (float)$row->total_hardness,
                                            2
                                        ) }}

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- =====================================
                                    REMARKS
                                ====================================== --}}
                                <td class="px-4 py-3 text-sm text-gray-600
                                           max-w-xs break-words">

                                    {{ $row->remarks ?? '-' }}

                                </td>


                                {{-- =====================================
                                    AKSI
                                ====================================== --}}
                                @can('delete kernel losses')

                                    <td class="px-4 py-3 whitespace-nowrap
                                               text-sm font-medium">

                                        <div class="flex items-center gap-2">


                                            {{-- DELETE --}}
                                            <form
                                                action="{{ route(
                                                    'kernel.boiler-softener.destroy',
                                                    $row->id
                                                ) }}"
                                                method="POST"
                                                class="delete-form"
                                                data-item-name="Data {{ $row->kode ?? 'ini' }}">

                                                @csrf

                                                @method('DELETE')


                                                <button type="submit"
                                                        class="inline-flex
                                                               items-center
                                                               justify-center
                                                               w-8 h-8
                                                               rounded-md
                                                               border
                                                               border-red-200
                                                               text-red-600
                                                               hover:bg-red-50
                                                               transition"
                                                        title="Hapus">

                                                    <svg class="w-4 h-4"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         viewBox="0 0 24 24">

                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 011 1v3M4 7h16"/>

                                                    </svg>

                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                @endcan

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- =====================================================
                PAGINATION
            ====================================================== --}}
            <div class="mt-6">

                {{ $boilerSoftenerCalculations->links() }}

            </div>

        @endif

    </x-ui.card>


    {{-- =========================================================
        DELETE CONFIRMATION SCRIPT
    ========================================================== --}}
    <script>

        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.delete-form').forEach(form => {

                form.addEventListener('submit', async function (e) {

                    e.preventDefault();

                    const itemName =
                        this.dataset.itemName || 'data ini';

                    const confirmed =
                        await window.confirmDelete(itemName);

                    if (confirmed) {
                        this.submit();
                    }

                });

            });

        });

    </script>

</x-layouts.app>