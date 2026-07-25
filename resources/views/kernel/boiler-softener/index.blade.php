<x-layouts.app title="Data Boiler & Softener">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4 mb-4 md:mb-6">
        @foreach([
            ['label' => 'Total Records', 'value' => $statistics['total_records'], 'color' => 'indigo'],
            ['label' => 'Records Hari Ini', 'value' => $statistics['records_today'], 'color' => 'green'],
            ['label' => 'Total Data', 'value' => $statistics['calculations_count'], 'color' => 'blue'],
        ] as $stat)
            <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-{{ $stat['color'] }}-500">
                <p class="text-xs md:text-sm text-gray-500 font-medium">{{ $stat['label'] }}</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <x-ui.card title="Data Boiler & Softener">
        <div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            @can('create kernel losses')
                <a href="{{ route('kernel.boiler-softener.create') }}" class="inline-flex items-center justify-center px-3 md:px-4 py-2 md:py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-sm w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Input Data Boiler &amp; Softener
                </a>
            @endcan
        </div>

        <div class="mb-4 md:mb-6 bg-gray-50 rounded-lg p-3 md:p-4 border border-gray-200">
            <form method="GET" action="{{ route('kernel.boiler-softener.index') }}" class="flex flex-col sm:flex-row gap-3 md:gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Jenis</label>
                    <select name="jenis" class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Semua Jenis --</option>
                        <option value="boiler" @selected(request('jenis') === 'boiler')>Boiler</option>
                        <option value="softener" @selected(request('jenis') === 'softener')>Softener</option>
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Parameter</label>
                    <select name="parameter" class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Semua Parameter --</option>
                        @foreach($parameterOptions as $key => $parameter)
                            <option value="{{ $key }}" @selected(request('parameter') === $key)>{{ $parameter['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Office/PT</label>
                    @if(auth()->user()->office)
                        <div class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg bg-gray-100 text-gray-700">{{ auth()->user()->office }}</div>
                    @else
                        <select name="office" class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="all" @selected($officeFilter === 'all')>-- Semua Office --</option>
                            @foreach(['YBS', 'SUN', 'SJN'] as $office)<option value="{{ $office }}" @selected($officeFilter === $office)>{{ $office }}</option>@endforeach
                        </select>
                    @endif
                </div>
                <div class="flex-1 w-full"><label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label><input type="date" name="start_date" value="{{ $startDate }}" max="{{ now()->toDateString() }}" class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></div>
                <div class="flex-1 w-full"><label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label><input type="date" name="end_date" value="{{ $endDate }}" max="{{ now()->toDateString() }}" class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></div>
                <button type="submit" class="inline-flex items-center justify-center px-4 md:px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-sm whitespace-nowrap">Filter</button>
            </form>
        </div>

        @if($boilerSoftenerCalculations->isEmpty())
            <div class="text-center py-10"><p class="text-base text-gray-500">Belum ada data Boiler &amp; Softener</p></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50"><tr>
                        @foreach(['Tanggal Sampel', 'Jenis', 'Parameter', 'Nilai', 'Satuan', 'Operator', 'Sampel Boy', 'Pengulangan', 'Remarks', 'Aksi'] as $heading)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider whitespace-nowrap">{{ $heading }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($boilerSoftenerCalculations as $row)
                            <tr class="hover:bg-blue-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ ($row->rounded_time ?? $row->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold uppercase text-gray-900">{{ $row->jenis }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $parameterOptions[$row->parameter]['label'] ?? $row->parameter }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold text-gray-900">{{ number_format((float) $row->nilai, 4) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $row->satuan ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $row->operator ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $row->sampel_boy ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-center"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $row->pengulangan ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">{{ $row->pengulangan ? 'Ya' : 'Tidak' }}</span></td>
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs break-words">{{ $row->remarks ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">@can('delete kernel losses')<form action="{{ route('kernel.boiler-softener.destroy', $row) }}" method="POST" class="delete-form">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-red-200 text-red-600 hover:bg-red-50" title="Hapus"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 011 1v3M4 7h16"/></svg></button></form>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $boilerSoftenerCalculations->links() }}</div>
        @endif
    </x-ui.card>
    <script>document.querySelectorAll('.delete-form').forEach(form => form.addEventListener('submit', async event => { event.preventDefault(); if (await window.confirmDelete('data ini')) form.submit(); }));</script>
</x-layouts.app>
