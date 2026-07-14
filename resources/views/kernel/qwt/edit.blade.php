<x-layouts.app title="Edit Data QWT Fibre Press">
    <x-ui.card title="Edit Data QWT Fibre Press">
        <form action="{{ route('kernel.qwt.update', $kernelQwt->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="kode" class="block text-sm font-medium text-gray-700 mb-2">Kode <span
                            class="text-red-500">*</span></label>
                    <select name="kode" id="kode"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('kode') border-red-400 bg-red-50 @enderror">
                        <option value="">-- Pilih Kode --</option>
                        @foreach($kodeOptions as $kodeValue => $kodeLabel)
                            <option value="{{ $kodeValue }}" {{ old('kode', $kernelQwt->kode) == $kodeValue ? 'selected' : '' }}>{{ $kodeValue }} - {{ $kodeLabel }}</option>
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
                            <option value="{{ $jenisValue }}" {{ old('jenis', $kernelQwt->jenis) == $jenisValue ? 'selected' : '' }}>{{ $jenisLabel }}</option>
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
                                <option value="{{ $operatorName }}" {{ old('operator', $kernelQwt->operator) == $operatorName ? 'selected' : '' }}>{{ $operatorName }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="operator" id="operator" value="{{ old('operator', $kernelQwt->operator) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('operator') border-red-400 bg-red-50 @enderror">
                    @endif
                    @error('operator')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sampel_boy" class="block text-sm font-medium text-gray-700 mb-2">Sampel Boy <span
                            class="text-red-500">*</span></label>
                    @if(!empty($sampleBoyOptions))
                        <select name="sampel_boy" id="sampel_boy"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                            <option value="">-- Pilih Sampel Boy --</option>
                            @foreach($sampleBoyOptions as $sampleBoyName)
                                <option value="{{ $sampleBoyName }}" {{ old('sampel_boy', $kernelQwt->sampel_boy) == $sampleBoyName ? 'selected' : '' }}>{{ $sampleBoyName }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="sampel_boy" id="sampel_boy"
                            value="{{ old('sampel_boy', $kernelQwt->sampel_boy) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                    @endif
                    @error('sampel_boy')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tanggal_sampel" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Sampel</label>
                    <input type="date" name="tanggal_sampel" id="tanggal_sampel"
                        value="{{ old('tanggal_sampel', $kernelQwt->rounded_time?->toDateString() ?? now()->toDateString()) }}"
                        max="{{ now()->toDateString() }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label for="rounded_time" class="block text-sm font-medium text-gray-700 mb-2">Jam Sampel</label>
                    <input type="time" name="rounded_time" id="rounded_time"
                        value="{{ old('rounded_time', $kernelQwt->rounded_time?->format('H:i') ?? now()->format('H:i')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div><label for="sampel_setelah_kuarter" class="block text-sm font-medium text-gray-700 mb-2">Sampel
                        Setelah Kuarter <span class="text-red-500">*</span></label><input type="number" step="0.0001"
                        name="sampel_setelah_kuarter" id="sampel_setelah_kuarter"
                        value="{{ old('sampel_setelah_kuarter', $kernelQwt->sampel_setelah_kuarter) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('sampel_setelah_kuarter') border-red-400 bg-red-50 @enderror">@error('sampel_setelah_kuarter')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="berat_nut_utuh" class="block text-sm font-medium text-gray-700 mb-2">Berat Nut Utuh
                        <span class="text-red-500">*</span></label><input type="number" step="0.0001"
                        name="berat_nut_utuh" id="berat_nut_utuh"
                        value="{{ old('berat_nut_utuh', $kernelQwt->berat_nut_utuh) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_nut_utuh') border-red-400 bg-red-50 @enderror">@error('berat_nut_utuh')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="berat_nut_pecah" class="block text-sm font-medium text-gray-700 mb-2">Berat Nut Pecah
                        <span class="text-red-500">*</span></label><input type="number" step="0.0001"
                        name="berat_nut_pecah" id="berat_nut_pecah"
                        value="{{ old('berat_nut_pecah', $kernelQwt->berat_nut_pecah) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_nut_pecah') border-red-400 bg-red-50 @enderror">@error('berat_nut_pecah')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="berat_kernel_utuh" class="block text-sm font-medium text-gray-700 mb-2">Berat Kernel
                        Utuh <span class="text-red-500">*</span></label><input type="number" step="0.0001"
                        name="berat_kernel_utuh" id="berat_kernel_utuh"
                        value="{{ old('berat_kernel_utuh', $kernelQwt->berat_kernel_utuh) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_kernel_utuh') border-red-400 bg-red-50 @enderror">@error('berat_kernel_utuh')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="berat_kernel_pecah" class="block text-sm font-medium text-gray-700 mb-2">Berat Kernel
                        Pecah <span class="text-red-500">*</span></label><input type="number" step="0.0001"
                        name="berat_kernel_pecah" id="berat_kernel_pecah"
                        value="{{ old('berat_kernel_pecah', $kernelQwt->berat_kernel_pecah) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_kernel_pecah') border-red-400 bg-red-50 @enderror">@error('berat_kernel_pecah')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="berat_cangkang" class="block text-sm font-medium text-gray-700 mb-2">Berat Cangkang
                        <span class="text-red-500">*</span></label><input type="number" step="0.0001"
                        name="berat_cangkang" id="berat_cangkang"
                        value="{{ old('berat_cangkang', $kernelQwt->berat_cangkang) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_cangkang') border-red-400 bg-red-50 @enderror">@error('berat_cangkang')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="berat_batu" class="block text-sm font-medium text-gray-700 mb-2">Berat Batu <span
                            class="text-red-500">*</span></label><input type="number" step="0.0001" name="berat_batu"
                        id="berat_batu" value="{{ old('berat_batu', $kernelQwt->berat_batu) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_batu') border-red-400 bg-red-50 @enderror">@error('berat_batu')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="ampere_screw" class="block text-sm font-medium text-gray-700 mb-2">Ampere Screw <span
                            class="text-red-500">*</span></label><input type="number" step="0.0001" name="ampere_screw"
                        id="ampere_screw" value="{{ old('ampere_screw', $kernelQwt->ampere_screw) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('ampere_screw') border-red-400 bg-red-50 @enderror">@error('ampere_screw')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="tekanan_hydraulic" class="block text-sm font-medium text-gray-700 mb-2">Tekanan
                        Hydraulic <span class="text-red-500">*</span></label><input type="number" step="0.0001"
                        name="tekanan_hydraulic" id="tekanan_hydraulic"
                        value="{{ old('tekanan_hydraulic', $kernelQwt->tekanan_hydraulic) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('tekanan_hydraulic') border-red-400 bg-red-50 @enderror">@error('tekanan_hydraulic')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('kernel.qwt.index') }}"
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
        });
    </script>
</x-layouts.app>