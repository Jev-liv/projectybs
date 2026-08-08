<x-layouts.app title="Input Data Boiler & Softener">

    @php

        /*
        |--------------------------------------------------------------------------
        | DEFAULT DATA
        |--------------------------------------------------------------------------
        */

        $defaultDate = now()->toDateString();
        $defaultTime = now()->format('H:i');


        /*
        |--------------------------------------------------------------------------
        | PARAMETER BOILER
        |--------------------------------------------------------------------------
        */

        $boilerMetrics = [
            [
                'key' => 'boiler_1_ph',
                'label' => 'pH',
                'unit' => 'pH',
            ],
            [
                'key' => 'boiler_1_tds',
                'label' => 'TDS',
                'unit' => 'ppm',
            ],
            [
                'key' => 'boiler_1_v_titrasi',
                'label' => 'V Titrasi',
                'unit' => 'mL',
            ],
            [
                'key' => 'boiler_1_silica',
                'label' => 'Silica',
                'unit' => 'ppm',
            ],
            [
                'key' => 'boiler_1_v_titrasi_chloride',
                'label' => 'V Titrasi Chloride',
                'unit' => 'mL',
            ],
            [
                'key' => 'boiler_1_total_v_titrasi_p_alkalinity',
                'label' => 'Total V Titrasi P Alkalinity',
                'unit' => 'mL',
            ],
            [
                'key' => 'boiler_1_iron_fe',
                'label' => 'Iron (Fe)',
                'unit' => 'ppm',
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | PARAMETER SOFTENER
        |--------------------------------------------------------------------------
        */

        $softenerSections = [

            'softener_1' => [
                'title' => 'SOFTENER 1',
                'code' => 'SF1',
                'metrics' => [
                    [
                        'key' => 'softener_1_ph',
                        'label' => 'pH',
                        'unit' => 'pH',
                    ],
                    [
                        'key' => 'softener_1_tds',
                        'label' => 'TDS',
                        'unit' => 'ppm',
                    ],
                    [
                        'key' => 'softener_1_total_hardness',
                        'label' => 'Total Hardness',
                        'unit' => 'ppm',
                    ],
                ],
            ],

            'softener_2' => [
                'title' => 'SOFTENER 2',
                'code' => 'SF2',
                'metrics' => [
                    [
                        'key' => 'softener_2_ph',
                        'label' => 'pH',
                        'unit' => 'pH',
                    ],
                    [
                        'key' => 'softener_2_tds',
                        'label' => 'TDS',
                        'unit' => 'ppm',
                    ],
                    [
                        'key' => 'softener_2_total_hardness',
                        'label' => 'Total Hardness',
                        'unit' => 'ppm',
                    ],
                ],
            ],

        ];

    @endphp


    <x-ui.card title="Input Data Boiler & Softener">


        {{-- ============================================================
             ERROR VALIDATION
        ============================================================= --}}

        @if ($errors->any())

            <div class="mb-5 rounded-xl border border-rose-200
                        bg-rose-50 px-4 py-3 text-sm text-rose-700">

                <div class="font-semibold text-rose-900">
                    Data belum bisa disimpan
                </div>

                <ul class="mt-2 list-inside list-disc space-y-1">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ============================================================
             HEADER
        ============================================================= --}}

        <div class="mb-5 rounded-xl border border-slate-200
                    bg-gradient-to-br from-slate-50
                    via-white to-indigo-50 p-5 shadow-sm">

            <div class="flex flex-col gap-3
                        md:flex-row md:items-end
                        md:justify-between">

                <div>

                    <p class="text-xs font-semibold uppercase
                              tracking-[0.20em] text-slate-400">

                        Boiler & Softener Input

                    </p>

                    <h1 class="mt-1 text-xl font-bold text-slate-900">

                        Input Data Boiler 1, Softener 1 & Softener 2

                    </h1>

                    <p class="mt-1 text-sm text-slate-500">

                        Isi tanggal, jam sampel, operator,
                        sampel boy, dan parameter pemeriksaan.

                    </p>

                </div>


                <a
                    href="{{ route('kernel.boiler-softener.index') }}"
                    class="inline-flex items-center justify-center
                           rounded-lg bg-slate-200 px-4 py-2
                           text-sm font-semibold text-slate-700
                           transition hover:bg-slate-300">

                    Lihat Data

                </a>

            </div>

        </div>
{{-- ========================================================
                 WAKTU PREVIEW
            ========================================================= --}}

            <div
                class="mb-5 rounded-xl border border-indigo-200
                       bg-indigo-50 p-4">

                <div
                    class="flex flex-col gap-3
                           md:flex-row md:items-center">

                    <div class="flex-1">

                        <h4
                            class="mb-1 text-sm font-semibold
                                   text-indigo-900">

                            Waktu Pengambilan Sampel

                        </h4>

                        <p
                            class="text-sm text-indigo-800">

                            Waktu ini merupakan preview waktu saat
                            halaman dibuka dan akan terus diperbarui.

                        </p>

                        <div
                            class="mt-2 rounded border
                                   border-indigo-200 bg-white p-3">

                            <div
                                class="text-xs text-indigo-700">

                                Waktu Saat Ini (Preview):

                            </div>

                            <div
                                class="text-lg font-bold
                                       text-indigo-900"
                                id="currentDateTime">

                                {{ now()->format('d/m/Y H:i:s') }}

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        {{-- ============================================================
             FORM UTAMA
        ============================================================= --}}

        <form
            action="{{ route('kernel.boiler-softener.store') }}"
            method="POST"
            id="boilerSoftenerForm">

            @csrf


            {{-- Hidden rows dibuat Javascript --}}
            <div id="boilerSoftenerGeneratedRows"></div>


            {{-- ========================================================
                 BOILER
            ========================================================= --}}

            <div
                class="mb-5 rounded-xl border border-emerald-300
                       bg-emerald-50 p-3"
                data-group="boiler">


                {{-- HEADER BOILER --}}

                <div class="mb-3 flex items-center
                            justify-between">

                    <div>

                        <p class="text-xs font-semibold
                                  uppercase tracking-[0.15em]
                                  text-emerald-700">

                            BOILER

                        </p>

                    </div>

                </div>


                {{-- ====================================================
                     BOILER 1
                ===================================================== --}}

                <div
                    class="rounded-lg border border-emerald-200
                           bg-white p-3 shadow-sm"
                    data-section-card
                    data-section-key="boiler_1"
                    data-section-jenis="boiler">


                    {{-- HEADER CARD --}}

                    <div class="mb-3 flex items-center
                                justify-between">

                        <div>

                            <h3 class="text-sm font-semibold
                                       text-slate-900">

                                BOILER 1

                            </h3>

                        </div>

                        <span
                            class="rounded-md bg-emerald-50
                                   px-2 py-1 text-[10px]
                                   font-semibold text-emerald-700">

                            B1

                        </span>

                    </div>


                    {{-- =================================================
                         METADATA
                    ================================================== --}}

                    <div class="grid gap-x-3 gap-y-3
                                md:grid-cols-2">


                        {{-- TANGGAL SAMPEL --}}

                        <div>

                            <label
                                class="mb-1 block text-[11px]
                                       font-medium text-slate-700">

                                Tanggal Sampel

                            </label>

                            <input
                                type="date"
                                name="sections[boiler_1][tanggal_sampel]"
                                value="{{ old(
                                    'sections.boiler_1.tanggal_sampel',
                                    $defaultDate
                                ) }}"
                                max="{{ $defaultDate }}"
                                required
                                data-section-field="tanggal_sampel"
                                class="w-full rounded-md border
                                       border-gray-300 bg-white
                                       px-3 py-1.5 text-xs
                                       outline-none transition
                                       focus:border-indigo-500
                                       focus:ring-1
                                       focus:ring-indigo-500/20">

                        </div>


                        {{-- JAM SAMPEL --}}

                        <div>

                            <label
                                class="mb-1 block text-[11px]
                                       font-medium text-slate-700">

                                Jam Sampel

                            </label>

                            <input
                                type="time"
                                name="sections[boiler_1][rounded_time]"
                                value="{{ old(
                                    'sections.boiler_1.rounded_time',
                                    $defaultTime
                                ) }}"
                                required
                                data-section-field="rounded_time"
                                class="w-full rounded-md border
                                       border-gray-300 bg-white
                                       px-3 py-1.5 text-xs
                                       outline-none transition
                                       focus:border-indigo-500
                                       focus:ring-1
                                       focus:ring-indigo-500/20">

                        </div>


                        {{-- JENIS --}}

                        <div>

                            <label
                                class="mb-1 block text-[11px]
                                       font-medium text-slate-700">

                                Jenis

                            </label>

                            <select
                                name="sections[boiler_1][jenis]"
                                required
                                class="w-full rounded-md border
                                       border-gray-300 bg-white
                                       px-3 py-1.5 text-xs
                                       outline-none
                                       focus:border-indigo-500">

                                <option value="boiler">
                                    Boiler
                                </option>

                            </select>

                        </div>


                        {{-- OPERATOR --}}

                        <div>

                            <label
                                class="mb-1 block text-[11px]
                                       font-medium text-slate-700">

                                Operator

                            </label>

                            <select
                                name="sections[boiler_1][operator]"
                                data-section-field="operator"
                                required
                                class="w-full rounded-md border
                                       border-gray-300 bg-white
                                       px-3 py-1.5 text-xs
                                       outline-none transition
                                       focus:border-indigo-500
                                       focus:ring-1
                                       focus:ring-indigo-500/20">

                                <option value="">
                                    -- Pilih Operator --
                                </option>

                                @foreach ($operatorOptions ?? [] as $operator)

                                    <option
                                        value="{{ $operator }}"
                                        @selected(
                                            old(
                                                'sections.boiler_1.operator'
                                            ) === $operator
                                        )>

                                        {{ $operator }}

                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- SAMPLE BOY --}}

                        <div class="md:col-span-2">

                            <label
                                class="mb-1 block text-[11px]
                                       font-medium text-slate-700">

                                Sampel Boy

                            </label>

                            <select
                                name="sections[boiler_1][sampel_boy]"
                                data-section-field="sampel_boy"
                                required
                                class="w-full rounded-md border
                                       border-gray-300 bg-white
                                       px-3 py-1.5 text-xs
                                       outline-none transition
                                       focus:border-indigo-500
                                       focus:ring-1
                                       focus:ring-indigo-500/20">

                                <option value="">
                                    -- Pilih Sampel Boy --
                                </option>

                                @foreach ($sampleBoyOptions ?? [] as $sampleBoy)

                                    <option
                                        value="{{ $sampleBoy }}"
                                        @selected(
                                            old(
                                                'sections.boiler_1.sampel_boy'
                                            ) === $sampleBoy
                                        )>

                                        {{ $sampleBoy }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    {{-- =================================================
                         PARAMETER BOILER
                    ================================================== --}}

                    <div class="mt-4 grid gap-3
                                md:grid-cols-2 xl:grid-cols-3">

                        @foreach ($boilerMetrics as $metric)

                            <div>

                                <label
                                    class="mb-1 block text-[11px]
                                           font-medium text-slate-700">

                                    {{ $metric['label'] }}

                                    <span class="text-slate-400">
                                        ({{ $metric['unit'] }})
                                    </span>

                                </label>

                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    name="sections[boiler_1][metrics][{{ $metric['key'] }}]"
                                    value="{{ old(
                                        'sections.boiler_1.metrics.' . $metric['key']
                                    ) }}"
                                    placeholder="0.0000"
                                    data-section-field="metric"
                                    data-metric-key="{{ $metric['key'] }}"
                                    data-metric-unit="{{ $metric['unit'] }}"
                                    class="w-full rounded-md border
                                           border-gray-300 bg-white
                                           px-3 py-1.5 text-xs
                                           outline-none transition
                                           focus:border-indigo-500
                                           focus:ring-1
                                           focus:ring-indigo-500/20">

                            </div>

                        @endforeach

                    </div>


                    {{-- =================================================
                         PREVIEW BOILER
                    ================================================== --}}

                    <div
                        class="mt-3 flex items-center
                               justify-between">

                        <span
                            class="text-[11px] font-medium
                                   text-slate-700">

                            Preview Boiler

                        </span>

                        <span
                            id="preview_boiler_1"
                            class="text-xs font-bold
                                   text-indigo-600">

                            0.000000

                        </span>

                    </div>

                </div>

            </div>


            {{-- ========================================================
                 SOFTENER
            ========================================================= --}}

            <div
                class="mb-5 rounded-xl border border-sky-300
                       bg-sky-50 p-3"
                data-group="softener">


                {{-- HEADER SOFTENER --}}

                <div class="mb-3">

                    <p
                        class="text-xs font-semibold uppercase
                               tracking-[0.15em] text-sky-700">

                        SOFTENER

                    </p>

                </div>


                {{-- ====================================================
                     SOFTENER 1 + SOFTENER 2
                ===================================================== --}}

                <div
                    class="grid gap-3 xl:grid-cols-2">


                    @foreach ($softenerSections as $sectionKey => $section)


                        {{-- =================================================
                             SOFTENER CARD
                        ================================================== --}}

                        <div
                            class="rounded-lg border border-sky-200
                                   bg-white p-3 shadow-sm"
                            data-section-card
                            data-section-key="{{ $sectionKey }}"
                            data-section-jenis="softener">


                            {{-- HEADER --}}

                            <div
                                class="mb-3 flex items-center
                                       justify-between">

                                <h3
                                    class="text-sm font-semibold
                                           text-slate-900">

                                    {{ $section['title'] }}

                                </h3>

                                <span
                                    class="rounded-md bg-sky-50
                                           px-2 py-1 text-[10px]
                                           font-semibold text-sky-700">

                                    {{ $section['code'] }}

                                </span>

                            </div>


                            {{-- =================================================
                                 METADATA
                            ================================================== --}}

                            <div
                                class="grid gap-x-3 gap-y-3
                                       md:grid-cols-2">


                                {{-- TANGGAL --}}

                                <div>

                                    <label
                                        class="mb-1 block text-[11px]
                                               font-medium text-slate-700">

                                        Tanggal Sampel

                                    </label>

                                    <input
                                        type="date"
                                        name="sections[{{ $sectionKey }}][tanggal_sampel]"
                                        value="{{ old(
                                            'sections.' . $sectionKey . '.tanggal_sampel',
                                            $defaultDate
                                        ) }}"
                                        max="{{ $defaultDate }}"
                                        required
                                        data-section-field="tanggal_sampel"
                                        class="w-full rounded-md border
                                               border-gray-300 bg-white
                                               px-3 py-1.5 text-xs
                                               outline-none
                                               focus:border-indigo-500
                                               focus:ring-1
                                               focus:ring-indigo-500/20">

                                </div>


                                {{-- JAM --}}

                                <div>

                                    <label
                                        class="mb-1 block text-[11px]
                                               font-medium text-slate-700">

                                        Jam Sampel

                                    </label>

                                    <input
                                        type="time"
                                        name="sections[{{ $sectionKey }}][rounded_time]"
                                        value="{{ old(
                                            'sections.' . $sectionKey . '.rounded_time',
                                            $defaultTime
                                        ) }}"
                                        required
                                        data-section-field="rounded_time"
                                        class="w-full rounded-md border
                                               border-gray-300 bg-white
                                               px-3 py-1.5 text-xs
                                               outline-none
                                               focus:border-indigo-500
                                               focus:ring-1
                                               focus:ring-indigo-500/20">

                                </div>


                                {{-- JENIS --}}

                                <div>

                                    <label
                                        class="mb-1 block text-[11px]
                                               font-medium text-slate-700">

                                        Jenis

                                    </label>

                                    <select
                                        name="sections[{{ $sectionKey }}][jenis]"
                                        required
                                        class="w-full rounded-md border
                                               border-gray-300 bg-white
                                               px-3 py-1.5 text-xs">

                                        <option value="softener">
                                            Softener
                                        </option>

                                    </select>

                                </div>


                                {{-- OPERATOR --}}

                                <div>

                                    <label
                                        class="mb-1 block text-[11px]
                                               font-medium text-slate-700">

                                        Operator

                                    </label>

                                    <select
                                        name="sections[{{ $sectionKey }}][operator]"
                                        data-section-field="operator"
                                        required
                                        class="w-full rounded-md border
                                               border-gray-300 bg-white
                                               px-3 py-1.5 text-xs
                                               outline-none transition
                                               focus:border-indigo-500
                                               focus:ring-1
                                               focus:ring-indigo-500/20">

                                        <option value="">
                                            -- Pilih Operator --
                                        </option>

                                        @foreach ($operatorOptions ?? [] as $operator)

                                            <option
                                                value="{{ $operator }}"
                                                @selected(
                                                    old(
                                                        'sections.' . $sectionKey . '.operator'
                                                    ) === $operator
                                                )>

                                                {{ $operator }}

                                            </option>

                                        @endforeach

                                    </select>

                                </div>


                                {{-- SAMPLE BOY --}}

                                <div class="md:col-span-2">

                                    <label
                                        class="mb-1 block text-[11px]
                                               font-medium text-slate-700">

                                        Sampel Boy

                                    </label>

                                    <select
                                        name="sections[{{ $sectionKey }}][sampel_boy]"
                                        data-section-field="sampel_boy"
                                        required
                                        class="w-full rounded-md border
                                               border-gray-300 bg-white
                                               px-3 py-1.5 text-xs
                                               outline-none transition
                                               focus:border-indigo-500
                                               focus:ring-1
                                               focus:ring-indigo-500/20">

                                        <option value="">
                                            -- Pilih Sampel Boy --
                                        </option>

                                        @foreach ($sampleBoyOptions ?? [] as $sampleBoy)

                                            <option
                                                value="{{ $sampleBoy }}"
                                                @selected(
                                                    old(
                                                        'sections.' . $sectionKey . '.sampel_boy'
                                                    ) === $sampleBoy
                                                )>

                                                {{ $sampleBoy }}

                                            </option>

                                        @endforeach

                                    </select>

                                </div>

                            </div>


                            {{-- =================================================
                                 PARAMETER SOFTENER
                            ================================================== --}}

                            <div class="mt-4 grid gap-3">

                                @foreach ($section['metrics'] as $metric)

                                    <div>

                                        <label
                                            class="mb-1 block text-[11px]
                                                   font-medium
                                                   text-slate-700">

                                            {{ $metric['label'] }}

                                            <span class="text-slate-400">
                                                ({{ $metric['unit'] }})
                                            </span>

                                        </label>

                                        <input
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            name="sections[{{ $sectionKey }}][metrics][{{ $metric['key'] }}]"
                                            value="{{ old(
                                                'sections.' . $sectionKey .
                                                '.metrics.' . $metric['key']
                                            ) }}"
                                            placeholder="0.0000"
                                            data-section-field="metric"
                                            data-metric-key="{{ $metric['key'] }}"
                                            data-metric-unit="{{ $metric['unit'] }}"
                                            class="w-full rounded-md border
                                                   border-gray-300 bg-white
                                                   px-3 py-1.5 text-xs
                                                   outline-none transition
                                                   focus:border-indigo-500
                                                   focus:ring-1
                                                   focus:ring-indigo-500/20">

                                    </div>

                                @endforeach

                            </div>


                            {{-- =================================================
                                 PREVIEW SOFTENER
                            ================================================== --}}

                            <div
                                class="mt-3 flex items-center
                                       justify-between">

                                <span
                                    class="text-[11px] font-medium
                                           text-slate-700">

                                    Preview Softener

                                </span>

                                <span
                                    id="preview_{{ $sectionKey }}"
                                    class="text-xs font-bold
                                           text-indigo-600">

                                    0.000000

                                </span>

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>


            


            {{-- ========================================================
                 BUTTON
            ========================================================= --}}

            <div
                class="flex flex-wrap justify-end gap-3
                       border-t border-slate-200 pt-4">

                <a
                    href="{{ route('kernel.boiler-softener.index') }}"
                    class="inline-flex items-center
                           rounded-lg border border-gray-300
                           px-5 py-2.5 text-sm font-semibold
                           text-gray-700 transition
                           hover:bg-gray-100">

                    Batal

                </a>


                <button
                    type="submit"
                    class="inline-flex items-center
                           rounded-lg bg-indigo-600
                           px-5 py-2.5 text-sm font-semibold
                           text-white shadow-sm transition
                           hover:bg-indigo-700">

                    Simpan Semua Data

                </button>

            </div>

        </form>

    </x-ui.card>


    {{-- ================================================================
         JAVASCRIPT
    ================================================================= --}}

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const form =
                document.getElementById('boilerSoftenerForm');

            const generatedRows =
                document.getElementById(
                    'boilerSoftenerGeneratedRows'
                );


            /*
            |--------------------------------------------------------------------------
            | FORMAT ANGKA
            |--------------------------------------------------------------------------
            */

            function formatNumber(value) {

                if (
                    value === null ||
                    value === undefined ||
                    value === ''
                ) {

                    return '0.000000';

                }

                const number =
                    parseFloat(value);

                if (Number.isNaN(number)) {

                    return '0.000000';

                }

                return number.toFixed(6);

            }


            /*
            |--------------------------------------------------------------------------
            | HITUNG PREVIEW
            |--------------------------------------------------------------------------
            |
            | Untuk sekarang preview menjumlahkan nilai parameter
            | yang diisi pada masing-masing section.
            |
            */

            function updatePreview(sectionKey) {

                const section =
                    document.querySelector(
                        `[data-section-key="${sectionKey}"]`
                    );

                if (!section) {

                    return;

                }


                const metricInputs =
                    section.querySelectorAll(
                        '[data-section-field="metric"]'
                    );


                let total = 0;


                metricInputs.forEach(function (input) {

                    const value =
                        parseFloat(input.value);

                    if (!Number.isNaN(value)) {

                        total += value;

                    }

                });


                const preview =
                    document.getElementById(
                        `preview_${sectionKey}`
                    );


                if (preview) {

                    preview.textContent =
                        formatNumber(total);

                }

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE SEMUA PREVIEW
            |--------------------------------------------------------------------------
            */

            function updateAllPreview() {

                updatePreview('boiler_1');

                updatePreview('softener_1');

                updatePreview('softener_2');

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE JAM SEKARANG
            |--------------------------------------------------------------------------
            */

            function updateCurrentDateTime() {

                const element =
                    document.getElementById(
                        'currentDateTime'
                    );

                if (!element) {

                    return;

                }


                const now = new Date();


                const day =
                    String(
                        now.getDate()
                    ).padStart(2, '0');


                const month =
                    String(
                        now.getMonth() + 1
                    ).padStart(2, '0');


                const year =
                    now.getFullYear();


                const hours =
                    String(
                        now.getHours()
                    ).padStart(2, '0');


                const minutes =
                    String(
                        now.getMinutes()
                    ).padStart(2, '0');


                const seconds =
                    String(
                        now.getSeconds()
                    ).padStart(2, '0');


                element.textContent =
                    `${day}/${month}/${year} ` +
                    `${hours}:${minutes}:${seconds}`;

            }


            /*
            |--------------------------------------------------------------------------
            | CREATE HIDDEN INPUT
            |--------------------------------------------------------------------------
            */

            function createHiddenInput(
                name,
                value
            ) {

                const input =
                    document.createElement('input');


                input.type = 'hidden';

                input.name = name;

                input.value =
                    value ?? '';


                return input;

            }


            /*
            |--------------------------------------------------------------------------
            | BUILD ROWS
            |--------------------------------------------------------------------------
            */

            function buildRows() {

                generatedRows.innerHTML = '';


                document
                    .querySelectorAll(
                        '[data-section-card]'
                    )
                    .forEach(function (sectionCard) {


                        const sectionKey =
                            sectionCard.dataset.sectionKey;


                        const jenis =
                            sectionCard.dataset.sectionJenis;


                        const tanggalSampel =
                            sectionCard.querySelector(
                                '[data-section-field="tanggal_sampel"]'
                            )?.value || '';


                        const roundedTime =
                            sectionCard.querySelector(
                                '[data-section-field="rounded_time"]'
                            )?.value || '';


                        const operator =
                            sectionCard.querySelector(
                                '[data-section-field="operator"]'
                            )?.value || '';


                        const sampelBoy =
                            sectionCard.querySelector(
                                '[data-section-field="sampel_boy"]'
                            )?.value || '';


                        /*
                        |--------------------------------------------------------------------------
                        | SETIAP PARAMETER YANG TERISI
                        |--------------------------------------------------------------------------
                        */

                        sectionCard
                            .querySelectorAll(
                                '[data-section-field="metric"]'
                            )
                            .forEach(function (metricInput) {


                                const nilai =
                                    metricInput.value.trim();


                                /*
                                | Jangan kirim parameter kosong
                                */

                                if (!nilai) {

                                    return;

                                }


                                const metricKey =
                                    metricInput.dataset.metricKey;


                                const unit =
                                    metricInput.dataset.metricUnit || '';


                                /*
                                |--------------------------------------------------------------------------
                                | ROW DATABASE
                                |--------------------------------------------------------------------------
                                */

                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][jenis]`,
                                        jenis
                                    )
                                );


                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][parameter]`,
                                        metricKey
                                    )
                                );


                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][nilai]`,
                                        nilai
                                    )
                                );


                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][satuan]`,
                                        unit
                                    )
                                );


                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][operator]`,
                                        operator
                                    )
                                );


                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][sampel_boy]`,
                                        sampelBoy
                                    )
                                );


                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][tanggal_sampel]`,
                                        tanggalSampel
                                    )
                                );


                                generatedRows.appendChild(
                                    createHiddenInput(
                                        `rows[${metricKey}][rounded_time]`,
                                        roundedTime
                                    )
                                );

                            });

                    });

            }


            /*
            |--------------------------------------------------------------------------
            | EVENT INPUT
            |--------------------------------------------------------------------------
            */

            form
                .querySelectorAll(
                    '[data-section-field="metric"]'
                )
                .forEach(function (input) {

                    input.addEventListener(
                        'input',
                        function () {

                            updateAllPreview();

                        }
                    );

                    input.addEventListener(
                        'change',
                        function () {

                            updateAllPreview();

                        }
                    );

                });


            /*
            |--------------------------------------------------------------------------
            | INITIAL PREVIEW
            |--------------------------------------------------------------------------
            */

            updateAllPreview();


            /*
            |--------------------------------------------------------------------------
            | JAM REALTIME
            |--------------------------------------------------------------------------
            */

            updateCurrentDateTime();

            setInterval(
                updateCurrentDateTime,
                1000
            );


            /*
            |--------------------------------------------------------------------------
            | SUBMIT FORM
            |--------------------------------------------------------------------------
            */

            form.addEventListener(
                'submit',
                async function (event) {

                    event.preventDefault();


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI OPERATOR
                    |--------------------------------------------------------------------------
                    */

                    const operatorFields =
                        form.querySelectorAll(
                            '[data-section-field="operator"]'
                        );


                    let operatorValid = true;


                    operatorFields.forEach(
                        function (operatorField) {

                            if (!operatorField.value) {

                                operatorValid = false;

                                operatorField.classList.add(
                                    'border-red-500'
                                );

                            }
                            else {

                                operatorField.classList.remove(
                                    'border-red-500'
                                );

                            }

                        }
                    );


                    if (!operatorValid) {

                        alert(
                            'Silakan pilih Operator pada semua section.'
                        );

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI SAMPLE BOY
                    |--------------------------------------------------------------------------
                    */

                    const sampleBoyFields =
                        form.querySelectorAll(
                            '[data-section-field="sampel_boy"]'
                        );


                    let sampleBoyValid = true;


                    sampleBoyFields.forEach(
                        function (sampleBoyField) {

                            if (!sampleBoyField.value) {

                                sampleBoyValid = false;

                                sampleBoyField.classList.add(
                                    'border-red-500'
                                );

                            }
                            else {

                                sampleBoyField.classList.remove(
                                    'border-red-500'
                                );

                            }

                        }
                    );


                    if (!sampleBoyValid) {

                        alert(
                            'Silakan pilih Sampel Boy pada semua section.'
                        );

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | BUILD ROWS
                    |--------------------------------------------------------------------------
                    */

                    buildRows();


                    /*
                    |--------------------------------------------------------------------------
                    | HARUS ADA PARAMETER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        generatedRows.children.length === 0
                    ) {

                        alert(
                            'Isi minimal satu parameter sebelum menyimpan.'
                        );

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CONFIRM SAVE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        typeof window.confirmSave ===
                        'function'
                    ) {

                        const confirmed =
                            await window.confirmSave(form);


                        if (!confirmed) {

                            return;

                        }

                    }
                    else {

                        const confirmed =
                            window.confirm(
                                'Apakah data sudah benar dan ingin disimpan?'
                            );


                        if (!confirmed) {

                            return;

                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SUBMIT
                    |--------------------------------------------------------------------------
                    */

                    form.submit();

                }
            );

        });

    </script>

</x-layouts.app>