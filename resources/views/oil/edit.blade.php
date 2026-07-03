<x-layouts.app title="Edit Data Oil Losses">

    @php
        $sampleDate = old('tanggal_sampel', $oilLoss->tanggal_sampel?->format('Y-m-d') ?? now()->toDateString());
    @endphp

    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    {{-- Custom Select2 Styling to Match Standard Inputs --}}
    <style>
        /* Match Select2 container with Tailwind input styling */
        .select2-container--default .select2-selection--single {
            height: auto !important;
            padding: 0.5rem 1rem !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            font-size: 0.875rem !important;
            line-height: 1.25rem !important;
            transition: all 0.15s !important;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--focus .select2-selection--single {
            outline: none !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding: 0 !important;
            line-height: inherit !important;
            color: #374151 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 0.75rem !important;
        }

        /* Dropdown styling */
        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }

        .select2-results__option {
            padding: 0.5rem 1rem !important;
            font-size: 0.875rem !important;
        }

        .select2-results__option--highlighted {
            background-color: #3b82f6 !important;
        }

        /* Match error state */
        .select2-container--default.border-red-400 .select2-selection--single {
            border-color: #f87171 !important;
            background-color: #fef2f2 !important;
        }
    </style>

    {{-- Form Edit Data Oil Losses --}}
    <x-ui.card title="Edit Data Oil Losses">
        <form action="{{ route('oil.update', $oilLoss->id) }}" method="POST" id="labForm" class="space-y-6"
            onsubmit="return handleFormSubmit(event, this)">
            @csrf
            @method('PUT')

            {{-- Info: Tanggal & Jam (Read-Only, dari created_at) --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-amber-900 mb-1">⏰ Waktu Input Asli</h4>
                        <p class="text-sm text-amber-800 mb-2">
                            Tanggal & Jam diambil dari waktu input pertama kali dan tidak dapat diubah.
                        </p>
                        <div class="p-3 bg-white rounded border border-amber-200">
                            <div class="text-xs text-amber-700">Tanggal & Jam Input:</div>
                            <div class="text-lg font-bold text-amber-900">
                                {{ $oilLoss->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="tanggal_sampel" class="block text-sm font-medium text-gray-700 mb-2">
                    Tanggal Sampel
                </label>
                <input type="date" name="tanggal_sampel" id="tanggal_sampel" value="{{ $sampleDate }}"
                    max="{{ now()->toDateString() }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <hr class="my-6 border-gray-200">

            {{-- MODE 1: Non-Angka (Bisa Multiple per Day) --}}
            @php
                // Check if this record has a related OilRecord
                $relatedRecord = \App\Models\OilRecord::where('kode', $oilLoss->kode)
                    ->whereDate('created_at', $oilLoss->created_at->format('Y-m-d'))
                    ->first();
            @endphp

            <div id="mode1Section" class="border-2 border-blue-200 bg-blue-50 rounded-lg p-5">
                <div class="flex items-center mb-4">
                    <div
                        class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">
                        1
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        Mode Non-Angka <span class="text-sm text-gray-600 font-normal">(Data Operator & Sampel
                            Boy)</span>
                    </h3>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="kode" class="block text-sm font-medium text-gray-700 mb-2">
                            Kode <span class="text-gray-400 text-xs">(Ketik untuk cari)</span>
                        </label>
                        <select name="kode" id="kode" class="w-full select2-kode
                                @error('kode') border-red-400 bg-red-50 @enderror">
                            <option value="">-- Pilih atau ketik untuk cari --</option>
                            @foreach($kodeOptions as $kodeValue => $kodeLabel)
                                <option value="{{ $kodeValue }}" {{ (old('kode', $relatedRecord?->kode ?? $oilLoss->kode) == $kodeValue) ? 'selected' : '' }}>
                                    {{ $kodeLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Format tampilan: Kode - Pivot. Pivot hanya untuk
                            informasi.</p>
                        @error('kode')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Jenis (Searchable Dropdown) --}}
                    <div>
                        <label for="jenis" class="block text-sm font-medium text-gray-700 mb-2">
                            Jenis <span class="text-gray-400 text-xs">(Ketik untuk cari)</span>
                        </label>
                        <select name="jenis" id="jenis" class="w-full select2-jenis
                                @error('jenis') border-red-400 bg-red-50 @enderror">
                            <option value="">-- Pilih atau ketik untuk cari --</option>
                            @foreach($jenisOptions as $jenisValue => $jenisLabel)
                                <option value="{{ $jenisValue }}" {{ (old('jenis', $relatedRecord?->jenis) == $jenisValue) ? 'selected' : '' }}>
                                    {{ $jenisLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('jenis')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Operator & Sampel Boy --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="operator" class="block text-sm font-medium text-gray-700 mb-2">
                                Operator
                            </label>
                            @if(!empty($operatorOptions))
                                <select name="operator" id="operator" class="w-full select2-operator
                                                    @error('operator') border-red-400 bg-red-50 @enderror">
                                    <option value="">-- Pilih Operator --</option>
                                    @foreach($operatorOptions as $operatorName)
                                        <option value="{{ $operatorName }}" {{ old('operator', $relatedRecord?->operator) == $operatorName ? 'selected' : '' }}>
                                            {{ $operatorName }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Daftar operator mengikuti office
                                    {{ $oilLoss->office ?? '-' }}.
                                </p>
                            @else
                                <input type="text" name="operator" id="operator"
                                    value="{{ old('operator', $relatedRecord?->operator) }}" placeholder="Nama operator"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500
                                                           @error('operator') border-red-400 bg-red-50 @enderror">
                                <p class="mt-1 text-xs text-gray-500">Dropdown operator belum tersedia untuk office
                                    {{ $oilLoss->office ?? '-' }}.
                                </p>
                            @endif
                            @error('operator')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="sampel_boy" class="block text-sm font-medium text-gray-700 mb-2">
                                Sampel Boy
                            </label>
                            @if(!empty($sampleBoyOptions))
                                <select name="sampel_boy" id="sampel_boy" class="w-full select2-sampel-boy
                                                   @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                                    <option value="">-- Pilih Sampel Boy --</option>
                                    @foreach($sampleBoyOptions as $sampleBoyName)
                                        <option value="{{ $sampleBoyName }}" {{ old('sampel_boy', $relatedRecord?->sampel_boy) == $sampleBoyName ? 'selected' : '' }}>
                                            {{ $sampleBoyName }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="sampel_boy" id="sampel_boy"
                                    value="{{ old('sampel_boy', $relatedRecord?->sampel_boy) }}"
                                    placeholder="Nama sampel boy" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500
                                               @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                            @endif
                            @error('sampel_boy')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- MODE 2: Angka --}}
            <div id="mode2Section" class="border-2 border-green-200 bg-green-50 rounded-lg p-5">
                <div class="flex items-center mb-4">
                    <div
                        class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold mr-3">
                        2
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        Mode Angka <span class="text-sm text-gray-600 font-normal">(Data Perhitungan)</span>
                    </h3>
                </div>

                <div class="space-y-4">
                    {{-- Row 1: Cawan Kosong, Berat Basah, Cawan Sample Kering --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="cawan_kosong" class="block text-sm font-medium text-gray-700 mb-2">
                                Cawan Kosong <span class="text-gray-400 text-xs">(gram)</span>
                            </label>
                            <input type="number" step="0.000001" name="cawan_kosong" id="cawan_kosong"
                                value="{{ old('cawan_kosong', $oilLoss->cawan_kosong) }}" placeholder="0.000000" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500
                                       @error('cawan_kosong') border-red-400 bg-red-50 @enderror">
                            @error('cawan_kosong')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="berat_basah" class="block text-sm font-medium text-gray-700 mb-2">
                                Berat Sampel Basah <span class="text-gray-400 text-xs">(gram)</span>
                            </label>
                            <input type="number" step="0.000001" name="berat_basah" id="berat_basah"
                                value="{{ old('berat_basah', $oilLoss->berat_basah) }}" placeholder="0.000000" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500
                                       @error('berat_basah') border-red-400 bg-red-50 @enderror">
                            @error('berat_basah')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cawan_sample_kering" class="block text-sm font-medium text-gray-700 mb-2">
                                Cawan + Sample Kering <span class="text-gray-400 text-xs">(gram)</span>
                            </label>
                            <input type="number" step="0.000001" name="cawan_sample_kering" id="cawan_sample_kering"
                                value="{{ old('cawan_sample_kering', $oilLoss->cawan_sample_kering) }}"
                                placeholder="0.000000" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500
                                       @error('cawan_sample_kering') border-red-400 bg-red-50 @enderror">
                            @error('cawan_sample_kering')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Row 2: Labu Kosong, Oil Labu --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="labu_kosong" class="block text-sm font-medium text-gray-700 mb-2">
                                Labu Kosong <span class="text-gray-400 text-xs">(gram)</span>
                            </label>
                            <input type="number" step="0.000001" name="labu_kosong" id="labu_kosong"
                                value="{{ old('labu_kosong', $oilLoss->labu_kosong) }}" placeholder="0.000000" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500
                                       @error('labu_kosong') border-red-400 bg-red-50 @enderror">
                            @error('labu_kosong')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="oil_labu" class="block text-sm font-medium text-gray-700 mb-2">
                                Oil + Labu <span class="text-gray-400 text-xs">(gram)</span>
                            </label>
                            <input type="number" step="0.000001" name="oil_labu" id="oil_labu"
                                value="{{ old('oil_labu', $oilLoss->oil_labu) }}" placeholder="0.000000" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500
                                       @error('oil_labu') border-red-400 bg-red-50 @enderror">
                            @error('oil_labu')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="{{ route('oil.index') }}"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">
                    Batal
                </a>

                <button type="submit"
                    class="px-5 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition font-medium shadow-sm">
                    <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Update Data
                </button>
            </div>
        </form>
    </x-ui.card>

    {{-- Select2 JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 for Kode dropdown
            $('.select2-kode').select2({
                placeholder: '-- Pilih atau ketik untuk cari --',
                allowClear: true,
                width: '100%',
                theme: 'default',
                language: {
                    noResults: function () {
                        return "Tidak ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });

            // Initialize Select2 for Jenis dropdown
            $('.select2-jenis').select2({
                placeholder: '-- Pilih atau ketik untuk cari --',
                allowClear: true,
                width: '100%',
                theme: 'default',
                language: {
                    noResults: function () {
                        return "Tidak ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });

            $('.select2-operator').select2({
                placeholder: '-- Pilih Operator --',
                allowClear: true,
                width: '100%',
                theme: 'default',
                language: {
                    noResults: function () {
                        return "Tidak ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });

            $('.select2-sampel-boy').select2({
                placeholder: '-- Pilih Sampel Boy --',
                allowClear: true,
                width: '100%',
                theme: 'default',
                language: {
                    noResults: function () {
                        return "Tidak ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });
        });
    </script>

    <script>
        // Confirmation handler for update form submission
        async function handleFormSubmit(event, form) {
            event.preventDefault();
            const confirmed = await window.confirmUpdate(form); // Pass form element untuk offline save
            if (confirmed) {
                if (typeof window.lockFormSubmission === 'function') {
                    const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                    window.lockFormSubmission(form, submitter);
                }
                form.submit();
            }
            return false;
        }
    </script>

</x-layouts.app>