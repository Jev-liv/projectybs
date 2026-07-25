<x-layouts.app title="Input Data Boiler & Softener">
    @php $isYbsOffice = Auth::user()?->office === 'YBS'; @endphp
    <x-ui.card title="Input Data Boiler & Softener">
        @if($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded"><h4 class="text-sm font-semibold text-red-900 mb-2">Data belum bisa disimpan</h4><ul class="list-disc list-inside text-sm text-red-700 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form action="{{ route('kernel.boiler-softener.store') }}" method="POST" id="boilerSoftenerForm" class="space-y-6">
            @csrf
            <div class="border-2 border-blue-200 bg-blue-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Sampel</label><input type="date" name="tanggal_sampel" value="{{ old('tanggal_sampel', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Jam Pengambilan <span class="text-red-500">*</span></label><input type="time" name="rounded_time" value="{{ old('rounded_time', now()->format('H:i')) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm"></div>
                </div>
            </div>

            @foreach(['boiler' => 'Boiler', 'softener' => 'Softener'] as $jenis => $jenisLabel)
                <div class="space-y-3 rounded-xl border p-4 {{ $jenis === 'boiler' ? 'border-emerald-200 bg-emerald-50' : 'border-sky-200 bg-sky-50' }}">
                    <h3 class="text-sm font-semibold uppercase tracking-wide {{ $jenis === 'boiler' ? 'text-emerald-900' : 'text-sky-900' }}">{{ $jenisLabel }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($parameterOptions as $parameter => $config)
                            @if(in_array($jenis, $config['jenis'], true))
                                <div class="border rounded-lg p-4 bg-white/80 shadow-sm space-y-3">
                                    <div class="flex items-center justify-between gap-3"><h4 class="text-sm font-semibold text-gray-900">{{ $config['label'] }}</h4><span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ $config['satuan'] }}</span></div>
                                    <input type="hidden" name="rows[{{ $jenis }}_{{ $parameter }}][jenis]" value="{{ $jenis }}">
                                    <input type="hidden" name="rows[{{ $jenis }}_{{ $parameter }}][parameter]" value="{{ $parameter }}">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Nilai ({{ $config['satuan'] }})</label>
                                    <input type="number" step="0.0001" name="rows[{{ $jenis }}_{{ $parameter }}][nilai]" value="{{ old('rows.' . $jenis . '_' . $parameter . '.nilai') }}" placeholder="0.0000" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <input type="hidden" name="rows[{{ $jenis }}_{{ $parameter }}][satuan]" value="{{ $config['satuan'] }}">
                                    <div><label class="block text-xs font-medium text-gray-700 mb-1">Operator</label>@if(!empty($operatorOptions))<select name="rows[{{ $jenis }}_{{ $parameter }}][operator]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">-- Pilih Operator --</option>@foreach($operatorOptions as $operator)<option value="{{ $operator }}">{{ $operator }}</option>@endforeach</select>@else<input type="text" name="rows[{{ $jenis }}_{{ $parameter }}][operator]" placeholder="Nama operator" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">@endif</div>
                                    <div><label class="block text-xs font-medium text-gray-700 mb-1">Sampel Boy</label>@if(!empty($sampleBoyOptions))<select name="rows[{{ $jenis }}_{{ $parameter }}][sampel_boy]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">-- Pilih Sampel Boy --</option>@foreach($sampleBoyOptions as $sampleBoy)<option value="{{ $sampleBoy }}">{{ $sampleBoy }}</option>@endforeach</select>@else<input type="text" name="rows[{{ $jenis }}_{{ $parameter }}][sampel_boy]" value="{{ Auth::user()?->name }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">@endif</div>
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700"><input type="checkbox" name="rows[{{ $jenis }}_{{ $parameter }}][pengulangan]" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600"><span>Data sampel ulang</span></label>
                                    <textarea name="rows[{{ $jenis }}_{{ $parameter }}][remarks]" rows="2" placeholder="Remarks" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex flex-wrap justify-end gap-3 pt-6 border-t border-gray-200"><a href="{{ route('kernel.boiler-softener.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">Batal</a><button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">Simpan Semua Data</button></div>
        </form>
    </x-ui.card>
    <script>document.getElementById('boilerSoftenerForm')?.addEventListener('submit', async function(event) { event.preventDefault(); if (await window.confirmSave(this)) this.submit(); });</script>
</x-layouts.app>
