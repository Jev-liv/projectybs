<x-layouts.app title="Edit Data Kernel Losses">
    <x-ui.card title="Edit Data Kernel Losses">
        <form action="{{ route('kernel.update', $kernelCalculation->id) }}" method="POST" class="space-y-5">
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
                            <option value="{{ $kodeValue }}" {{ old('kode', $kernelCalculation->kode) == $kodeValue ? 'selected' : '' }}>{{ $kodeLabel }}</option>
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
                            <option value="{{ $jenisValue }}" {{ old('jenis', $kernelCalculation->jenis) == $jenisValue ? 'selected' : '' }}>{{ $jenisLabel }}</option>
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
                                <option value="{{ $operatorName }}" {{ old('operator', $kernelCalculation->operator) == $operatorName ? 'selected' : '' }}>{{ $operatorName }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="operator" id="operator"
                            value="{{ old('operator', $kernelCalculation->operator) }}"
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
                                <option value="{{ $sampleBoyName }}" {{ old('sampel_boy', $kernelCalculation->sampel_boy) == $sampleBoyName ? 'selected' : '' }}>{{ $sampleBoyName }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="sampel_boy" id="sampel_boy"
                            value="{{ old('sampel_boy', $kernelCalculation->sampel_boy) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                    @endif
                    @error('sampel_boy')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="berat_sampel" class="block text-sm font-medium text-gray-700 mb-2">Berat Sampel (gram)
                        <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="berat_sampel" id="berat_sampel"
                        value="{{ old('berat_sampel', $kernelCalculation->berat_sampel) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('berat_sampel') border-red-400 bg-red-50 @enderror">
                    @error('berat_sampel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nut_utuh_nut" class="block text-sm font-medium text-gray-700 mb-2">Nut Utuh - Nut (gram)
                        <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="nut_utuh_nut" id="nut_utuh_nut"
                        value="{{ old('nut_utuh_nut', $kernelCalculation->nut_utuh_nut) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('nut_utuh_nut') border-red-400 bg-red-50 @enderror">
                    @error('nut_utuh_nut')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nut_utuh_kernel" class="block text-sm font-medium text-gray-700 mb-2">Nut Utuh - Kernel
                        (gram) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="nut_utuh_kernel" id="nut_utuh_kernel"
                        value="{{ old('nut_utuh_kernel', $kernelCalculation->nut_utuh_kernel) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('nut_utuh_kernel') border-red-400 bg-red-50 @enderror">
                    @error('nut_utuh_kernel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nut_pecah_nut" class="block text-sm font-medium text-gray-700 mb-2">Nut Pecah - Nut
                        (gram) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="nut_pecah_nut" id="nut_pecah_nut"
                        value="{{ old('nut_pecah_nut', $kernelCalculation->nut_pecah_nut) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('nut_pecah_nut') border-red-400 bg-red-50 @enderror">
                    @error('nut_pecah_nut')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nut_pecah_kernel" class="block text-sm font-medium text-gray-700 mb-2">Nut Pecah -
                        Kernel (gram) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="nut_pecah_kernel" id="nut_pecah_kernel"
                        value="{{ old('nut_pecah_kernel', $kernelCalculation->nut_pecah_kernel) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('nut_pecah_kernel') border-red-400 bg-red-50 @enderror">
                    @error('nut_pecah_kernel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="kernel_utuh" class="block text-sm font-medium text-gray-700 mb-2">Kernel Utuh (gram)
                        <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="kernel_utuh" id="kernel_utuh"
                        value="{{ old('kernel_utuh', $kernelCalculation->kernel_utuh) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('kernel_utuh') border-red-400 bg-red-50 @enderror">
                    @error('kernel_utuh')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="kernel_pecah" class="block text-sm font-medium text-gray-700 mb-2">Kernel Pecah (gram)
                        <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" name="kernel_pecah" id="kernel_pecah"
                        value="{{ old('kernel_pecah', $kernelCalculation->kernel_pecah) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('kernel_pecah') border-red-400 bg-red-50 @enderror">
                    @error('kernel_pecah')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('kernel.index') }}"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">Batal</a>
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">Simpan
                    Perubahan</button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>