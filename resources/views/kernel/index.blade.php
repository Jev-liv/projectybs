<x-layouts.app title="Data Kernel Losses">

    @php
        $successProof = session('success_proof');
    @endphp

    @include('kernel.partials.success-proof-modal', ['successProof' => $successProof])

    {{-- ── Statistics Cards ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">Total Records</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">{{ $statistics['total_records'] }}</p>
                </div>
                <div class="p-2 md:p-3 bg-indigo-100 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-indigo-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">Records Hari Ini</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">{{ $statistics['records_today'] }}</p>
                </div>
                <div class="p-2 md:p-3 bg-green-100 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-green-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">Total Data</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">{{ $statistics['calculations_count'] }}
                    </p>
                </div>
                <div class="p-2 md:p-3 bg-blue-100 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-blue-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 font-medium truncate">Data Ditampilkan</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">{{ $statistics['calculations_count'] }}
                    </p>
                </div>
                <div class="p-2 md:p-3 bg-purple-100 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-purple-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Card with Tabs ───────────────────────────────────────── --}}
    <x-ui.card title="Data Kernel Losses">

        {{-- Action Buttons --}}
        <div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                @can('create kernel losses')
                    <a href="{{ route('kernel.create') }}"
                        class="inline-flex items-center justify-center px-3 md:px-4 py-2 md:py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-sm">
                        <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Input Data Kernel Losses
                    </a>
                @endcan
            </div>
        </div>

        {{-- Date Range Filter --}}
        <div class="mb-4 md:mb-6 bg-gray-50 rounded-lg p-3 md:p-4 border border-gray-200">
            <form method="GET" action="{{ route('kernel.index') }}"
                class="flex flex-col sm:flex-row gap-3 md:gap-4 items-end">

                <div class="flex-1 w-full">
                    <label for="kode" class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                        Filter Kode <span class="text-xs text-gray-500">(Opsional)</span>
                    </label>
                    <select name="kode" id="kode"
                        class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">-- Semua Kode --</option>
                        @foreach($kodeOptions as $kodeValue => $kodeLabel)
                            <option value="{{ $kodeValue }}" {{ request('kode') == $kodeValue ? 'selected' : '' }}>
                                {{ $kodeLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 w-full">
                    <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                        Office/PT
                    </label>
                    @if(auth()->user()->office)
                        <div
                            class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg bg-gray-100 text-gray-700">
                            {{ auth()->user()->office }}
                        </div>
                    @else
                        <select name="office" id="office"
                            class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="all" {{ $officeFilter == 'all' ? 'selected' : '' }}>-- Semua Office --</option>
                            <option value="YBS" {{ $officeFilter == 'YBS' ? 'selected' : '' }}>YBS</option>
                            <option value="SUN" {{ $officeFilter == 'SUN' ? 'selected' : '' }}>SUN</option>
                            <option value="SJN" {{ $officeFilter == 'SJN' ? 'selected' : '' }}>SJN</option>
                        </select>
                    @endif
                </div>

                <div class="flex-1 w-full">
                    <label for="start_date" class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                        Tanggal Mulai <span class="text-xs text-gray-500">(Default: Hari ini)</span>
                    </label>
                    <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                        class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="flex-1 w-full">
                    <label for="end_date" class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                        Tanggal Akhir <span class="text-xs text-gray-500">(Default: Hari ini)</span>
                    </label>
                    <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                        class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 md:px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-sm whitespace-nowrap">
                        <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>

                    @if((request('start_date') && request('start_date') != now()->format('Y-m-d')) || (request('end_date') && request('end_date') != now()->format('Y-m-d')) || request('kode') || request('office'))
                        <a href="{{ route('kernel.index') }}"
                            class="inline-flex items-center justify-center px-4 md:px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition font-medium text-sm whitespace-nowrap">
                            <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="hidden sm:inline">Reset ke Hari Ini</span>
                            <span class="sm:hidden">Reset</span>
                        </a>
                    @endif
                </div>
            </form>

            <div class="mt-3 text-xs md:text-sm text-gray-600 flex flex-wrap items-center gap-2">
                <span class="font-medium">Menampilkan data:</span>
                @if($startDate == $endDate)
                    <span
                        class="inline-flex items-center px-2 md:px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 whitespace-nowrap">
                        📅 {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                    </span>
                @else
                    <span
                        class="inline-flex items-center px-2 md:px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        📅 {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} -
                        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </span>
                @endif
                @if(request('kode'))
                    <span
                        class="inline-flex items-center px-2 md:px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 whitespace-nowrap">
                        🔖 Kode: {{ request('kode') }}
                    </span>
                @else
                    <span
                        class="inline-flex items-center px-2 md:px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 whitespace-nowrap">
                        🔖 Semua Kode
                    </span>
                @endif
                @if(request('office'))
                    <span
                        class="inline-flex items-center px-2 md:px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 whitespace-nowrap">
                        🏢 Office: {{ $officeFilter }}
                    </span>
                @endif
            </div>
        </div>

        @can('view kernel losses')
            {{-- Single merged table --}}
            <div>
                @if ($kernelCalculations->isEmpty())
                    <div class="text-center py-8 md:py-12">
                        <svg class="mx-auto h-10 w-10 md:h-12 md:w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-4 text-base md:text-lg text-gray-500">Belum ada data kernel losses</p>
                        @can('create kernel losses')
                            <a href="{{ route('kernel.create') }}"
                                class="mt-4 inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Input Data Pertama
                            </a>
                        @endcan
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Tanggal Input</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Jam Proses</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Kode</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Nama Sample</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Jenis</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Sampel Boy</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Pengulangan</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Kegiatan Dispatch</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Berat Sampel (g)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Nut Utuh-Nut (g)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Nut Utuh-Kernel (g)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Nut Pecah-Nut (g)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Nut Pecah-Kernel (g)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Kernel Utuh (g)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Kernel Pecah (g)</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-purple-700 uppercase tracking-wider bg-purple-100">
                                        KTS Nut Utuh</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-purple-700 uppercase tracking-wider bg-purple-100">
                                        KTS Nut Pecah</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-purple-700 uppercase tracking-wider bg-purple-100">
                                        Kernel Utuh/Sampel</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-purple-700 uppercase tracking-wider bg-purple-100">
                                        Kernel Pecah/Sampel</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-red-700 uppercase tracking-wider bg-red-50">
                                        Kernel Losses</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-orange-700 uppercase tracking-wider bg-orange-50">
                                        Limit</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Remarks</th>
                                    @canany(['edit kernel losses', 'delete kernel losses'])
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                            Aksi</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($kernelCalculations as $calc)
                                    @php
                                        $master = $masterData[$calc->kode] ?? null;
                                        $displayAt = $calc->rounded_time ?? $calc->created_at;
                                        $lossPercent = ($calc->kernel_losses ?? 0) * 100;
                                        $isExceeded = $master ? $master->isExceeded($lossPercent) : null;
                                        $lossBadge = $isExceeded === null
                                            ? 'bg-gray-100 text-gray-700'
                                            : ($isExceeded ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800');
                                    @endphp
                                    <tr class="hover:bg-blue-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $displayAt->format('d/m/Y H:i') }}
                                        </td>
                                        
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $calc->rounded_time ? $calc->rounded_time->format('H:i') : $calc->created_at->format('H:i') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-blue-900">
                                            {{ $calc->kode }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $master->nama_sample ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $calc->jenis ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $calc->operator ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $calc->sampel_boy ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $calc->pengulangan ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $calc->pengulangan ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ ($calc->kegiatan_dispek ?? false) ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">
                                                {{ ($calc->kegiatan_dispek ?? false) ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($calc->berat_sampel ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($calc->nut_utuh_nut ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($calc->nut_utuh_kernel ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($calc->nut_pecah_nut ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($calc->nut_pecah_kernel ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($calc->kernel_utuh ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($calc->kernel_pecah ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center bg-purple-50">
                                            {{ number_format($calc->kernel_to_sampel_nut_utuh ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center bg-purple-50">
                                            {{ number_format($calc->kernel_to_sampel_nut_pecah ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center bg-purple-50">
                                            {{ number_format($calc->kernel_utuh_to_sampel ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center bg-purple-50">
                                            {{ number_format($calc->kernel_pecah_to_sampel ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $lossBadge }}">
                                                {{ number_format($lossPercent, 2) }}%
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 whitespace-nowrap text-center text-xs font-semibold bg-orange-50 text-orange-800">
                                            {{ $master ? $master->limit_label : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 whitespace-normal max-w-xs break-words">
                                            {{ $calc->remarks ?? '-' }}
                                        </td>
                                        @canany(['edit kernel losses', 'delete kernel losses'])
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center gap-2">
                                                    @can('edit kernel losses')
                                                        <a href="{{ route('kernel.edit', $calc->id) }}"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-indigo-200 text-indigo-600 hover:bg-indigo-50 transition"
                                                            title="Edit">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </a>
                                                    @endcan
                                                    @can('delete kernel losses')
                                                        <form action="{{ route('kernel.calculations.destroy', $calc->id) }}" method="POST"
                                                            class="delete-form" data-item-name="Data {{ $calc->kode ?? 'ini' }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-red-200 text-red-600 hover:bg-red-50 transition"
                                                                title="Hapus">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        @endcanany
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">{{ $kernelCalculations->links() }}</div>
                @endif
            </div>
        @endcan

    </x-ui.card>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const itemName = this.dataset.itemName || 'data ini';
                    const confirmed = await window.confirmDelete(itemName);
                    if (confirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>

</x-layouts.app>