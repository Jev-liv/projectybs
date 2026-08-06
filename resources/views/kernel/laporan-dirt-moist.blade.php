<x-layouts.app title="Laporan Dirt & Moist">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Laporan Dirt &amp; Moist</h1>
        <p class="mt-2 text-sm text-gray-600">Laporan data Dirt &amp; Moist per periode.</p>
    </div>

    {{-- Filter & Export --}}
    <div class="mb-6 bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
        <form method="GET" action="{{ route('reports.kernel.dirt-moist') }}"
            class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-end flex-wrap">

            <div class="flex-1 w-full min-w-[160px]">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filter Kode</label>
                <select name="kode"
                    class="w-full px-3 py-1.5 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Semua Kode --</option>
                    @foreach($kodeOptions as $kodeValue => $kodeLabel)
                        <option value="{{ $kodeValue }}" {{ ($kode ?? '') == $kodeValue ? 'selected' : '' }}>
                            {{ $kodeValue }} - {{ $kodeLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 w-full min-w-[140px]">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Office/PT</label>
                @if(auth()->user()->office)
                    <div
                        class="w-full px-3 py-1.5 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-sm bg-gray-100 text-gray-700">
                        {{ auth()->user()->office }}
                    </div>
                @else
                    <select name="office"
                        class="w-full px-3 py-1.5 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="all" {{ $officeFilter == 'all' ? 'selected' : '' }}>-- Semua Office --</option>
                        <option value="YBS" {{ $officeFilter == 'YBS' ? 'selected' : '' }}>YBS</option>
                        <option value="SUN" {{ $officeFilter == 'SUN' ? 'selected' : '' }}>SUN</option>
                        <option value="SJN" {{ $officeFilter == 'SJN' ? 'selected' : '' }}>SJN</option>
                    </select>
                @endif
            </div>

            <div class="flex-1 w-full min-w-[140px]">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="w-full px-3 py-1.5 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex-1 w-full min-w-[140px]">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="w-full px-3 py-1.5 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <button type="submit"
                    class="px-4 sm:px-6 py-1.5 sm:py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
                @if(request()->hasAny(['kode', 'start_date', 'end_date', 'office']))
                    <a href="{{ route('reports.kernel.dirt-moist') }}"
                        class="px-4 sm:px-6 py-1.5 sm:py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition text-sm font-medium text-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @can('view laporan kernel losses')
            <form method="POST" action="{{ route('reports.kernel.dirt-moist.export') }}" class="mt-4">
                @csrf
                <input type="hidden" name="kode" value="{{ $kode ?? '' }}">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="office" value="{{ $officeFilter }}">
                <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2 text-sm font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export ke Excel
                </button>
            </form>
        @endcan

        <div class="mt-3 text-sm text-gray-600">
            <span class="font-medium">Periode:</span>
            <span
                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} -
                {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </span>
            @if($kode ?? null)
                <span
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    Kode: {{ $kode }}
                </span>
            @endif
            <span
                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Office: {{ $officeFilter }}
            </span>
        </div>
    </div>

    {{-- Summary card --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 border-l-4 border-l-amber-500">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Total Records</p>
            <p class="text-2xl font-bold text-amber-600">{{ number_format($totalRecords) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 border-l-4 border-l-purple-500">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Rata-rata Dirty to Sampel</p>
            <p class="text-2xl font-bold text-purple-600">
                {{ $avgDirty !== null ? number_format((float) $avgDirty, 4) : '0.0000' }}
                <span class="text-sm font-normal text-gray-400">%</span>
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 border-l-4 border-l-cyan-500">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Rata-rata Kadar Air</p>
            <p class="text-2xl font-bold text-cyan-600">
                {{ $avgMoist !== null ? number_format((float) $avgMoist, 4) : '0.0000' }}
                <span class="text-sm font-normal text-gray-400">%</span>
            </p>
        </div>
    </div>

    <x-ui.card title="Tabel Data Laporan Dirt &amp; Moist">
        <div class="mb-4 flex items-center justify-between gap-3 text-sm text-gray-600">
            <span>Data Dirt &amp; Moist sesuai filter aktif.</span>
            <span class="font-medium">{{ number_format($totalRecords) }} record</span>
        </div>

        <style>
            .tbl-dm {
                border-collapse: separate;
                border-spacing: 0;
            }

            .tbl-dm thead tr.grp th {
                font-size: .65rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                padding: 6px 8px;
                border-bottom: none;
            }

            .tbl-dm thead tr.sub th {
                font-size: .65rem;
                font-weight: 600;
                letter-spacing: .05em;
                text-transform: uppercase;
                padding: 7px 8px;
                border-top: 1px solid rgba(0, 0, 0, .08);
                white-space: nowrap;
            }

            .tbl-dm .fz {
                background-color: #fff;
                position: sticky;
            }

            .tbl-dm .fz-last {
                box-shadow: 4px 0 8px -2px rgba(0, 0, 0, .13);
            }

            .tbl-dm tbody tr:nth-child(even) td {
                background-color: #f9fafb;
            }

            .tbl-dm tbody tr:nth-child(even) td.fz {
                background-color: #f9fafb !important;
            }

            .tbl-dm tbody tr:hover td {
                background-color: #eef2ff !important;
            }

            .tbl-dm tbody tr:hover td.fz {
                background-color: #eef2ff !important;
            }

            .tbl-dm thead {
                border-bottom: 2px solid #0891b2;
            }

            .dm-ok {
                display: inline-flex;
                align-items: center;
                padding: 2px 8px;
                border-radius: 9999px;
                font-size: .68rem;
                font-weight: 700;
                background: #dcfce7;
                color: #166534;
            }

            .dm-bad {
                display: inline-flex;
                align-items: center;
                padding: 2px 8px;
                border-radius: 9999px;
                font-size: .68rem;
                font-weight: 700;
                background: #fee2e2;
                color: #991b1b;
            }

            .dm-na {
                display: inline-flex;
                align-items: center;
                padding: 2px 8px;
                border-radius: 9999px;
                font-size: .68rem;
                font-weight: 600;
                background: #f3f4f6;
                color: #374151;
            }
        </style>

        <div class="relative overflow-x-auto overflow-y-auto max-h-[600px] border border-gray-200 rounded-xl shadow-sm">
            <table class="tbl-dm min-w-max text-xs text-gray-700">
                <thead class="sticky top-0 z-40">
                    <tr class="sub">
                        <th
                            class="fz sticky left-0 z-[60] !bg-cyan-600 text-cyan-100 border border-cyan-500/50 w-[50px] text-center">
                            #</th>
                        <th class="fz sticky z-[55] !bg-cyan-600 text-cyan-100 border border-cyan-500/50 w-[100px]"
                            style="left:50px">BULAN</th>
                        <th class="fz sticky z-[55] !bg-cyan-600 text-cyan-100 border border-cyan-500/50 w-[100px]"
                            style="left:150px">TANGGAL</th>
                        <th class="fz sticky z-[55] !bg-cyan-600 text-cyan-100 border border-cyan-500/50 w-[80px]"
                            style="left:250px">JAM</th>
                        <th class="fz fz-last sticky z-[55] !bg-cyan-600 text-cyan-100 border border-cyan-500/50 w-[80px]"
                            style="left:330px">KODE</th>
                        <th class="bg-slate-500 text-slate-100 border border-slate-400/50 min-w-[15０px]">NAMA SAMPEL
                        </th>
                        <th class="bg-slate-500 text-slate-100 border border-slate-400/50 min-w-[130px]">INPUTED BY</th>
                        <th class="bg-slate-500 text-slate-100 border border-slate-400/50">JENIS</th>
                        <th class="bg-slate-500 text-slate-100 border border-slate-400/50 min-w-[110px]">OPERATOR</th>
                        <th class="bg-slate-500 text-slate-100 border border-slate-400/50 min-w-[100px]">SAMPEL BOY</th>
                        <th class="bg-slate-500 text-slate-100 border border-slate-400/50">PENGULANGAN</th>
                        <th class="bg-slate-500 text-slate-100 border border-slate-400/50">KEG. DISPATCH</th>
                        <th class="bg-teal-600 text-teal-100 border border-teal-500/50 text-right min-w-[110px]">BERAT
                            SAMPEL (g)</th>
                        <th class="bg-teal-600 text-teal-100 border border-teal-500/50 text-right min-w-[100px]">BERAT
                            DIRTY (g)</th>
                        <th class="bg-purple-600 text-purple-100 border border-purple-500/50 text-center min-w-[130px]">
                            DIRTY/SAMPEL (%)</th>
                        <th class="bg-purple-600 text-purple-100 border border-purple-500/50 text-center">LIMIT DIRTY
                        </th>
                        <th class="bg-blue-600 text-blue-100 border border-blue-500/50 text-center min-w-[130px]">KADAR
                            AIR (%)</th>
                        <th class="bg-blue-600 text-blue-100 border border-blue-500/50 text-center">LIMIT MOIST</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $master = $masterData[$row->kode] ?? null;
                            $displayAt = $row->rounded_time ?? $row->created_at;
                            $productionDate = $displayAt->copy();
                            if ((int) $productionDate->format('H') < 7) {
                                $productionDate->subDay();
                            }
                            $metricKey = str_starts_with(strtoupper($row->kode ?? ''), 'OUT') ? 'moist' : 'dirty';
                            $dirtyValue = $metricKey === 'dirty' ? (float) ($row->dirty_to_sampel ?? 0) : null;
                            $moistValue = $metricKey === 'moist' ? (float) ($row->moist_percent ?? 0) : null;
                            $dirtyLimitOperator = $metricKey === 'dirty' ? ($row->dirty_limit_operator ?? 'le') : null;
                            $dirtyLimitValue = $metricKey === 'dirty' && $row->dirty_limit_value !== null ? (float) $row->dirty_limit_value : null;
                            $dirtyOk = $dirtyLimitValue !== null
                                ? match ($dirtyLimitOperator) {
                                    'lt' => $dirtyValue < $dirtyLimitValue,
                                    'ge' => $dirtyValue >= $dirtyLimitValue,
                                    'gt' => $dirtyValue > $dirtyLimitValue,
                                    default => $dirtyValue <= $dirtyLimitValue,
                                }
                                : null;
                            $moistLimitOperator = $metricKey === 'moist' ? ($row->moist_limit_operator ?? 'le') : null;
                            $moistLimitValue = $metricKey === 'moist' && $row->moist_limit_value !== null ? (float) $row->moist_limit_value : null;
                            $moistOk = $moistLimitValue !== null
                                ? match ($moistLimitOperator) {
                                    'lt' => $moistValue < $moistLimitValue,
                                    'ge' => $moistValue >= $moistLimitValue,
                                    'gt' => $moistValue > $moistLimitValue,
                                    default => $moistValue <= $moistLimitValue,
                                }
                                : null;
                        @endphp
                        <tr>
                            <td class="fz border-r border-gray-200 px-2 py-2.5 z-[20]" style="left:0">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="toggleDmDetail({{ $loop->index }})" id="dm-btn-{{ $loop->index }}"
                                        class="sm:hidden w-5 h-5 flex-shrink-0 rounded-full bg-cyan-100 text-cyan-700 hover:bg-cyan-200 flex items-center justify-center text-xs font-bold transition-all">+</button>
                                    <span
                                        class="font-semibold text-gray-500 text-xs">{{ ($rows->currentPage() - 1) * $rows->perPage() + $loop->iteration }}</span>
                                </div>
                            </td>
                            <td class="fz border-r border-gray-200 px-3 py-2.5 whitespace-nowrap z-[10]" style="left:50px">
                                {{ $productionDate->format('F Y') }}
                            </td>
                            <td class="fz border-r border-gray-200 px-3 py-2.5 whitespace-nowrap z-[10]" style="left:150px">
                                {{ $productionDate->format('d-m-Y') }}
                            </td>
                            <td class="fz border-r border-gray-200 px-3 py-2.5 whitespace-nowrap z-[10]" style="left:250px">
                                {{ $displayAt->format('H:i:s') }}
                            </td>
                            <td class="fz fz-last border-r-2 border-cyan-200 px-3 py-2.5 z-[10]" style="left:330px">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-cyan-100 text-cyan-800 ring-1 ring-cyan-300">
                                    {{ $row->kode }}
                                </span>
                            </td>
                            <td
                                class="border-r border-gray-100 px-3 py-2.5 bg-slate-50 whitespace-nowrap font-medium text-slate-700">
                                {{ $master->nama_sample ?? '-' }}</td>
                            <td class="border-r border-gray-100 px-3 py-2.5 bg-slate-50 whitespace-nowrap">
                                {{ optional($row->user)->name ?? '-' }}</td>
                            <td class="border-r border-gray-100 px-3 py-2.5 bg-slate-50">
                                <span class="dm-na">{{ $row->jenis ?? '-' }}</span>
                            </td>
                            <td class="border-r border-gray-100 px-3 py-2.5 bg-slate-50 whitespace-nowrap">
                                {{ $row->operator ?? '-' }}</td>
                            <td class="border-r border-gray-100 px-3 py-2.5 bg-slate-50 whitespace-nowrap">
                                {{ $row->sampel_boy ?? '-' }}</td>
                            <td class="border-r border-gray-100 px-3 py-2.5 bg-slate-50 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($row->pengulangan ?? false) ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ($row->pengulangan ?? false) ? 'Ya' : 'Tidak' }}
                                </span>
                            </td>
                            <td class="border-r border-slate-200 px-3 py-2.5 bg-slate-50 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($row->kegiatan_dispek ?? false) ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ($row->kegiatan_dispek ?? false) ? 'Ya' : 'Tidak' }}
                                </span>
                            </td>
                            <td class="border-r border-gray-100 px-3 py-2.5 text-right font-mono">
                                {{ number_format((float) ($row->berat_sampel ?? 0), 4) }}</td>
                            <td class="border-r border-teal-200 px-3 py-2.5 text-right font-mono">
                                {{ $metricKey === 'dirty' ? number_format((float) ($row->berat_dirty ?? 0), 4) : '-' }}</td>
                            <td class="border-r border-gray-100 px-3 py-2.5 text-center bg-purple-50/40">
                                @if($dirtyOk === null)
                                    <span class="dm-na">{{ $dirtyValue === null ? '-' : number_format($dirtyValue, 4) . '%' }}</span>
                                @elseif($dirtyOk)
                                    <span class="dm-ok">{{ number_format($dirtyValue, 4) }}%</span>
                                @else
                                    <span class="dm-bad">{{ number_format($dirtyValue, 4) }}%</span>
                                @endif
                            </td>
                            <td
                                class="border-r border-purple-200 px-3 py-2.5 text-center bg-purple-50/40 text-purple-700 font-medium whitespace-nowrap">
                                @if($dirtyLimitValue !== null)
                                    {{ match ($dirtyLimitOperator) { 'lt' => '<', 'ge' => '>=', 'gt' => '>', default => '<='} }}
                                    {{ number_format($dirtyLimitValue, 4) }}%
                                @else -
                                @endif
                            </td>
                            <td class="border-r border-gray-100 px-3 py-2.5 text-center bg-blue-50/40">
                                @if($moistOk === null)
                                    <span class="dm-na">{{ $moistValue === null ? '-' : number_format($moistValue, 4) . '%' }}</span>
                                @elseif($moistOk)
                                    <span class="dm-ok">{{ number_format($moistValue, 4) }}%</span>
                                @else
                                    <span class="dm-bad">{{ number_format($moistValue, 4) }}%</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center bg-blue-50/40 text-blue-700 font-medium whitespace-nowrap">
                                @if($moistLimitValue !== null)
                                    {{ match ($moistLimitOperator) { 'lt' => '<', 'ge' => '>=', 'gt' => '>', default => '<='} }}
                                    {{ number_format($moistLimitValue, 4) }}%
                                @else
                                < 6.8000% @endif </td>
                        </tr>
                        {{-- Child detail row (mobile expand) --}}
                        <tr id="dm-detail-{{ $loop->index }}" style="display:none" class="bg-cyan-50/30">
                            <td colspan="19" class="px-4 py-4 border-b border-cyan-100">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-xs">
                                    <div>
                                        <p class="text-[10px] font-bold text-cyan-600 uppercase tracking-wider">Nama Sampel
                                        </p>
                                        <p class="mt-0.5 font-medium text-gray-800">{{ $master->nama_sample ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Inputed By
                                        </p>
                                        <p class="mt-0.5">{{ optional($row->user)->name ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jenis</p>
                                        <p class="mt-0.5">{{ $row->jenis ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operator
                                        </p>
                                        <p class="mt-0.5">{{ $row->operator ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sampel Boy
                                        </p>
                                        <p class="mt-0.5">{{ $row->sampel_boy ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pengulangan
                                        </p>
                                        <p class="mt-0.5"><span
                                                class="px-2 py-0.5 rounded-full text-xs {{ ($row->pengulangan ?? false) ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-600' }}">{{ ($row->pengulangan ?? false) ? 'Ya' : 'Tidak' }}</span>
                                        </p>
                                    </div>
                                    <div class="col-span-2 sm:col-span-3 border-t border-cyan-100 pt-2 mt-1"></div>
                                    <div>
                                        <p class="text-[10px] font-bold text-teal-500 uppercase tracking-wider">Berat Sampel
                                        </p>
                                        <p class="mt-0.5 font-mono">{{ number_format((float) ($row->berat_sampel ?? 0), 4) }} g
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-teal-500 uppercase tracking-wider">Berat Dirty
                                        </p>
                                        <p class="mt-0.5 font-mono">{{ number_format((float) ($row->berat_dirty ?? 0), 4) }} g
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-purple-500 uppercase tracking-wider">
                                            Dirty/Sampel</p>
                                        <p class="mt-0.5">@if($dirtyOk === null)<span
                                        class="dm-na">{{ number_format($dirtyValue, 4) }}%</span>@elseif($dirtyOk)<span
                                                class="dm-ok">{{ number_format($dirtyValue, 4) }}%</span>@else<span
                                                class="dm-bad">{{ number_format($dirtyValue, 4) }}%</span>@endif</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-purple-500 uppercase tracking-wider">Limit
                                            Dirty</p>
                                        <p class="mt-0.5 font-medium text-purple-700">
                                            @if($dirtyLimitValue !== null){{ match ($dirtyLimitOperator) { 'lt' => '<', 'ge' => '>=', 'gt' => '>', default => '<='} }}
                                            {{ number_format($dirtyLimitValue, 4) }}%@else-@endif</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Kadar Air
                                        </p>
                                        <p class="mt-0.5">@if($moistOk === null)<span
                                        class="dm-na">{{ number_format($moistValue, 4) }}%</span>@elseif($moistOk)<span
                                                class="dm-ok">{{ number_format($moistValue, 4) }}%</span>@else<span
                                                class="dm-bad">{{ number_format($moistValue, 4) }}%</span>@endif</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Limit Moist
                                        </p>
                                        <p class="mt-0.5 font-medium text-blue-700">
                                            @if($moistLimitValue !== null){{ match ($moistLimitOperator) { 'lt' => '<', 'ge' => '>=', 'gt' => '>', default => '<='} }}
                                            {{ number_format($moistLimitValue, 4) }}%@else&lt; 6.8000%@endif</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="19" class="px-4 py-16 text-center text-sm text-gray-400">Belum ada data laporan
                                Dirt &amp; Moist untuk periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="mt-6 px-4">{{ $rows->links() }}</div>
        @endif
    </x-ui.card>

    <script>
        function toggleDmDetail(idx) {
            const row = document.getElementById('dm-detail-' + idx);
            const btn = document.getElementById('dm-btn-' + idx);
            const isHidden = row.style.display === 'none';
            row.style.display = isHidden ? 'table-row' : 'none';
            btn.textContent = isHidden ? '−' : '+';
            btn.classList.toggle('bg-cyan-300', isHidden);
            btn.classList.toggle('bg-cyan-100', !isHidden);
        }
    </script>
</x-layouts.app>