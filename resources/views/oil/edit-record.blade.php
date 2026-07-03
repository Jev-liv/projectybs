<x-layouts.app title="Edit Data Jenis Sampel">

    @php
        $sampleDate = old('tanggal_sampel', $oilRecord->tanggal_sampel?->format('Y-m-d') ?? now()->toDateString());
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

    {{-- Breadcrumb / Back Button --}}
    <div class="mb-6">
        <a href="{{ route('oil.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Daftar Data Oil Losses
        </a>
    </div>

    {{-- Form Edit Data Jenis Sampel --}}
    <x-ui.card title="Edit Data Jenis Sampel">
        <form action="{{ route('oil.records.update', $oilRecord->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Info: Data Created --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-blue-800">
                            <strong>Data dibuat:</strong> {{ $oilRecord->created_at->format('d/m/Y H:i') }} oleh
                            {{ $oilRecord->user->name }}
                        </p>
                        <p class="text-sm text-blue-800 mt-1">
                            <strong>Office:</strong> {{ $oilRecord->office }}
                        </p>
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

            {{-- Form Fields --}}
            <div class="space-y-4">
                {{-- Kode --}}
                <div>
                    <label for="kode" class="block text-sm font-medium text-gray-700 mb-2">
                        Kode <span class="text-red-500">*</span>
                    </label>
                    <select name="kode" id="kode" class="w-full select2-kode
                            @error('kode') border-red-400 bg-red-50 @enderror" required>
                        <option value="">-- Pilih Kode --</option>
                        @foreach($kodeOptions as $kodeValue => $kodeLabel)
                            <option value="{{ $kodeValue }}" {{ old('kode', $oilRecord->kode) == $kodeValue ? 'selected' : '' }}>
                                {{ $kodeLabel }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Format tampilan: Kode - Pivot. Pivot hanya untuk informasi.
                    </p>
                    @error('kode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Jenis --}}
                <div>
                    <label for="jenis" class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis <span class="text-red-500">*</span>
                    </label>
                    <select name="jenis" id="jenis" class="w-full select2-jenis
                            @error('jenis') border-red-400 bg-red-50 @enderror" required>
                        @foreach($jenisOptions as $jenisValue => $jenisLabel)
                            <option value="{{ $jenisValue }}" {{ old('jenis', $oilRecord->jenis) == $jenisValue ? 'selected' : '' }}>
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
                            Operator <span class="text-red-500">*</span>
                        </label>
                        @if(!empty($operatorOptions))
                            <select name="operator" id="operator" class="w-full select2-operator
                                            @error('operator') border-red-400 bg-red-50 @enderror" required>
                                <option value="">-- Pilih Operator --</option>
                                @foreach($operatorOptions as $operatorName)
                                    <option value="{{ $operatorName }}" {{ old('operator', $oilRecord->operator) == $operatorName ? 'selected' : '' }}>
                                        {{ $operatorName }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Daftar operator mengikuti office
                                {{ $oilRecord->office ?? '-' }}.
                            </p>
                        @else
                            <input type="text" name="operator" id="operator"
                                value="{{ old('operator', $oilRecord->operator) }}" placeholder="Nama operator" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500
                                                   @error('operator') border-red-400 bg-red-50 @enderror" required>
                            <p class="mt-1 text-xs text-gray-500">Dropdown operator belum tersedia untuk office
                                {{ $oilRecord->office ?? '-' }}.
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
                                    <option value="{{ $sampleBoyName }}" {{ old('sampel_boy', $oilRecord->sampel_boy) == $sampleBoyName ? 'selected' : '' }}>
                                        {{ $sampleBoyName }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="sampel_boy" id="sampel_boy"
                                value="{{ old('sampel_boy', $oilRecord->sampel_boy) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm @error('sampel_boy') border-red-400 bg-red-50 @enderror">
                        @endif
                        @error('sampel_boy')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
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
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">
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
                placeholder: '-- Pilih Kode --',
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
                placeholder: '-- Pilih Jenis --',
                allowClear: false,
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

            // Initialize Select2 for Operator dropdown
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

</x-layouts.app>