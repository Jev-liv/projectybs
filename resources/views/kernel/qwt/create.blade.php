<x-layouts.app title="Input Data QWT Fibre Press">
    @php
        $isYbsOffice = Auth::user()?->office === 'YBS';
    @endphp
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
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
    <x-ui.card title="Input Data QWT Fibre Press">
        @if(!$isYbsOffice)
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-indigo-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-indigo-900 mb-1">Jam Pengambilan Checklist</h4>
                        <p class="text-sm text-indigo-800">Isi jam pengambilan saat checklist. Jam ini dipakai untuk
                            validasi interval dan jam proses.</p>
                        <div class="mt-2 p-3 bg-white rounded border border-indigo-200">
                            <div class="text-xs text-indigo-700">Waktu Saat Ini (Preview):</div>
                            <div class="text-lg font-bold text-indigo-900" id="currentDateTime">
                                {{ now()->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <h4 class="text-sm font-semibold text-red-900 mb-2">Data belum bisa disimpan</h4>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('kernel.qwt.store') }}" method="POST" id="qwtForm" class="space-y-6">
            @csrf

            <div class="border-2 border-blue-200 bg-blue-50 rounded-lg p-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                    <input type="checkbox" name="kegiatan_dispek" id="kegiatan_dispek" value="1" {{ old('kegiatan_dispek') ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Ada kegiatan dispatch</span>
                </label>
                <label for="rounded_time" class="block text-sm font-medium text-gray-700 mb-2">Jam Pengambilan</label>
                <input type="time" name="rounded_time" id="rounded_time"
                    value="{{ old('rounded_time', now()->format('H:i')) }}" {{ old('kegiatan_dispek') ? '' : 'disabled' }}
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('rounded_time') border-red-400 bg-red-50 @enderror">
                @error('rounded_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-500">Jam manual hanya aktif jika kegiatan dispatch dicentang.</p>
            </div>

            @foreach($kodeFormGroups as $group)
                <div class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-amber-900">{{ $group['title'] }}</h3>
                    @php
                        $count = count($group['items']);
                        $gridClass = match ($count) {
                            4 => 'md:grid-cols-2 lg:grid-cols-4',
                            9 => 'md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
                            default => 'md:grid-cols-2 lg:grid-cols-3',
                        };
                    @endphp
                    <div class="grid grid-cols-1 {{ $gridClass }} gap-4">
                        @foreach($group['items'] as $item)
                            @php $kode = $item['kode']; @endphp
                            <div class="border border-amber-200 rounded-lg p-4 bg-white/85 shadow-sm space-y-3" data-kernel-row>
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-gray-900">{{ $item['label'] }}</h4>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ $kode }}</span>
                                </div>

                                <input type="hidden" name="rows[{{ $kode }}][kode]" value="{{ $kode }}">

                                @if($isYbsOffice)
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Sampel</label>
                                            <input type="date" name="rows[{{ $kode }}][tanggal_sampel]"
                                                value="{{ old("rows.$kode.tanggal_sampel", now()->toDateString()) }}"
                                                max="{{ now()->toDateString() }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Jam Sampel</label>
                                            <input type="time" name="rows[{{ $kode }}][rounded_time]"
                                                value="{{ old("rows.$kode.rounded_time", now()->format('H:i')) }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                    </div>
                                @endif

                                <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700">
                                    <input type="checkbox" name="rows[{{ $kode }}][pengulangan]" value="1" {{ old("rows.$kode.pengulangan") ? 'checked' : '' }} data-remarks-toggle
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Data sampel ulang</span>
                                </label>

                                <div class="space-y-1 {{ old("rows.$kode.pengulangan") ? '' : 'hidden' }}" data-remarks-wrapper>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Remarks</label>
                                    <textarea name="rows[{{ $kode }}][remarks]" rows="3"
                                        placeholder="Tulis catatan sampel ulang"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">{{ old("rows.$kode.remarks") }}</textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Jenis</label>
                                    <select name="rows[{{ $kode }}][jenis]"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="">-- Pilih Jenis --</option>
                                        @foreach($jenisOptions as $jenisValue => $jenisLabel)
                                            <option value="{{ $jenisValue }}" {{ old("rows.$kode.jenis", 'TBS') == $jenisValue ? 'selected' : '' }}>{{ $jenisLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Operator</label>
                                    @if(!empty($operatorOptions))
                                        <select name="rows[{{ $kode }}][operator]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            <option value="">-- Pilih Operator --</option>
                                            @foreach($operatorOptions as $operatorName)
                                                <option value="{{ $operatorName }}" {{ old("rows.$kode.operator") == $operatorName ? 'selected' : '' }}>{{ $operatorName }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" name="rows[{{ $kode }}][operator]"
                                            value="{{ old("rows.$kode.operator") }}" placeholder="Nama operator"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Sampel Boy</label>
                                    @if(!empty($sampleBoyOptions))
                                        <select name="rows[{{ $kode }}][sampel_boy]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm @error("rows.$kode.sampel_boy") border-red-400 bg-red-50 @enderror">
                                            <option value="">-- Pilih Sampel Boy --</option>
                                            @foreach($sampleBoyOptions as $sampleBoyName)
                                                <option value="{{ $sampleBoyName }}" {{ old("rows.$kode.sampel_boy") == $sampleBoyName ? 'selected' : '' }}>{{ $sampleBoyName }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" name="rows[{{ $kode }}][sampel_boy]"
                                            value="{{ old("rows.$kode.sampel_boy", Auth::user()?->name) }}"
                                            placeholder="Nama sampel boy"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm @error("rows.$kode.sampel_boy") border-red-400 bg-red-50 @enderror">
                                    @endif
                                    @error("rows.$kode.sampel_boy")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Sampel Setelah
                                            Kuarter</label>
                                        <input type="number" step="0.0001" name="rows[{{ $kode }}][sampel_setelah_kuarter]"
                                            data-losses="sampel-setelah-kuarter"
                                            value="{{ old("rows.$kode.sampel_setelah_kuarter") }}" placeholder="0.0000"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Berat Nut Utuh</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][berat_nut_utuh]"
                                                data-losses="berat-nut-utuh" value="{{ old("rows.$kode.berat_nut_utuh") }}"
                                                placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Berat Nut Pecah</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][berat_nut_pecah]"
                                                data-losses="berat-nut-pecah" value="{{ old("rows.$kode.berat_nut_pecah") }}"
                                                placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Berat Kernel
                                                Utuh</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][berat_kernel_utuh]"
                                                data-losses="berat-kernel" value="{{ old("rows.$kode.berat_kernel_utuh") }}"
                                                placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Berat Kernel
                                                Pecah</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][berat_kernel_pecah]"
                                                data-losses="berat-kernel" value="{{ old("rows.$kode.berat_kernel_pecah") }}"
                                                placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Berat Cangkang</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][berat_cangkang]"
                                                data-losses="berat-cangkang" value="{{ old("rows.$kode.berat_cangkang") }}"
                                                placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Berat Batu</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][berat_batu]"
                                                data-losses="berat-batu" value="{{ old("rows.$kode.berat_batu") }}"
                                                placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Ampere Screw</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][ampere_screw]"
                                                value="{{ old("rows.$kode.ampere_screw") }}" placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Tekanan
                                                Hydraulic</label>
                                            <input type="number" step="0.0001" name="rows[{{ $kode }}][tekanan_hydraulic]"
                                                value="{{ old("rows.$kode.tekanan_hydraulic") }}" placeholder="0.0000"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-600">
                                        Preview Losses
                                    </span>
                                    <span class="text-sm font-bold text-indigo-600" data-kernel-losses>
                                        0.000000
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex flex-wrap justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="{{ route('kernel.qwt.index') }}"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">Batal</a>
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">Simpan
                    Semua Data</button>
            </div>
        </form>
    </x-ui.card>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function preventNumberWheel(scope = document) {
            scope.querySelectorAll('input[type="number"]').forEach((input) => {
                input.addEventListener('wheel', function (event) {
                    event.preventDefault();
                }, { passive: false });
            });
        }

        $(document).ready(function () {
            $('select[name$="[operator]"]').select2({
                placeholder: '-- Pilih Operator --',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                language: {
                    noResults: function () {
                        return 'Tidak ditemukan';
                    },
                    searching: function () {
                        return 'Mencari...';
                    }
                }
            });

            $('select[name$="[sampel_boy]"]').select2({
                placeholder: '-- Pilih Sampel Boy --',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                language: {
                    noResults: function () {
                        return 'Tidak ditemukan';
                    },
                    searching: function () {
                        return 'Mencari...';
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {

            function calculateLosses(card) {
                const getValue = (selector, index = 0) =>
                    parseFloat(card.querySelectorAll(selector)[index]?.value) || 0;

                const sampelSetelahKuarter = getValue('[data-losses="sampel-setelah-kuarter"]');

                const beratNutUtuh = getValue('[data-losses="berat-nut-utuh"]');
                const beratNutPecah = getValue('[data-losses="berat-nut-pecah"]');

                const beratKernelUtuh = getValue('[data-losses="berat-kernel"]', 0);
                const beratKernelPecah = getValue('[data-losses="berat-kernel"]', 1);

                const beratCangkang = getValue('[data-losses="berat-cangkang"]');
                const beratBatu = getValue('[data-losses="berat-batu"]');

                const totalBeratNut =
                    beratNutUtuh +
                    beratNutPecah +
                    beratKernelUtuh +
                    beratKernelPecah +
                    beratCangkang;

                const beratBrokenNut =
                    beratNutPecah +
                    beratKernelUtuh +
                    beratKernelPecah +
                    beratCangkang;

                const losses =
                    sampelSetelahKuarter > 0 && totalBeratNut > 0
                        ? (beratBrokenNut / totalBeratNut) * 100
                        : 0;

                card.querySelector('[data-kernel-losses]')
                    .textContent = losses.toFixed(2);
            }

            document.querySelectorAll('[data-kernel-row]').forEach(card => {

                card.querySelectorAll('[data-losses]').forEach(input => {

                    input.addEventListener('input', () => {
                        calculateLosses(card);
                    });

                });

                calculateLosses(card);
            });

        });

        function updateClock() {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const el = document.getElementById('currentDateTime');
            if (el) {
                el.textContent =
                    pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear() + ' ' +
                    pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
            }
        }

        function toggleRoundedTimeInput() {
            const dispatch = document.getElementById('kegiatan_dispek');
            const roundedTime = document.getElementById('rounded_time');
            if (!dispatch || !roundedTime) return;
            roundedTime.disabled = !dispatch.checked;
        }

        function toggleRemarksField(checkbox) {
            const card = checkbox.closest('[data-kernel-row]');
            const wrapper = card?.querySelector('[data-remarks-wrapper]');
            if (wrapper) {
                wrapper.classList.toggle('hidden', !checkbox.checked);
            }
        }

        updateClock();
        setInterval(updateClock, 1000);

        const qwtForm = document.getElementById('qwtForm');
        if (qwtForm) {
            let isConfirmedSubmit = false;
            qwtForm.addEventListener('submit', async function (e) {
                if (isConfirmedSubmit) {
                    return;
                }

                e.preventDefault();
                const confirmed = await window.confirmSave(this);
                if (confirmed) {
                    isConfirmedSubmit = true;
                    this.submit();
                }
            });
        }

        document.getElementById('kegiatan_dispek')?.addEventListener('change', toggleRoundedTimeInput);
        document.querySelectorAll('[data-remarks-toggle]').forEach(checkbox => {
            checkbox.addEventListener('change', () => toggleRemarksField(checkbox));
            toggleRemarksField(checkbox);
        });
        toggleRoundedTimeInput();
        preventNumberWheel();
    </script>
</x-layouts.app>