<x-layouts.app title="Input Data Oil Losses Oil Losses">

    @php
        $defaultSampleDate = now()->toDateString();
        $defaultSampleTime = now()->format('H:i');
        $isYbsOffice = Auth::user()?->office === 'YBS';
        $oldMode1Rows = old('mode1_rows', []);
        $oldMode2Rows = old('mode2_rows', []);
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

        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    {{-- Form Input Data Oil Losses dengan Dual-Mode --}}
    <x-ui.card title="Input Data Oil Losses Oil Losses">
        <form action="{{ route('oil.store') }}" method="POST" id="labForm" class="space-y-6"
            onsubmit="return handleFormSubmit(event, this)">
            @csrf

            @if($isYbsOffice)
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                    <div class="text-sm text-indigo-900 font-semibold mb-1">Tanggal dan jam diisi manual per kartu.</div>
                    <div class="text-sm text-indigo-800">Silakan lengkapi tanggal sampel dan jam sampel di kartu yang
                        dipakai.</div>
                </div>
            @endif

            <hr class="my-6 border-gray-200">

            {{-- MODE 1: Non-Angka --}}
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
                        1
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Mode Non-Angka</h3>
                        <p class="text-sm text-gray-600">Kartu mesin ditampilkan langsung per office. Kartu kosong
                            diabaikan, kartu yang diisi wajib lengkap.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-4" id="mode1RowsContainer">
                    @foreach($kodeOptions as $kodeValue => $kodeLabel)
                        @php $rowIndex = $loop->index; @endphp
                        <div class="mode1-row oil-machine-card rounded-xl border border-blue-200 bg-blue-50/70 p-3 space-y-3"
                            data-row-type="mode1">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-blue-900 leading-tight">{{ $kodeLabel }}</h4>
                                    <p class="text-[11px] text-blue-700">Kode: {{ $kodeValue }}</p>
                                </div>
                                <span
                                    class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">Non-Angka</span>
                            </div>

                            <input type="hidden" name="mode1_rows[{{ $rowIndex }}][kode]" value="{{ $kodeValue }}"
                                class="mode1-code-field" disabled>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Tanggal Sampel
                                </label>
                                <input type="date" name="mode1_rows[{{ $rowIndex }}][tanggal_sampel]"
                                    value="{{ $oldMode1Rows[$rowIndex]['tanggal_sampel'] ?? $defaultSampleDate }}"
                                    max="{{ $defaultSampleDate }}"
                                    class="mode1-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            @if($isYbsOffice)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                        Jam Sampel
                                    </label>
                                    <input type="time" name="mode1_rows[{{ $rowIndex }}][jam_sampel]"
                                        value="{{ $oldMode1Rows[$rowIndex]['jam_sampel'] ?? $defaultSampleTime }}"
                                        class="mode1-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Jenis
                                </label>
                                <select name="mode1_rows[{{ $rowIndex }}][jenis]"
                                    class="mode1-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @foreach($jenisOptions as $jenisValue => $jenisLabel)
                                        <option value="{{ $jenisValue }}" {{ ($oldMode1Rows[$rowIndex]['jenis'] ?? 'TBS') == $jenisValue ? 'selected' : '' }}>
                                            {{ $jenisLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Operator
                                </label>
                                @if(!empty($operatorOptions))
                                    <select name="mode1_rows[{{ $rowIndex }}][operator]"
                                        class="select2-operator mode1-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Pilih Operator --</option>
                                        @foreach($operatorOptions as $operatorName)
                                            <option value="{{ $operatorName }}" {{ ($oldMode1Rows[$rowIndex]['operator'] ?? '') == $operatorName ? 'selected' : '' }}>
                                                {{ $operatorName }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" name="mode1_rows[{{ $rowIndex }}][operator]"
                                        value="{{ $oldMode1Rows[$rowIndex]['operator'] ?? '' }}" placeholder="Nama operator"
                                        class="mode1-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Sampel Boy
                                </label>
                                @if(!empty($sampleBoyOptions))
                                    <select name="mode1_rows[{{ $rowIndex }}][sampel_boy]"
                                        class="select2-sampel-boy mode1-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Pilih Sampel Boy --</option>
                                        @foreach($sampleBoyOptions as $sampleBoyName)
                                            <option value="{{ $sampleBoyName }}" {{ ($oldMode1Rows[$rowIndex]['sampel_boy'] ?? '') == $sampleBoyName ? 'selected' : '' }}>
                                                {{ $sampleBoyName }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" name="mode1_rows[{{ $rowIndex }}][sampel_boy]"
                                        value="{{ Auth::user()->name }}" readonly
                                        class="w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-600">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- MODE 2: Angka (Phase-Based) --}}
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">
                        2
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Mode Angka</h3>
                        <p class="text-sm text-gray-600">Kartu mesin ditampilkan langsung per office. Jika kartu diisi,
                            seluruh field wajib lengkap.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-4" id="mode2RowsContainer">
                    @foreach($kodeOptions as $kodeValue => $kodeLabel)
                        @php $rowIndex = $loop->index; @endphp
                        <div class="mode2-row oil-machine-card rounded-xl border border-green-200 bg-green-50/70 p-3 space-y-3"
                            data-row-type="mode2">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-green-900 leading-tight">{{ $kodeLabel }}</h4>
                                    <p class="text-[11px] text-green-700">Kode: {{ $kodeValue }}</p>
                                </div>
                                <span
                                    class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">Angka</span>
                            </div>

                            <input type="hidden" name="mode2_rows[{{ $rowIndex }}][kode_mode2]" value="{{ $kodeValue }}"
                                class="mode2-code-field" disabled>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Tanggal Sampel
                                </label>
                                <input type="date" name="mode2_rows[{{ $rowIndex }}][tanggal_sampel]"
                                    value="{{ $oldMode2Rows[$rowIndex]['tanggal_sampel'] ?? $defaultSampleDate }}"
                                    max="{{ $defaultSampleDate }}"
                                    class="mode2-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>

                            @if($isYbsOffice)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                        Jam Sampel
                                    </label>
                                    <input type="time" name="mode2_rows[{{ $rowIndex }}][jam_sampel]"
                                        value="{{ $oldMode2Rows[$rowIndex]['jam_sampel'] ?? $defaultSampleTime }}"
                                        class="mode2-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            @endif

                            <div>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Cawan Kosong</label>
                                        <input type="number" step="0.000001"
                                            name="mode2_rows[{{ $rowIndex }}][cawan_kosong]"
                                            value="{{ $oldMode2Rows[$rowIndex]['cawan_kosong'] ?? '' }}"
                                            placeholder="0.000000"
                                            class="mode2-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Berat Sampel
                                            Basah</label>
                                        <input type="number" step="0.000001" name="mode2_rows[{{ $rowIndex }}][berat_basah]"
                                            value="{{ $oldMode2Rows[$rowIndex]['berat_basah'] ?? '' }}"
                                            placeholder="0.000000"
                                            class="mode2-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Labu Kosong</label>
                                        <input type="number" step="0.000001" name="mode2_rows[{{ $rowIndex }}][labu_kosong]"
                                            value="{{ $oldMode2Rows[$rowIndex]['labu_kosong'] ?? '' }}"
                                            placeholder="0.000000"
                                            class="mode2-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Cawan + Sample
                                            Kering</label>
                                        <input type="number" step="0.000001"
                                            name="mode2_rows[{{ $rowIndex }}][cawan_sample_kering]"
                                            value="{{ $oldMode2Rows[$rowIndex]['cawan_sample_kering'] ?? '' }}"
                                            placeholder="0.000000"
                                            class="mode2-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Oil + Labu</label>
                                        <input type="number" step="0.000001" name="mode2_rows[{{ $rowIndex }}][oil_labu]"
                                            value="{{ $oldMode2Rows[$rowIndex]['oil_labu'] ?? '' }}" placeholder="0.000000"
                                            class="mode2-user-field w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Action Buttons --}}
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="{{ route('oil.index') }}"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">
                    Batal
                </a>

                <button type="reset"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">
                    Reset Form
                </button>

                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">
                    <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    Simpan Data
                </button>
            </div>
        </form>
    </x-ui.card>

    {{-- Select2 JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            const defaultSampleDate = @json($defaultSampleDate);
            const defaultSampleTime = @json($defaultSampleTime);

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
                placeholder: 'TBS (Default)',
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
                minimumInputLength: 0,
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
                minimumInputLength: 0,
                language: {
                    noResults: function () {
                        return "Tidak ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });

            // Live Clock - Update current date/time display every second
            function updateClock() {
                const now = new Date();
                const options = {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                };
                const formattedTime = now.toLocaleString('id-ID', options).replace(',', '');
                const clockEl = document.getElementById('currentDateTime');
                if (clockEl) {
                    clockEl.textContent = formattedTime;
                }
            }

            // Update clock immediately and then every second
            updateClock();
            setInterval(updateClock, 1000);

            function preventNumberWheel(scope = document) {
                scope.querySelectorAll('input[type="number"]').forEach((input) => {
                    input.addEventListener('wheel', function (event) {
                        event.preventDefault();
                    }, { passive: false });
                });
            }

            function getTrimmedValue(element) {
                if (!element) {
                    return '';
                }

                return String(element.value || '').trim();
            }

            function syncMachineCard(card, codeSelector, userFieldSelector) {
                const codeField = card.querySelector(codeSelector);
                const userFields = Array.from(card.querySelectorAll(userFieldSelector));
                const hasUserValue = userFields.some((field) => {
                    const value = getTrimmedValue(field);

                    if (!value) {
                        return false;
                    }

                    if (field.type === 'date' && value === defaultSampleDate) {
                        return false;
                    }

                    if (field.type === 'time' && value === defaultSampleTime) {
                        return false;
                    }

                    if (card.classList.contains('mode1-row') && field.name && field.name.endsWith('[jenis]') && value === 'TBS') {
                        return false;
                    }

                    return true;
                });

                if (codeField) {
                    codeField.disabled = !hasUserValue;
                }

                return hasUserValue;
            }

            function syncAllMachineCards() {
                document.querySelectorAll('.mode1-row').forEach((card) => {
                    syncMachineCard(card, '.mode1-code-field', '.mode1-user-field');
                });

                document.querySelectorAll('.mode2-row').forEach((card) => {
                    syncMachineCard(card, '.mode2-code-field', '.mode2-user-field');
                });
            }

            function detectMode2Phase() {
                const hasStep1 = ['cawan_kosong', 'berat_basah', 'labu_kosong'].some((field) => {
                    const value = $(`#${field}`).val();
                    return value && value.trim() !== '';
                });

                const hasStep2 = (() => {
                    const value = $('#cawan_sample_kering').val();
                    return value && value.trim() !== '';
                })();

                const hasFinal = (() => {
                    const value = $('#oil_labu').val();
                    return value && value.trim() !== '';
                })();

                if (hasFinal) {
                    return 'complete';
                }

                if (hasStep2) {
                    return 'final';
                }

                if (hasStep1) {
                    return 'initial';
                }

                return 'initial';
            }

            function getPhase() {
                return detectMode2Phase();
            }

            function togglePhaseFields() {
                const phase = getPhase();
                const initialFields = $('#initialPhaseFields').find('input');
                const middleFields = $('#middlePhaseFields').find('input');
                const finalFields = $('#finalPhaseFields').find('input');

                // Jangan lock field: user bebas isi awal/akhir, phase dideteksi otomatis.
                initialFields.prop('disabled', false);
                middleFields.prop('disabled', false);
                finalFields.prop('disabled', false);

                $('#initialPhaseFields, #middlePhaseFields, #finalPhaseFields').removeClass('ring-2 ring-green-200');
                if (phase === 'initial') {
                    $('#initialPhaseFields').addClass('ring-2 ring-green-200');
                } else if (phase === 'final') {
                    $('#middlePhaseFields').addClass('ring-2 ring-green-200');
                } else {
                    $('#initialPhaseFields, #middlePhaseFields, #finalPhaseFields').addClass('ring-2 ring-green-200');
                }
            }

            $('#initialPhaseFields input, #middlePhaseFields input, #finalPhaseFields input').on('input change', togglePhaseFields);
            togglePhaseFields();

            document.querySelectorAll('.mode1-row .mode1-user-field, .mode2-row .mode2-user-field').forEach((field) => {
                field.addEventListener('input', syncAllMachineCards);
                field.addEventListener('change', syncAllMachineCards);
            });

            document.getElementById('labForm')?.addEventListener('reset', function () {
                window.setTimeout(syncAllMachineCards, 0);
            });

            preventNumberWheel();
            syncAllMachineCards();

            // Form validation: Jika 1 field di mode diisi, semua field di mode tersebut WAJIB diisi
            $('#labForm').on('submit', function (e) {
                let hasError = false;
                let errorMessages = [];
                const phase = getPhase();

                syncAllMachineCards();

                const clearDynamicRowVisualErrors = () => {
                    document.querySelectorAll('.mode1-row, .mode2-row').forEach((rowEl) => {
                        rowEl.classList.remove('ring-2', 'ring-red-200', 'border-red-400');
                        rowEl.querySelectorAll('input, select, textarea').forEach((el) => {
                            el.classList.remove('border-red-400', 'bg-red-50');
                        });
                    });
                };

                const markDynamicRowError = (rowEl, fields = []) => {
                    rowEl.classList.add('ring-2', 'ring-red-200', 'border-red-400');

                    fields.forEach((field) => {
                        const input = rowEl.querySelector(`[name$="[${field}]"]`);
                        if (input) {
                            input.classList.add('border-red-400', 'bg-red-50');
                        }
                    });
                };

                clearDynamicRowVisualErrors();

                // Mode 1 validation per kartu mesin
                const mode1Rows = document.querySelectorAll('.mode1-row');
                mode1Rows.forEach((rowEl, idx) => {
                    const rowNo = idx + 1;

                    const getVal = (field) => {
                        const input = rowEl.querySelector(`[name$="[${field}]"]`);
                        return input ? String(input.value || '').trim() : '';
                    };

                    const jenis = getVal('jenis');
                    const operator = getVal('operator');
                    const tanggalSampel = getVal('tanggal_sampel');
                    const jamSampel = getVal('jam_sampel');

                    const hasAnyValue = operator !== '' || (tanggalSampel !== '' && tanggalSampel !== defaultSampleDate) || (jamSampel !== '' && jamSampel !== defaultSampleTime) || (jenis !== '' && jenis !== 'TBS');

                    if (!hasAnyValue) {
                        return;
                    }

                    const missingFields = [];
                    if (tanggalSampel === '') {
                        missingFields.push('tanggal_sampel');
                    }
                    if (jenis === '') {
                        missingFields.push('jenis');
                    }
                    if (operator === '') {
                        missingFields.push('operator');
                    }

                    if (missingFields.length > 0) {
                        markDynamicRowError(rowEl, ['kode', ...missingFields]);
                        errorMessages.push(`Mode Non-Angka Kartu #${rowNo}: jika diisi, Tanggal Sampel, Jenis, dan Operator harus lengkap`);
                        hasError = true;
                    }
                });

                // Mode 2 validation per kartu mesin
                const mode2Rows = document.querySelectorAll('.mode2-row');
                mode2Rows.forEach((rowEl, idx) => {
                    const rowNo = idx + 1;

                    const getVal = (field) => {
                        const input = rowEl.querySelector(`[name$="[${field}]"]`);
                        return input ? String(input.value || '').trim() : '';
                    };

                    const kode = getVal('kode_mode2');
                    const tanggalSampel = getVal('tanggal_sampel');
                    const jamSampel = getVal('jam_sampel');
                    const cawanKosong = getVal('cawan_kosong');
                    const beratBasah = getVal('berat_basah');
                    const cawanSampleKering = getVal('cawan_sample_kering');
                    const labuKosong = getVal('labu_kosong');
                    const oilLabu = getVal('oil_labu');

                    const hasDate = tanggalSampel !== '' && tanggalSampel !== defaultSampleDate;
                    const hasTime = jamSampel !== '' && jamSampel !== defaultSampleTime;
                    const hasStep1 = cawanKosong !== '' || beratBasah !== '' || labuKosong !== '';
                    const hasStep2 = cawanSampleKering !== '';
                    const hasFinal = oilLabu !== '';
                    const hasAnyNumeric = hasDate || hasTime || hasStep1 || hasStep2 || hasFinal;

                    if (!hasAnyNumeric && kode === '') {
                        return;
                    }

                    if (kode === '') {
                        markDynamicRowError(rowEl, ['kode_mode2']);
                        errorMessages.push(`Mode Angka Kartu #${rowNo}: Kode wajib terisi jika ada input`);
                        hasError = true;
                        return;
                    }

                    if (tanggalSampel === '') {
                        markDynamicRowError(rowEl, ['kode_mode2', 'tanggal_sampel']);
                        errorMessages.push(`Mode Angka Kartu #${rowNo}: Tanggal Sampel wajib diisi jika kartu digunakan`);
                        hasError = true;
                        return;
                    }

                    const rowPhase = hasFinal ? 'complete' : (hasStep2 ? 'final' : 'initial');

                    if (rowPhase === 'initial') {
                        const missingFields = [];
                        if (cawanKosong === '') missingFields.push('cawan_kosong');
                        if (beratBasah === '') missingFields.push('berat_basah');
                        if (labuKosong === '') missingFields.push('labu_kosong');

                        if (missingFields.length > 0) {
                            markDynamicRowError(rowEl, ['kode_mode2', ...missingFields]);
                            errorMessages.push(`Mode Angka Kartu #${rowNo}: Tahap 1 harus lengkap (Cawan Kosong, Berat Sampel Basah, Labu Kosong)`);
                            hasError = true;
                        }
                    } else if (rowPhase === 'final') {
                        if (cawanSampleKering === '') {
                            markDynamicRowError(rowEl, ['kode_mode2', 'cawan_sample_kering']);
                            errorMessages.push(`Mode Angka Kartu #${rowNo}: Tahap 2 membutuhkan Cawan + Sample Kering`);
                            hasError = true;
                        }
                    } else {
                        if (oilLabu === '') {
                            markDynamicRowError(rowEl, ['kode_mode2', 'oil_labu']);
                            errorMessages.push(`Mode Angka Kartu #${rowNo}: Tahap Akhir membutuhkan Oil + Labu`);
                            hasError = true;
                        }
                    }
                });

                // Tampilkan error jika ada
                if (hasError) {
                    e.preventDefault();

                    // Buat alert error
                    const errorHtml = `
                        <div id="validation-error" class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold text-red-900 mb-2">⚠️ Data Tidak Lengkap</h4>
                                    <p class="text-sm text-red-800 mb-2">Jika satu kartu diisi, maka semua field dalam kartu itu harus lengkap:</p>
                                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                                        ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;

                    // Hapus error lama jika ada
                    $('#validation-error').remove();

                    // Tambahkan error di atas form
                    $('#labForm').before(errorHtml);

                    // Scroll prioritas ke row error pertama, fallback ke alert error atas
                    const firstRowError = document.querySelector('.mode1-row.ring-red-200, .mode2-row.ring-red-200');
                    const targetTop = firstRowError
                        ? (firstRowError.getBoundingClientRect().top + window.pageYOffset - 120)
                        : ($('#validation-error').offset().top - 100);

                    $('html, body').animate({
                        scrollTop: targetTop
                    }, 300);

                    return false;
                }
            });
        });
    </script>

    <script>
        async function handleFormSubmit(event, form) {
            event.preventDefault();
            const confirmed = await window.confirmSave(form); // Pass form element untuk offline save
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