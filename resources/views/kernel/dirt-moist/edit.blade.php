<x-layouts.app title="Edit Data Dirt & Moist">
    <x-ui.card title="Edit Data Dirt & Moist">
        <form action="{{ route('kernel.dirt-moist.update', $dirtMoistCalculation->id) }}" method="POST"
            class="space-y-5">
            @csrf
            @method('PUT')

            @php
                $kode = strtoupper($dirtMoistCalculation->kode ?? '');
                $isInlet = str_starts_with($kode, 'IN');
                $isOutlet = str_starts_with($kode, 'OUT');
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="kode" class="block text-sm font-medium text-gray-700 mb-2">Kode <span
                            class="text-red-500">*</span></label>
                    <select name="kode" id="kode"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('kode') border-red-400 bg-red-50 @enderror">
                        <option value="">-- Pilih Kode --</option>
                        @foreach($kodeOptions as $kodeValue => $kodeLabel)
                            <option value="{{ $kodeValue }}" {{ old('kode', $dirtMoistCalculation->kode) == $kodeValue ? 'selected' : '' }}>{{ $kodeValue }} - {{ $kodeLabel }}</option>
                        @endforeach
                    </select>
                    @error('kode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jenis" class="block text-sm font-medium text-gray-700 mb-2">Jenis <span
                            class="text-red-500">*</span></label>
                    <select name="jenis" id="jenis"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('jenis') border-red-400 bg-red-50 @enderror">
                        @foreach($jenisOptions as $jenisValue => $jenisLabel)
                            <option value="{{ $jenisValue }}" {{ old('jenis', $dirtMoistCalculation->jenis) == $jenisValue ? 'selected' : '' }}>{{ $jenisLabel }}</option>
                        @endforeach
                    </select>
                    @error('jenis')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="operator" class="block text-sm font-medium text-gray-700 mb-2">Operator <span
                            class="text-red-500">*</span></label>
                    @if(!empty($operatorOptions))
                        <select name="operator" id="operator"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('operator') border-red-400 bg-red-50 @enderror">
                            <option value="">-- Pilih Operator --</option>
                            @foreach($operatorOptions as $operatorName)
                                <option value="{{ $operatorName }}" {{ old('operator', $dirtMoistCalculation->operator) == $operatorName ? 'selected' : '' }}>{{ $operatorName }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="operator" id="operator"
                            value="{{ old('operator', $dirtMoistCalculation->operator) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('operator') border-red-400 bg-red-50 @enderror">
                    @endif
                    @error('operator')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sampel_boy" class="block text-sm font-medium text-gray-700 mb-2">Sampel Boy</label>
                    @if(!empty($sampleBoyOptions))
                        <select name="sampel_boy" id="sampel_boy"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                            <option value="">-- Pilih Sampel Boy --</option>
                            @foreach($sampleBoyOptions as $sampleBoyName)
                                <option value="{{ $sampleBoyName }}" {{ old('sampel_boy', $dirtMoistCalculation->sampel_boy) == $sampleBoyName ? 'selected' : '' }}>{{ $sampleBoyName }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="sampel_boy" id="sampel_boy"
                            value="{{ old('sampel_boy', $dirtMoistCalculation->sampel_boy) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                    @endif
                    @error('sampel_boy')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tanggal_sampel" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Sampel</label>
                    <input type="date" name="tanggal_sampel" id="tanggal_sampel"
                        value="{{ old('tanggal_sampel', $dirtMoistCalculation->rounded_time?->toDateString() ?? now()->toDateString()) }}"
                        max="{{ now()->toDateString() }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label for="rounded_time" class="block text-sm font-medium text-gray-700 mb-2">Jam Sampel</label>
                    <input type="time" name="rounded_time" id="rounded_time"
                        value="{{ old('rounded_time', $dirtMoistCalculation->rounded_time?->format('H:i') ?? now()->format('H:i')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label for="berat_sampel" class="block text-sm font-medium text-gray-700 mb-2">Berat Sampel (gram)
                        <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="berat_sampel" id="berat_sampel"
                        value="{{ old('berat_sampel', $dirtMoistCalculation->berat_sampel) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_sampel') border-red-400 bg-red-50 @enderror">
                    @error('berat_sampel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div data-dm-dirty-field class="{{ $isOutlet ? 'hidden' : '' }}">
                    <label for="berat_dirty" class="block text-sm font-medium text-gray-700 mb-2">Berat Dirty (gram)
                        <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="berat_dirty" id="berat_dirty"
                        value="{{ old('berat_dirty', $dirtMoistCalculation->berat_dirty) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_dirty') border-red-400 bg-red-50 @enderror">
                    @error('berat_dirty')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 {{ $isOutlet ? '' : 'hidden' }}" data-dm-moist-field>
                        <label for="moist_percent" class="block text-sm font-medium text-gray-700 mb-2">Moist (%) <span
                                class="text-red-500">*</span></label>
                        <input type="number" step="0.0001" name="moist_percent" id="moist_percent"
                            value="{{ old('moist_percent', $dirtMoistCalculation->moist_percent) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('moist_percent') border-red-400 bg-red-50 @enderror">
                        @error('moist_percent')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('kernel.dirt-moist.index') }}"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">Batal</a>
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">Simpan
                    Perubahan</button>
            </div>
        </form>
    </x-ui.card>

    <style>
        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    <script>
        function preventNumberWheel(scope = document) {
            scope.querySelectorAll('input[type="number"]').forEach((input) => {
                input.addEventListener('wheel', function (event) {
                    event.preventDefault();
                }, { passive: false });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            preventNumberWheel();

            const kodeSelect = document.getElementById('kode');
            const dirtyField = document.querySelector('[data-dm-dirty-field]');
            const moistField = document.querySelector('[data-dm-moist-field]');

            function syncDirtMoistFields() {
                const kode = (kodeSelect?.value || '').toUpperCase();
                const isOutlet = kode.startsWith('OUT');

                dirtyField?.classList.toggle('hidden', isOutlet);
                moistField?.classList.toggle('hidden', !isOutlet);
            }

            kodeSelect?.addEventListener('change', syncDirtMoistFields);
            syncDirtMoistFields();
        });
    </script>
</x-layouts.app>