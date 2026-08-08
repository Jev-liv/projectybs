<x-layouts.app title="Data Softener">
    @php
        $totalRecords = $statistics['total_records'] ?? 0;
        $recordsToday = $statistics['records_today'] ?? 0;
        $displayed = $statistics['calculations_count'] ?? 0;
    @endphp

    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-500">Softener</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 md:text-3xl">Data Softener</h1>
            <p class="mt-2 text-sm text-slate-600">Menampilkan data Softener 1 dan Softener 2 beserta nilai utamanya.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 mb-4 md:grid-cols-2 lg:grid-cols-4 md:gap-4 md:mb-6">
        <div class="rounded-lg border-l-4 border-sky-500 bg-white p-4 shadow">
            <p class="text-xs font-medium text-gray-500">Total Records</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $totalRecords }}</p>
        </div>
        <div class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow">
            <p class="text-xs font-medium text-gray-500">Records Hari Ini</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $recordsToday }}</p>
        </div>
        <div class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow">
            <p class="text-xs font-medium text-gray-500">Total Data</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $displayed }}</p>
        </div>
        <div class="rounded-lg border-l-4 border-purple-500 bg-white p-4 shadow">
            <p class="text-xs font-medium text-gray-500">Data Ditampilkan</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $displayed }}</p>
        </div>
    </div>

    <x-ui.card title="Data Softener">
        <div class="mb-4 flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 md:flex-row md:items-end md:gap-4 md:p-4">
            <form method="GET" action="{{ route('kernel.boiler-softener.softener.index') }}" class="flex w-full flex-col gap-3 md:flex-row md:items-end md:gap-4">
                <div class="w-full flex-1">
                    <label for="start_date" class="mb-1 block text-xs font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" max="{{ now()->toDateString() }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="w-full flex-1">
                    <label for="end_date" class="mb-1 block text-xs font-medium text-gray-700">Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" max="{{ now()->toDateString() }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="w-full flex-1">
                    <label class="mb-1 block text-xs font-medium text-gray-700">Office/PT</label>
                    @if(auth()->user()->office)
                        <div class="w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-700">{{ auth()->user()->office }}</div>
                    @else
                        <select name="office" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-indigo-500">
                            <option value="all" @selected(($officeFilter ?? 'all') === 'all')>-- Semua Office --</option>
                            @foreach(['YBS', 'SUN', 'SJN'] as $office)
                                <option value="{{ $office }}" @selected(($officeFilter ?? '') === $office)>{{ $office }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 md:px-6">
                        Filter
                    </button>

                    @if(request('start_date') || request('end_date') || request('office'))
                        <a href="{{ route('kernel.boiler-softener.softener.index') }}" class="inline-flex items-center justify-center rounded-lg bg-gray-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-600 md:px-6">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-gray-600">
            <span class="font-medium">Menampilkan data:</span>
            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                📅 {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </span>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                🏢 Office: {{ $officeFilter }}
            </span>
        </div>

        @if($boilerSoftenerCalculations->isEmpty())
            <div class="py-12 text-center">
                <p class="text-base text-gray-500">Belum ada data Softener.</p>
                @can('create kernel losses')
                    <a href="{{ route('kernel.boiler-softener.create') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-6 py-2 text-white hover:bg-indigo-700">Input Data Pertama</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-max divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Tanggal Input</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Jam Proses</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Kode</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Nama Sampel</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Jenis</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Operator</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Sampel Boy</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-700">No Softener</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-700">pH</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-700">TDS</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-700">Total Hardness</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($boilerSoftenerCalculations as $row)
                            <tr class="hover:bg-blue-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ data_get($row, 'tanggal_input', '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ data_get($row, 'jam_proses', '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-blue-900">{{ data_get($row, 'kode', '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ data_get($row, 'nama_sample', '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900"><span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-800">{{ data_get($row, 'jenis', '-') }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ data_get($row, 'operator', '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ data_get($row, 'sampel_boy', '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm font-semibold text-gray-900">{{ data_get($row, 'no_softener', '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-900">{{ data_get($row, 'ph') !== null ? number_format((float) data_get($row, 'ph'), 2) : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-900">{{ data_get($row, 'tds') !== null ? number_format((float) data_get($row, 'tds'), 2) : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm font-semibold text-gray-900">{{ data_get($row, 'total_hardness') !== null ? number_format((float) data_get($row, 'total_hardness'), 2) : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ data_get($row, 'remarks', '-') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $boilerSoftenerCalculations->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>