<?php

namespace App\Http\Controllers;

use App\Exports\KernelDestonerExport;
use App\Exports\KernelDirtMoistExport;
use App\Exports\KernelLaporanExport;
use App\Exports\KernelPerformanceExport;
use App\Exports\KernelQwtExport;
use App\Exports\KernelRekapExport;
use App\Exports\KernelRippleMillExport;
use App\Models\KernelBobotConfig;
use App\Models\KernelCalculation;
use App\Models\KernelMesin;
use App\Models\KernelDirtMoistCalculation;
use App\Models\KernelMasterData;
use App\Models\KernelProsses;
use App\Models\KernelQwt;
use App\Models\KernelDestoner;
use App\Models\KernelRippleMill;
use App\Models\KernelRecord;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class KernelController extends Controller
{
    use LogsActivity;

    // Kernel Losses kode groups by office
    private const KERNEL_LOSSES_KODES_YBS = ['FC1', 'FC2', 'L1', 'L2', 'L3', 'L4', 'CWS', 'CWS2', 'CWS3'];
    private const KERNEL_LOSSES_KODES_SUN = ['FC1', 'FC2', 'L1', 'L2', 'CWS', 'CWS2'];
    private const KERNEL_LOSSES_KODES_SJN = self::KERNEL_LOSSES_KODES_YBS;

    // Dirt & Moist kode groups by office
    private const DIRT_MOIST_KODES_YBS = ['IN1', 'IN2', 'IN3', 'IN4', 'IN5', 'OUT1', 'OUT2', 'OUT3', 'OUT4', 'OUT5'];
    private const DIRT_MOIST_KODES_SUN = ['IN1', 'IN2', 'IN3', 'IN4', 'OUT1', 'OUT2', 'OUT3', 'OUT4'];
    private const DIRT_MOIST_KODES_SJN = self::DIRT_MOIST_KODES_YBS;

    // QWT kode groups by office
    private const QWT_KODES_YBS = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9'];
    private const QWT_KODES_SUN = ['P1', 'P2', 'P3', 'P4'];
    private const QWT_KODES_SJN = self::QWT_KODES_YBS;

    // Ripple Mill kode groups by office
    private const RIPPLE_MILL_KODES_YBS = ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7'];
    private const RIPPLE_MILL_KODES_SUN = ['R1', 'R2', 'R3'];
    private const RIPPLE_MILL_KODES_SJN = self::RIPPLE_MILL_KODES_YBS;

    // Destoner kode groups by office
    private const DESTONER_KODES_YBS = ['D1', 'D2'];
    private const DESTONER_KODES_SUN = ['D1', 'D2'];
    private const DESTONER_KODES_SJN = self::DESTONER_KODES_YBS;

    private array $pengulanganColumnCache = [];

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $calculationsQuery = KernelCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->orderBy('created_at', 'desc');

        $this->applyOfficeFilter($calculationsQuery, $officeFilter);

        if ($request->filled('kode')) {
            $calculationsQuery->where('kode', $request->kode);
        }

        if (!Auth::user()->can('view kernel losses')) {
            $calculationsQuery->where('user_id', Auth::id());
        }

        $totalCalculations = (clone $calculationsQuery)->count();
        $todayStart = Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $todayEnd = Carbon::today()->endOfDay()->format('Y-m-d H:i:s');

        $statistics = [
            'total_records' => $totalCalculations,
            'records_today' => KernelCalculation::whereBetween('created_at', [$todayStart, $todayEnd])
                ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
                ->count(),
            'calculations_count' => $totalCalculations,
        ];

        $kernelCalculations = $calculationsQuery->paginate(15, ['*'], 'calculations');

        $kernelCalculations->appends($request->only(['start_date', 'end_date', 'kode', 'office']));

        $kodeOptions = KernelMasterData::getKodeDropdown($officeFilter);
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');

        return view('kernel.index', compact(
            'kernelCalculations',
            'statistics',
            'kodeOptions',
            'masterData',
            'startDate',
            'endDate',
            'officeFilter'
        ));
    }

    public function create()
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data kernel losses.');
        }

        $userOffice = $this->getUserOffice();
        $kodeOptions = $this->getKernelLossesKodeOptions($userOffice);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);
        $sampleBoyRequired = $userOffice === 'YBS' && !empty($sampleBoyOptions);
        $kodeFormGroups = $this->getKernelLossesFormGroups($kodeOptions, $userOffice);
        $jenisOptions = KernelRecord::getJenisOptions();

        $kernelLimitMap = $this->activeKernelMasterDataQuery($userOffice)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->kode => [
                        'operator' => $item->limit_operator,
                        'value' => (float) $item->limit_value,
                    ],
                ];
            })
            ->toArray();

        $operatorOptions = $this->getOperatorOptionsByOffice($userOffice, 'kernel');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);

        return view('kernel.create', compact('kodeOptions', 'kodeFormGroups', 'jenisOptions', 'kernelLimitMap', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data kernel losses.');
        }

        $userOffice = $this->getUserOffice();
        if (!$userOffice) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda harus memiliki Office yang ditentukan untuk dapat input data.'], 422);
            }
            return back()
                ->withInput()
                ->with('error', 'Anda harus memiliki Office yang ditentukan untuk dapat input data. Silakan hubungi Administrator.');
        }

        $kodeOptions = $this->getKernelLossesKodeOptions($userOffice);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);
        $sampleBoyRequired = !empty($sampleBoyOptions);

        $validated = $request->validate([
            'kegiatan_dispek' => 'nullable|boolean',
            'tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rounded_time' => 'nullable|date_format:H:i',
            'rows' => 'required|array',
            'rows.*.kode' => ['required', 'string', Rule::in(array_keys($kodeOptions))],
            'rows.*.tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rows.*.rounded_time' => 'nullable|date_format:H:i',
            'rows.*.jenis' => 'nullable|string',
            'rows.*.operator' => $this->getOperatorValidationRules($userOffice, false, 'kernel'),
            'rows.*.sampel_boy' => $sampleBoyRequired
                ? $this->getSampleBoyValidationRules($userOffice, false)
                : $this->getSampleBoyValidationRules($userOffice),
            'rows.*.pengulangan' => 'nullable|boolean',
            'rows.*.remarks' => 'nullable|string|max:500',
            'rows.*.berat_sampel' => 'nullable|numeric|gt:0',
            'rows.*.nut_utuh_nut' => 'nullable|numeric|min:0',
            'rows.*.nut_utuh_kernel' => 'nullable|numeric|min:0',
            'rows.*.nut_pecah_nut' => 'nullable|numeric|min:0',
            'rows.*.nut_pecah_kernel' => 'nullable|numeric|min:0',
            'rows.*.kernel_utuh' => 'nullable|numeric|min:0',
            'rows.*.kernel_pecah' => 'nullable|numeric|min:0',
        ], [
            'operator.in' => 'Operator tidak sesuai dengan daftar office.',
            'rounded_time.date_format' => 'Format jam pengambilan harus HH:MM.',
            'rows.required' => 'Data sampel belum tersedia.',
            'rows.*.kode.required' => 'Kode tidak boleh kosong.',
            'rows.*.kode.in' => 'Kode tidak valid untuk input Kernel Losses.',
            'rows.*.sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'rows.*.sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
            'rows.*.berat_sampel.gt' => 'Berat Sampel harus lebih dari 0.',
        ]);

        $isKegiatanDispek = (bool) $request->boolean('kegiatan_dispek');
        if ($isKegiatanDispek && empty($validated['rounded_time'])) {
            throw ValidationException::withMessages([
                'rounded_time' => 'Jam pengambilan wajib diisi jika kegiatan dispatch dicentang.',
            ]);
        }

        $numericFields = [
            'berat_sampel',
            'nut_utuh_nut',
            'nut_utuh_kernel',
            'nut_pecah_nut',
            'nut_pecah_kernel',
            'kernel_utuh',
            'kernel_pecah',
        ];

        $rowsToSave = [];
        foreach (($validated['rows'] ?? []) as $row) {
            $hasInput = false;
            foreach ($numericFields as $field) {
                if ($this->hasAnyValue($row[$field] ?? null)) {
                    $hasInput = true;
                    break;
                }
            }

            if ($hasInput) {
                $rowsToSave[] = $row;
            }
        }

        if (empty($rowsToSave)) {
            throw ValidationException::withMessages([
                'rows' => 'Silakan isi minimal satu form data kernel losses.',
            ]);
        }

        return DB::transaction(function () use ($request, $userOffice, $rowsToSave, $isKegiatanDispek, $sampleBoyRequired, $sampleBoyOptions) {
            $rowErrors = [];
            $savedRows = [];

            foreach ($rowsToSave as $row) {
                $roundedTime = $this->resolveKernelSampleTimestamp(
                    $row['tanggal_sampel'] ?? $validated['tanggal_sampel'] ?? null,
                    $row['rounded_time'] ?? $validated['rounded_time'] ?? null,
                    $isKegiatanDispek || $userOffice === 'YBS'
                );
                $kode = (string) ($row['kode'] ?? '');
                $isPengulangan = (bool) ($row['pengulangan'] ?? false);

                if ($sampleBoyRequired) {
                    $sampleBoy = trim((string) ($row['sampel_boy'] ?? ''));

                    if ($sampleBoy === '') {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy wajib dipilih dari daftar Office YBS.';
                        continue;
                    }

                    if (!in_array($sampleBoy, $sampleBoyOptions, true)) {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy tidak sesuai daftar Office YBS.';
                        continue;
                    }
                }

                try {
                    if (!$isKegiatanDispek) {
                        $this->validatePengulanganWindow(
                            KernelCalculation::class,
                            $kode,
                            $userOffice,
                            $isPengulangan,
                            'Kernel Losses',
                            $roundedTime
                        );

                        $this->validateMachineWindowForKernelInput(
                            $kode,
                            $userOffice,
                            $roundedTime,
                            'Kernel Losses'
                        );
                    }
                } catch (ValidationException $e) {
                    $firstMessage = collect($e->errors())->flatten()->first();
                    $rowErrors['rows.' . $kode . '.kode'] = $firstMessage ?: "Validasi untuk kode {$kode} gagal.";
                    continue;
                }

                $beratSampel = $this->normalizeKernelNumericValue($row['berat_sampel'] ?? null);
                $nutUtuhNut = $this->normalizeKernelNumericValue($row['nut_utuh_nut'] ?? null);
                $nutUtuhKernel = $this->normalizeKernelNumericValue($row['nut_utuh_kernel'] ?? null);
                $nutPecahNut = $this->normalizeKernelNumericValue($row['nut_pecah_nut'] ?? null);
                $nutPecahKernel = $this->normalizeKernelNumericValue($row['nut_pecah_kernel'] ?? null);
                $kernelUtuh = $this->normalizeKernelNumericValue($row['kernel_utuh'] ?? null);
                $kernelPecah = $this->normalizeKernelNumericValue($row['kernel_pecah'] ?? null);

                if ($beratSampel > 0) {
                    $ktsNutUtuh = round(($nutUtuhKernel / $beratSampel) * 100, 6);
                    $ktsNutPecah = round(($nutPecahKernel / $beratSampel) * 100, 6);
                    $kernelUtuhToSampel = round(($kernelUtuh / $beratSampel) * 100, 6);
                    $kernelPecahToSampel = round(($kernelPecah / $beratSampel) * 100, 6);
                    $kernelLosses = ($ktsNutUtuh + $ktsNutPecah + $kernelUtuhToSampel + $kernelPecahToSampel) / 100;
                    // if ($kernelLosses > 0.04) {
                    //     $rowErrors['rows.' . $kode . '.kode'] = "Total kernel losses untuk kode {$kode} melebihi batas wajar (4%).";
                    //     continue;
                    // }
                } else {
                    $ktsNutUtuh = 0;
                    $ktsNutPecah = 0;
                    $kernelUtuhToSampel = 0;
                    $kernelPecahToSampel = 0;
                    $kernelLosses = 0;
                }

                $kernelCalculationPayload = [
                    'user_id' => Auth::id(),
                    'office' => $userOffice,
                    'rounded_time' => $roundedTime,
                    'kegiatan_dispek' => $isKegiatanDispek,
                    'kode' => $kode,
                    'jenis' => $row['jenis'] ?? null,
                    'operator' => $row['operator'] ?? null,
                    'sampel_boy' => $row['sampel_boy'] ?? Auth::user()->name,
                    'pengulangan' => $isPengulangan,
                    'remarks' => $row['remarks'] ?? null,
                    'berat_sampel' => $beratSampel,
                    'nut_utuh_nut' => $nutUtuhNut,
                    'nut_utuh_kernel' => $nutUtuhKernel,
                    'nut_pecah_nut' => $nutPecahNut,
                    'nut_pecah_kernel' => $nutPecahKernel,
                    'kernel_utuh' => $kernelUtuh,
                    'kernel_pecah' => $kernelPecah,
                    'kernel_to_sampel_nut_utuh' => $ktsNutUtuh,
                    'kernel_to_sampel_nut_pecah' => $ktsNutPecah,
                    'kernel_utuh_to_sampel' => $kernelUtuhToSampel,
                    'kernel_pecah_to_sampel' => $kernelPecahToSampel,
                    'kernel_losses' => $kernelLosses,
                ];
                $kernelCalculation = KernelCalculation::create(
                    $this->prepareCreatePayload($kernelCalculationPayload, KernelCalculation::class, $isPengulangan)
                );

                $savedRows[] = $kernelCalculation;

                $this->logCreate(
                    $kernelCalculation,
                    "Input data kernel losses untuk kode {$kernelCalculation->kode}",
                    ['module' => 'kernel_losses', 'office' => $userOffice]
                );
            }

            if (!empty($rowErrors)) {
                throw ValidationException::withMessages($rowErrors);
            }

            if (empty($savedRows)) {
                throw ValidationException::withMessages([
                    'rows' => 'Tidak ada data yang berhasil disimpan.',
                ]);
            }

            $message = count($savedRows) === 1
                ? '1 data Kernel Losses berhasil disimpan.'
                : count($savedRows) . ' data Kernel Losses berhasil disimpan.';

            $proofData = $this->buildKernelLossesProofData($savedRows[0], $message);
            $proofData['entries'] = $this->buildKernelProofEntries($savedRows);
            $proofData['entries_metrics'] = $this->buildKernelLossesEntriesMetrics($savedRows);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'proof' => $proofData]);
            }

            return redirect()->route('kernel.index')
                ->with('success', $message)
                ->with('success_proof', $proofData);
        });
    }

    private function hasAnyValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    private function normalizeKernelNumericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return 0.0;
            }
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    public function edit(KernelCalculation $kernelCalculation)
    {
        $this->ensureCanEditKernelLosses();

        $kodeOptions = $this->getKernelLossesKodeOptions($kernelCalculation->office);
        $jenisOptions = KernelRecord::getJenisOptions();
        $operatorOptions = $this->getOperatorOptionsByOffice($kernelCalculation->office, 'kernel');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($kernelCalculation->office);

        return view('kernel.edit', compact('kernelCalculation', 'kodeOptions', 'jenisOptions', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function update(Request $request, KernelCalculation $kernelCalculation)
    {
        $this->ensureCanEditKernelLosses();

        $oldAttributes = $kernelCalculation->getOriginal();

        $validated = $request->validate([
            'kode' => ['required', 'string', Rule::in(array_keys($this->getKernelLossesKodeOptions($kernelCalculation->office)))],
            'jenis' => 'required|string',
            'operator' => $this->getOperatorValidationRules($kernelCalculation->office, true, 'kernel'),
            'sampel_boy' => $this->getSampleBoyValidationRules($kernelCalculation->office),
            'berat_sampel' => 'required|numeric|min:0',
            'nut_utuh_nut' => 'required|numeric|min:0',
            'nut_utuh_kernel' => 'required|numeric|min:0',
            'nut_pecah_nut' => 'required|numeric|min:0',
            'nut_pecah_kernel' => 'required|numeric|min:0',
            'kernel_utuh' => 'required|numeric|min:0',
            'kernel_pecah' => 'required|numeric|min:0',
        ], [
            'sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
        ]);

        $beratSampel = $validated['berat_sampel'];
        $ktsNutUtuh = $beratSampel > 0 ? round(($validated['nut_utuh_kernel'] / $beratSampel) * 100, 6) : 0;
        $ktsNutPecah = $beratSampel > 0 ? round(($validated['nut_pecah_kernel'] / $beratSampel) * 100, 6) : 0;
        $kernelUtuhToSampel = $beratSampel > 0 ? round(($validated['kernel_utuh'] / $beratSampel) * 100, 6) : 0;
        $kernelPecahToSampel = $beratSampel > 0 ? round(($validated['kernel_pecah'] / $beratSampel) * 100, 6) : 0;
        $kernelLosses = ($ktsNutUtuh + $ktsNutPecah + $kernelUtuhToSampel + $kernelPecahToSampel) / 100;

        $kernelCalculation->update([
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
            'operator' => $validated['operator'],
            'sampel_boy' => $validated['sampel_boy'],
            'berat_sampel' => $beratSampel,
            'nut_utuh_nut' => $validated['nut_utuh_nut'],
            'nut_utuh_kernel' => $validated['nut_utuh_kernel'],
            'nut_pecah_nut' => $validated['nut_pecah_nut'],
            'nut_pecah_kernel' => $validated['nut_pecah_kernel'],
            'kernel_utuh' => $validated['kernel_utuh'],
            'kernel_pecah' => $validated['kernel_pecah'],
            'kernel_to_sampel_nut_utuh' => $ktsNutUtuh,
            'kernel_to_sampel_nut_pecah' => $ktsNutPecah,
            'kernel_utuh_to_sampel' => $kernelUtuhToSampel,
            'kernel_pecah_to_sampel' => $kernelPecahToSampel,
            'kernel_losses' => $kernelLosses,
        ]);

        $this->logUpdate(
            $kernelCalculation,
            $oldAttributes,
            "Update data kernel losses kode {$kernelCalculation->kode}",
            ['module' => 'kernel_losses', 'office' => $kernelCalculation->office]
        );

        return redirect()->route('kernel.index')->with('success', 'Data Kernel Losses berhasil diperbarui.');
    }

    public function dirtMoistIndex(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelDirtMoistCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->orderBy('created_at', 'desc');

        $this->applyOfficeFilter($query, $officeFilter);

        if ($request->filled('kode')) {
            $query->where('kode', $request->kode);
        }

        if (!Auth::user()->can('view kernel losses')) {
            $query->where('user_id', Auth::id());
        }

        $totalRows = (clone $query)->count();
        $todayStart = Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $todayEnd = Carbon::today()->endOfDay()->format('Y-m-d H:i:s');

        $statistics = [
            'total_records' => $totalRows,
            'records_today' => KernelDirtMoistCalculation::whereBetween('created_at', [$todayStart, $todayEnd])
                ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
                ->count(),
            'calculations_count' => $totalRows,
        ];

        $dirtMoistCalculations = $query->paginate(15, ['*'], 'calculations');
        $dirtMoistCalculations->appends($request->only(['start_date', 'end_date', 'kode', 'office']));

        $kodeOptions = $this->getDirtMoistKodeOptions($officeFilter);
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');

        return view('kernel.dirt-moist.index', compact(
            'dirtMoistCalculations',
            'statistics',
            'kodeOptions',
            'masterData',
            'startDate',
            'endDate',
            'officeFilter'
        ));
    }

    public function dirtMoistCreate()
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data dirt & moist.');
        }

        $userOffice = $this->getUserOffice();
        $kodeOptions = $this->getDirtMoistKodeOptions($userOffice);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);
        $sampleBoyRequired = $userOffice === 'YBS' && !empty($sampleBoyOptions);
        $kodeFormGroups = $this->getDirtMoistFormGroups($kodeOptions, $userOffice);
        $jenisOptions = KernelRecord::getJenisOptions();
        $kernelLimitMap = $this->getDirtMoistLimitMap($userOffice);
        $operatorOptions = $this->getOperatorOptionsByOffice($userOffice, 'dirt_moist');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);

        return view('kernel.dirt-moist.create', compact('kodeOptions', 'kodeFormGroups', 'jenisOptions', 'kernelLimitMap', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function dirtMoistStore(Request $request)
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data dirt & moist.');
        }

        $userOffice = $this->getUserOffice();
        if (!$userOffice) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda harus memiliki Office yang ditentukan untuk dapat input data.'], 422);
            }
            return back()
                ->withInput()
                ->with('error', 'Anda harus memiliki Office yang ditentukan untuk dapat input data. Silakan hubungi Administrator.');
        }

        $kodeOptions = $this->getDirtMoistKodeOptions($userOffice);

        $validated = $request->validate([
            'kegiatan_dispek' => 'nullable|boolean',
            'tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rounded_time' => 'nullable|date_format:H:i',
            'rows' => 'required|array',
            'rows.*.kode' => ['required', 'string', Rule::in(array_keys($kodeOptions))],
            'rows.*.tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rows.*.rounded_time' => 'nullable|date_format:H:i',
            'rows.*.jenis' => 'nullable|string',
            'rows.*.operator' => $this->getOperatorValidationRules($userOffice, false, 'dirt_moist'),
            'rows.*.sampel_boy' => $sampleBoyRequired
                ? $this->getSampleBoyValidationRules($userOffice, false)
                : $this->getSampleBoyValidationRules($userOffice),
            'rows.*.pengulangan' => 'nullable|boolean',
            'rows.*.remarks' => 'nullable|string|max:500',
            'rows.*.berat_sampel' => 'nullable|numeric|gt:0',
            'rows.*.berat_dirty' => 'nullable|numeric|min:0',
            'rows.*.moist_percent' => 'nullable|numeric|min:0',
        ], [
            'rows.required' => 'Data sampel belum tersedia.',
            'rows.*.kode.required' => 'Kode tidak boleh kosong.',
            'rows.*.kode.in' => 'Kode Dirt & Moist tidak valid.',
            'rows.*.operator.in' => 'Operator tidak sesuai dengan daftar office.',
            'rows.*.sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'rows.*.sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
            'rounded_time.date_format' => 'Format jam pengambilan harus HH:MM.',
            'rows.*.berat_sampel.gt' => 'Berat sampel harus lebih dari 0.',
        ]);

        $isKegiatanDispek = (bool) $request->boolean('kegiatan_dispek');
        if ($isKegiatanDispek && empty($validated['rounded_time'])) {
            throw ValidationException::withMessages([
                'rounded_time' => 'Jam pengambilan wajib diisi jika kegiatan dispatch dicentang.',
            ]);
        }

        $roundedTime = $this->resolveKernelSampleTimestamp(
            $row['tanggal_sampel'] ?? $validated['tanggal_sampel'] ?? null,
            $row['rounded_time'] ?? $validated['rounded_time'] ?? null,
            $isKegiatanDispek || $userOffice === 'YBS'
        );
        $rowsToSave = [];

        foreach (($validated['rows'] ?? []) as $row) {
            $kode = (string) ($row['kode'] ?? '');
            $requiredFields = ['berat_sampel', 'berat_dirty'];
            if (str_starts_with($kode, 'OUT')) {
                $requiredFields[] = 'moist_percent';
            }

            $hasInput = false;
            foreach ($requiredFields as $field) {
                if ($this->hasAnyValue($row[$field] ?? null)) {
                    $hasInput = true;
                    break;
                }
            }

            if ($hasInput) {
                $rowsToSave[] = $row;
            }
        }

        if (empty($rowsToSave)) {
            throw ValidationException::withMessages([
                'rows' => 'Silakan isi minimal satu form Dirt & Moist.',
            ]);
        }

        $limitMap = $this->getDirtMoistLimitMap($userOffice);

        return DB::transaction(function () use ($request, $userOffice, $roundedTime, $rowsToSave, $limitMap, $requiredFields, $isKegiatanDispek, $sampleBoyRequired, $sampleBoyOptions) {
            $rowErrors = [];
            $savedRows = [];

            foreach ($rowsToSave as $row) {
                $kode = (string) ($row['kode'] ?? '');
                if ($sampleBoyRequired) {
                    $sampleBoy = trim((string) ($row['sampel_boy'] ?? ''));

                    if ($sampleBoy === '') {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy wajib dipilih dari daftar Office YBS.';
                        continue;
                    }

                    if (!in_array($sampleBoy, $sampleBoyOptions, true)) {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy tidak sesuai daftar Office YBS.';
                        continue;
                    }
                }
                $requiredFields = ['berat_sampel', 'berat_dirty'];
                if (str_starts_with($kode, 'OUT')) {
                    $requiredFields[] = 'moist_percent';
                }

                foreach ($requiredFields as $field) {
                    if (!$this->hasAnyValue($row[$field] ?? null)) {
                        $rowErrors['rows.' . $kode . '.' . $field] = "Field {$field} wajib diisi untuk kode {$kode}.";
                    }
                }

                if (
                    array_key_exists('rows.' . $kode . '.berat_sampel', $rowErrors)
                    || array_key_exists('rows.' . $kode . '.berat_dirty', $rowErrors)
                    || array_key_exists('rows.' . $kode . '.moist_percent', $rowErrors)
                ) {
                    continue;
                }

                $isPengulangan = (bool) ($row['pengulangan'] ?? false);

                try {
                    if (!$isKegiatanDispek) {
                        $this->validatePengulanganWindow(
                            KernelDirtMoistCalculation::class,
                            $kode,
                            $userOffice,
                            $isPengulangan,
                            'Dirt & Moist',
                            $roundedTime
                        );

                        $this->validateMachineWindowForKernelInput(
                            $kode,
                            $userOffice,
                            $roundedTime,
                            'Dirt & Moist'
                        );
                    }
                } catch (ValidationException $e) {
                    $firstMessage = collect($e->errors())->flatten()->first();
                    $rowErrors['rows.' . $kode . '.kode'] = $firstMessage ?: "Validasi untuk kode {$kode} gagal.";
                    continue;
                }

                $beratSampel = (float) $row['berat_sampel'];
                $beratDirty = (float) $row['berat_dirty'];
                $moistPercent = $this->hasAnyValue($row['moist_percent'] ?? null) ? (float) $row['moist_percent'] : null;
                $dirtyToSampel = round(($beratDirty / $beratSampel) * 100, 6);

                $limitConfig = $limitMap[$kode] ?? ['dirty' => null, 'moist' => null];

                // if ($dirtyToSampel > 16) {
                //     $rowErrors['rows.' . $kode . '.kode'] = "Total kernel losses untuk kode {$kode} melebihi batas wajar (16%).";
                //     continue;
                // }

                $dirtMoistPayload = [
                    'user_id' => Auth::id(),
                    'office' => $userOffice,
                    'rounded_time' => $roundedTime,
                    'kegiatan_dispek' => $isKegiatanDispek,
                    'kode' => $kode,
                    'jenis' => $row['jenis'] ?? null,
                    'operator' => $row['operator'] ?? null,
                    'sampel_boy' => $row['sampel_boy'] ?? Auth::user()->name,
                    'pengulangan' => $isPengulangan,
                    'remarks' => $row['remarks'] ?? null,
                    'berat_sampel' => $beratSampel,
                    'berat_dirty' => $beratDirty,
                    'dirty_to_sampel' => $dirtyToSampel,
                    'moist_percent' => $moistPercent,
                    'dirty_limit_operator' => data_get($limitConfig, 'dirty.operator'),
                    'dirty_limit_value' => data_get($limitConfig, 'dirty.value'),
                    'moist_limit_operator' => data_get($limitConfig, 'moist.operator'),
                    'moist_limit_value' => data_get($limitConfig, 'moist.value'),
                ];
                $dirtMoistCalculation = KernelDirtMoistCalculation::create(
                    $this->prepareCreatePayload($dirtMoistPayload, KernelDirtMoistCalculation::class, $isPengulangan)
                );

                $savedRows[] = $dirtMoistCalculation;

                $this->logCreate(
                    $dirtMoistCalculation,
                    "Input data dirt & moist untuk kode {$dirtMoistCalculation->kode}",
                    ['module' => 'dirt_moist', 'office' => $userOffice]
                );
            }

            if (!empty($rowErrors)) {
                throw ValidationException::withMessages($rowErrors);
            }

            if (empty($savedRows)) {
                throw ValidationException::withMessages([
                    'rows' => 'Tidak ada data Dirt & Moist yang berhasil disimpan.',
                ]);
            }

            $message = count($savedRows) === 1
                ? '1 data dirt & moist berhasil disimpan.'
                : count($savedRows) . ' data dirt & moist berhasil disimpan.';

            $proofData = $this->buildDirtMoistProofData($savedRows[0], $message);
            $proofData['entries'] = $this->buildKernelProofEntries($savedRows);
            $proofData['entries_metrics'] = $this->buildDirtMoistEntriesMetrics($savedRows);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'proof' => $proofData]);
            }

            return redirect()->route('kernel.dirt-moist.index')
                ->with('success', $message)
                ->with('success_proof', $proofData);
        });
    }

    public function dirtMoistEdit(KernelDirtMoistCalculation $dirtMoistCalculation)
    {
        $this->ensureCanEditKernelLosses();

        $kodeOptions = $this->getDirtMoistKodeOptions($dirtMoistCalculation->office);
        $jenisOptions = KernelRecord::getJenisOptions();
        $operatorOptions = $this->getOperatorOptionsByOffice($dirtMoistCalculation->office, 'dirt_moist');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($dirtMoistCalculation->office);

        return view('kernel.dirt-moist.edit', compact('dirtMoistCalculation', 'kodeOptions', 'jenisOptions', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function dirtMoistUpdate(Request $request, KernelDirtMoistCalculation $dirtMoistCalculation)
    {
        $this->ensureCanEditKernelLosses();

        $oldAttributes = $dirtMoistCalculation->getOriginal();

        $validated = $request->validate([
            'kode' => ['required', 'string', Rule::in(array_keys($this->getDirtMoistKodeOptions($dirtMoistCalculation->office)))],
            'jenis' => 'required|string',
            'operator' => $this->getOperatorValidationRules($dirtMoistCalculation->office, true, 'dirt_moist'),
            'sampel_boy' => $this->getSampleBoyValidationRules($dirtMoistCalculation->office),
            'berat_sampel' => 'required|numeric|gt:0',
            'berat_dirty' => 'required|numeric|min:0',
            'moist_percent' => 'nullable|numeric|min:0',
        ], [
            'sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
        ]);

        $dirtyToSampel = round(($validated['berat_dirty'] / $validated['berat_sampel']) * 100, 6);
        $limitMap = $this->getDirtMoistLimitMap($dirtMoistCalculation->office);
        $limitConfig = $limitMap[$validated['kode']] ?? ['dirty' => null, 'moist' => null];
        $requiredFields = ['berat_sampel', 'berat_dirty'];
        if (str_starts_with($validated['kode'], 'OUT')) {
            $requiredFields[] = 'moist_percent';
        }

        foreach ($requiredFields as $field) {
            if (!$this->hasAnyValue($validated[$field] ?? null)) {
                throw ValidationException::withMessages([
                    $field => "Field {$field} wajib diisi untuk kode {$validated['kode']}.",
                ]);
            }
        }

        $moistPercent = array_key_exists('moist_percent', $validated)
            ? $validated['moist_percent']
            : $dirtMoistCalculation->moist_percent;

        $dirtMoistCalculation->update([
            'user_id' => Auth::id(),
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
            'operator' => $validated['operator'],
            'sampel_boy' => $validated['sampel_boy'] ?? $dirtMoistCalculation->sampel_boy,
            'berat_sampel' => $validated['berat_sampel'],
            'berat_dirty' => $validated['berat_dirty'],
            'dirty_to_sampel' => $dirtyToSampel,
            'moist_percent' => $moistPercent,
            'dirty_limit_operator' => data_get($limitConfig, 'dirty.operator'),
            'dirty_limit_value' => data_get($limitConfig, 'dirty.value'),
            'moist_limit_operator' => data_get($limitConfig, 'moist.operator'),
            'moist_limit_value' => data_get($limitConfig, 'moist.value'),
        ]);

        $this->logUpdate(
            $dirtMoistCalculation,
            $oldAttributes,
            "Update data dirt & moist kode {$dirtMoistCalculation->kode}",
            ['module' => 'dirt_moist', 'office' => $dirtMoistCalculation->office]
        );

        return redirect()->route('kernel.dirt-moist.index')->with('success', 'Data Dirt & Moist berhasil diperbarui.');
    }

    public function dirtMoistDestroy(KernelDirtMoistCalculation $dirtMoistCalculation)
    {
        $this->ensureCanDeleteKernelLosses();

        $this->logDelete(
            $dirtMoistCalculation,
            "Hapus data dirt & moist kode {$dirtMoistCalculation->kode}",
            ['module' => 'dirt_moist', 'office' => $dirtMoistCalculation->office]
        );

        $dirtMoistCalculation->delete();

        return back()->with('success', 'Data Dirt & Moist berhasil dihapus.');
    }

    public function qwtIndex(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelQwt::with('user')
            ->whereBetween('created_at', $range)
            ->orderBy('created_at', 'desc');

        $this->applyOfficeFilter($query, $officeFilter);

        if ($request->filled('kode')) {
            $query->where('kode', $request->kode);
        }

        if (!Auth::user()->can('view kernel losses')) {
            $query->where('user_id', Auth::id());
        }

        $totalRows = (clone $query)->count();
        $todayStart = Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $todayEnd = Carbon::today()->endOfDay()->format('Y-m-d H:i:s');

        $statistics = [
            'total_records' => $totalRows,
            'records_today' => KernelQwt::whereBetween('created_at', [$todayStart, $todayEnd])
                ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
                ->count(),
            'calculations_count' => $totalRows,
        ];

        $kernelQwtRows = $query->paginate(15, ['*'], 'calculations');
        $kernelQwtRows->appends($request->only(['start_date', 'end_date', 'kode', 'office']));

        $kodeOptions = $this->getQwtKodeOptions($officeFilter);
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');

        return view('kernel.qwt.index', compact(
            'kernelQwtRows',
            'statistics',
            'kodeOptions',
            'masterData',
            'startDate',
            'endDate',
            'officeFilter'
        ));
    }

    public function qwtCreate()
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data QWT Fibre Press.');
        }

        $userOffice = $this->getUserOffice();
        $kodeOptions = $this->getQwtKodeOptions($userOffice);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);
        $sampleBoyRequired = $userOffice === 'YBS' && !empty($sampleBoyOptions);
        $kodeFormGroups = $this->getQwtFormGroups($kodeOptions, $userOffice);
        $jenisOptions = KernelRecord::getJenisOptions();
        $kernelLimitMap = $this->getQwtLimitMap($userOffice);
        $operatorOptions = $this->getOperatorOptionsByOffice($userOffice, 'qwt');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);

        return view('kernel.qwt.create', compact('kodeOptions', 'kodeFormGroups', 'jenisOptions', 'kernelLimitMap', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function qwtStore(Request $request)
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data QWT Fibre Press.');
        }

        $userOffice = $this->getUserOffice();
        if (!$userOffice) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda harus memiliki Office yang ditentukan untuk dapat input data.'], 422);
            }
            return back()
                ->withInput()
                ->with('error', 'Anda harus memiliki Office yang ditentukan untuk dapat input data. Silakan hubungi Administrator.');
        }

        $kodeOptions = $this->getQwtKodeOptions($userOffice);

        $validated = $request->validate([
            'kegiatan_dispek' => 'nullable|boolean',
            'tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rounded_time' => 'nullable|date_format:H:i',
            'rows' => 'required|array',
            'rows.*.kode' => ['required', 'string', Rule::in(array_keys($kodeOptions))],
            'rows.*.tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rows.*.rounded_time' => 'nullable|date_format:H:i',
            'rows.*.jenis' => 'nullable|string',
            'rows.*.operator' => $this->getOperatorValidationRules($userOffice, false, 'qwt'),
            'rows.*.sampel_boy' => $sampleBoyRequired
                ? $this->getSampleBoyValidationRules($userOffice, false)
                : $this->getSampleBoyValidationRules($userOffice),
            'rows.*.pengulangan' => 'nullable|boolean',
            'rows.*.remarks' => 'nullable|string|max:500',
            'rows.*.sampel_setelah_kuarter' => 'nullable|numeric|gt:0',
            'rows.*.berat_nut_utuh' => 'nullable|numeric|min:0',
            'rows.*.berat_nut_pecah' => 'nullable|numeric|min:0',
            'rows.*.berat_kernel_utuh' => 'nullable|numeric|min:0',
            'rows.*.berat_kernel_pecah' => 'nullable|numeric|min:0',
            'rows.*.berat_cangkang' => 'nullable|numeric|min:0',
            'rows.*.berat_batu' => 'nullable|numeric|min:0',
            'rows.*.ampere_screw' => 'nullable|numeric|min:0',
            'rows.*.tekanan_hydraulic' => 'nullable|numeric|min:0',
        ], [
            'rows.required' => 'Data sampel belum tersedia.',
            'rows.*.kode.required' => 'Kode tidak boleh kosong.',
            'rows.*.kode.in' => 'Kode QWT tidak valid.',
            'rows.*.operator.in' => 'Operator tidak sesuai dengan daftar office.',
            'rows.*.sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'rows.*.sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
            'rounded_time.date_format' => 'Format jam pengambilan harus HH:MM.',
            'rows.*.sampel_setelah_kuarter.gt' => 'Sampel setelah kuarter harus lebih dari 0.',
        ]);

        $isKegiatanDispek = (bool) $request->boolean('kegiatan_dispek');
        if ($isKegiatanDispek && empty($validated['rounded_time'])) {
            throw ValidationException::withMessages([
                'rounded_time' => 'Jam pengambilan wajib diisi jika kegiatan dispatch dicentang.',
            ]);
        }

        $roundedTime = $this->resolveKernelSampleTimestamp(
            $row['tanggal_sampel'] ?? $validated['tanggal_sampel'] ?? null,
            $row['rounded_time'] ?? $validated['rounded_time'] ?? null,
            $isKegiatanDispek || $userOffice === 'YBS'
        );
        $requiredFields = [
            'sampel_setelah_kuarter',
            'berat_nut_utuh',
            'berat_nut_pecah',
            'berat_kernel_utuh',
            'berat_kernel_pecah',
            'berat_cangkang',
            'berat_batu',
            'ampere_screw',
            'tekanan_hydraulic',
        ];

        $rowsToSave = [];
        foreach (($validated['rows'] ?? []) as $row) {
            $hasInput = false;
            foreach ($requiredFields as $field) {
                if ($this->hasAnyValue($row[$field] ?? null)) {
                    $hasInput = true;
                    break;
                }
            }

            if ($hasInput) {
                $rowsToSave[] = $row;
            }
        }

        if (empty($rowsToSave)) {
            throw ValidationException::withMessages([
                'rows' => 'Silakan isi minimal satu form QWT Fibre Press.',
            ]);
        }

        $limitMap = $this->getQwtLimitMap($userOffice);

        return DB::transaction(function () use ($request, $userOffice, $roundedTime, $rowsToSave, $limitMap, $requiredFields, $isKegiatanDispek, $sampleBoyRequired, $sampleBoyOptions) {
            $rowErrors = [];
            $savedRows = [];

            foreach ($rowsToSave as $row) {
                $kode = (string) ($row['kode'] ?? '');

                if ($sampleBoyRequired) {
                    $sampleBoy = trim((string) ($row['sampel_boy'] ?? ''));

                    if ($sampleBoy === '') {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy wajib dipilih dari daftar Office YBS.';
                        continue;
                    }

                    if (!in_array($sampleBoy, $sampleBoyOptions, true)) {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy tidak sesuai daftar Office YBS.';
                        continue;
                    }
                }

                foreach ($requiredFields as $field) {
                    if (!$this->hasAnyValue($row[$field] ?? null)) {
                        $rowErrors['rows.' . $kode . '.' . $field] = "Field {$field} wajib diisi untuk kode {$kode}.";
                    }
                }

                $requiredErrorCount = collect($requiredFields)
                    ->filter(fn($field) => array_key_exists('rows.' . $kode . '.' . $field, $rowErrors))
                    ->count();
                if ($requiredErrorCount > 0) {
                    continue;
                }

                $isPengulangan = (bool) ($row['pengulangan'] ?? false);

                try {
                    if (!$isKegiatanDispek) {
                        $this->validatePengulanganWindow(
                            KernelQwt::class,
                            $kode,
                            $userOffice,
                            $isPengulangan,
                            'QWT Fibre Press',
                            $roundedTime
                        );

                        $this->validateMachineWindowForKernelInput(
                            $kode,
                            $userOffice,
                            $roundedTime,
                            'QWT Fibre Press'
                        );
                    }
                } catch (ValidationException $e) {
                    $firstMessage = collect($e->errors())->flatten()->first();
                    $rowErrors['rows.' . $kode . '.kode'] = $firstMessage ?: "Validasi untuk kode {$kode} gagal.";
                    continue;
                }

                $sampelSetelahKuarter = (float) $row['sampel_setelah_kuarter'];
                $beratNutUtuh = (float) $row['berat_nut_utuh'];
                $beratNutPecah = (float) $row['berat_nut_pecah'];
                $beratKernelUtuh = (float) $row['berat_kernel_utuh'];
                $beratKernelPecah = (float) $row['berat_kernel_pecah'];
                $beratCangkang = (float) $row['berat_cangkang'];
                $beratBatu = (float) $row['berat_batu'];
                $ampereScrew = (float) $row['ampere_screw'];
                $tekananHydraulic = (float) $row['tekanan_hydraulic'];

                $totalBeratNut = round(
                    $beratNutUtuh + $beratNutPecah + $beratKernelUtuh + $beratKernelPecah + $beratCangkang,
                    6
                );
                $beratFiber = round($sampelSetelahKuarter - $totalBeratNut, 6);
                $beratBrokenNut = round(
                    $beratNutPecah + $beratKernelUtuh + $beratKernelPecah + $beratCangkang,
                    6
                );
                $bnTn = $totalBeratNut > 0 ? round(($beratBrokenNut / $totalBeratNut) * 100, 6) : 0;

                // if ($bnTn > 40) {
                //     $rowErrors['rows.' . $kode . '.kode'] = "Total kernel losses untuk kode {$kode} melebihi batas wajar (40%).";
                //     continue;
                // }

                $kernelQwtPayload = [
                    'user_id' => Auth::id(),
                    'office' => $userOffice,
                    'rounded_time' => $roundedTime,
                    'kegiatan_dispek' => $isKegiatanDispek,
                    'kode' => $kode,
                    'jenis' => $row['jenis'] ?? null,
                    'operator' => $row['operator'] ?? null,
                    'sampel_boy' => $row['sampel_boy'] ?? Auth::user()->name,
                    'pengulangan' => $isPengulangan,
                    'remarks' => $row['remarks'] ?? null,
                    'sampel_setelah_kuarter' => $sampelSetelahKuarter,
                    'berat_nut_utuh' => $beratNutUtuh,
                    'berat_nut_pecah' => $beratNutPecah,
                    'berat_kernel_utuh' => $beratKernelUtuh,
                    'berat_kernel_pecah' => $beratKernelPecah,
                    'berat_cangkang' => $beratCangkang,
                    'berat_batu' => $beratBatu,
                    'berat_fiber' => $beratFiber,
                    'berat_broken_nut' => $beratBrokenNut,
                    'total_berat_nut' => $totalBeratNut,
                    'bn_tn' => $bnTn,
                    'ampere_screw' => $ampereScrew,
                    'tekanan_hydraulic' => $tekananHydraulic,
                    'bn_tn_limit_operator' => data_get($limitMap, $kode . '.bn_tn.operator', 'le'),
                    'bn_tn_limit_value' => data_get($limitMap, $kode . '.bn_tn.value'),
                    'moist_limit_operator' => data_get($limitMap, $kode . '.moist.operator', 'le'),
                    'moist_limit_value' => data_get($limitMap, $kode . '.moist.value'),
                ];
                $kernelQwt = KernelQwt::create(
                    $this->prepareCreatePayload($kernelQwtPayload, KernelQwt::class, $isPengulangan)
                );

                $savedRows[] = $kernelQwt;

                $this->logCreate(
                    $kernelQwt,
                    "Input data QWT Fibre Press untuk kode {$kernelQwt->kode}",
                    ['module' => 'qwt', 'office' => $userOffice]
                );
            }

            if (!empty($rowErrors)) {
                throw ValidationException::withMessages($rowErrors);
            }

            if (empty($savedRows)) {
                throw ValidationException::withMessages([
                    'rows' => 'Tidak ada data QWT Fibre Press yang berhasil disimpan.',
                ]);
            }

            $message = count($savedRows) === 1
                ? '1 data QWT Fibre Press berhasil disimpan.'
                : count($savedRows) . ' data QWT Fibre Press berhasil disimpan.';

            $proofData = $this->buildQwtProofData($savedRows[0], $message);
            $proofData['entries'] = $this->buildKernelProofEntries($savedRows);
            $proofData['entries_metrics'] = $this->buildQwtEntriesMetrics($savedRows);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'proof' => $proofData]);
            }

            return redirect()->route('kernel.qwt.index')
                ->with('success', $message)
                ->with('success_proof', $proofData);
        });
    }

    public function qwtEdit(KernelQwt $kernelQwt)
    {
        $this->ensureCanEditKernelLosses();

        $kodeOptions = $this->getQwtKodeOptions($kernelQwt->office);
        $jenisOptions = KernelRecord::getJenisOptions();
        $operatorOptions = $this->getOperatorOptionsByOffice($kernelQwt->office, 'qwt');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($kernelQwt->office);

        return view('kernel.qwt.edit', compact('kernelQwt', 'kodeOptions', 'jenisOptions', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function qwtUpdate(Request $request, KernelQwt $kernelQwt)
    {
        $this->ensureCanEditKernelLosses();

        $oldAttributes = $kernelQwt->getOriginal();

        $validated = $request->validate([
            'kode' => ['required', 'string', Rule::in(array_keys($this->getQwtKodeOptions($kernelQwt->office)))],
            'jenis' => 'required|string',
            'operator' => $this->getOperatorValidationRules($kernelQwt->office, true, 'qwt'),
            'sampel_boy' => $this->getSampleBoyValidationRules($kernelQwt->office),
            'sampel_setelah_kuarter' => 'required|numeric|gt:0',
            'berat_nut_utuh' => 'required|numeric|min:0',
            'berat_nut_pecah' => 'required|numeric|min:0',
            'berat_kernel_utuh' => 'required|numeric|min:0',
            'berat_kernel_pecah' => 'required|numeric|min:0',
            'berat_cangkang' => 'required|numeric|min:0',
            'berat_batu' => 'required|numeric|min:0',
            'ampere_screw' => 'required|numeric|min:0',
            'tekanan_hydraulic' => 'required|numeric|min:0',
        ], [
            'sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
        ]);

        $totalBeratNut = round(
            $validated['berat_nut_utuh']
            + $validated['berat_nut_pecah']
            + $validated['berat_kernel_utuh']
            + $validated['berat_kernel_pecah']
            + $validated['berat_cangkang'],
            6
        );

        $beratFiber = round($validated['sampel_setelah_kuarter'] - $totalBeratNut, 6);
        $beratBrokenNut = round(
            $validated['berat_nut_pecah']
            + $validated['berat_kernel_utuh']
            + $validated['berat_kernel_pecah']
            + $validated['berat_cangkang'],
            6
        );
        $bnTn = $totalBeratNut > 0 ? round(($beratBrokenNut / $totalBeratNut) * 100, 6) : 0;

        $limitMap = $this->getQwtLimitMap($kernelQwt->office);
        $kode = $validated['kode'];

        $kernelQwt->update([
            'kode' => $kode,
            'jenis' => $validated['jenis'],
            'operator' => $validated['operator'],
            'sampel_boy' => $validated['sampel_boy'],
            'sampel_setelah_kuarter' => $validated['sampel_setelah_kuarter'],
            'berat_nut_utuh' => $validated['berat_nut_utuh'],
            'berat_nut_pecah' => $validated['berat_nut_pecah'],
            'berat_kernel_utuh' => $validated['berat_kernel_utuh'],
            'berat_kernel_pecah' => $validated['berat_kernel_pecah'],
            'berat_cangkang' => $validated['berat_cangkang'],
            'berat_batu' => $validated['berat_batu'],
            'berat_fiber' => $beratFiber,
            'berat_broken_nut' => $beratBrokenNut,
            'total_berat_nut' => $totalBeratNut,
            'bn_tn' => $bnTn,
            'ampere_screw' => $validated['ampere_screw'],
            'tekanan_hydraulic' => $validated['tekanan_hydraulic'],
            'bn_tn_limit_operator' => data_get($limitMap, $kode . '.bn_tn.operator', 'le'),
            'bn_tn_limit_value' => data_get($limitMap, $kode . '.bn_tn.value'),
            'moist_limit_operator' => data_get($limitMap, $kode . '.moist.operator', 'le'),
            'moist_limit_value' => data_get($limitMap, $kode . '.moist.value'),
        ]);

        $this->logUpdate(
            $kernelQwt,
            $oldAttributes,
            "Update data QWT Fibre Press kode {$kernelQwt->kode}",
            ['module' => 'qwt', 'office' => $kernelQwt->office]
        );

        return redirect()->route('kernel.qwt.index')->with('success', 'Data QWT Fibre Press berhasil diperbarui.');
    }

    public function qwtDestroy(KernelQwt $kernelQwt)
    {
        $this->ensureCanDeleteKernelLosses();

        $this->logDelete(
            $kernelQwt,
            "Hapus data QWT Fibre Press kode {$kernelQwt->kode}",
            ['module' => 'qwt', 'office' => $kernelQwt->office]
        );

        $kernelQwt->delete();

        return back()->with('success', 'Data QWT Fibre Press berhasil dihapus.');
    }

    public function rippleMillIndex(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelRippleMill::with('user')
            ->whereBetween('created_at', $range)
            ->orderBy('created_at', 'desc');

        $this->applyOfficeFilter($query, $officeFilter);

        if ($request->filled('kode')) {
            $query->where('kode', $request->kode);
        }

        if (!Auth::user()->can('view kernel losses')) {
            $query->where('user_id', Auth::id());
        }

        $totalRows = (clone $query)->count();
        $todayStart = Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $todayEnd = Carbon::today()->endOfDay()->format('Y-m-d H:i:s');

        $statistics = [
            'total_records' => $totalRows,
            'records_today' => KernelRippleMill::whereBetween('created_at', [$todayStart, $todayEnd])
                ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
                ->count(),
            'calculations_count' => $totalRows,
        ];

        $rippleMillRows = $query->paginate(15, ['*'], 'calculations');
        $rippleMillRows->appends($request->only(['start_date', 'end_date', 'kode', 'office']));

        $kodeOptions = $this->getRippleMillKodeOptions($officeFilter);
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');

        return view('kernel.ripple-mill.index', compact(
            'rippleMillRows',
            'statistics',
            'kodeOptions',
            'masterData',
            'startDate',
            'endDate',
            'officeFilter'
        ));
    }

    public function rippleMillCreate()
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data Ripple Mill.');
        }

        $userOffice = $this->getUserOffice();
        $kodeOptions = $this->getRippleMillKodeOptions($userOffice);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);
        $sampleBoyRequired = $userOffice === 'YBS' && !empty($sampleBoyOptions);
        $kodeFormGroups = $this->getRippleMillFormGroups($kodeOptions, $userOffice);
        $jenisOptions = KernelRecord::getJenisOptions();
        $kernelLimitMap = $this->getRippleMillLimitMap($userOffice);
        $operatorOptions = $this->getOperatorOptionsByOffice($userOffice, 'ripple_mill');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);

        return view('kernel.ripple-mill.create', compact('kodeOptions', 'kodeFormGroups', 'jenisOptions', 'kernelLimitMap', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function rippleMillStore(Request $request)
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data Ripple Mill.');
        }

        $userOffice = $this->getUserOffice();
        if (!$userOffice) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda harus memiliki Office yang ditentukan untuk dapat input data.'], 422);
            }
            return back()
                ->withInput()
                ->with('error', 'Anda harus memiliki Office yang ditentukan untuk dapat input data. Silakan hubungi Administrator.');
        }

        $kodeOptions = $this->getRippleMillKodeOptions($userOffice);

        $validated = $request->validate([
            'kegiatan_dispek' => 'nullable|boolean',
            'tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rounded_time' => 'nullable|date_format:H:i',
            'rows' => 'required|array',
            'rows.*.kode' => ['required', 'string', Rule::in(array_keys($kodeOptions))],
            'rows.*.tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rows.*.rounded_time' => 'nullable|date_format:H:i',
            'rows.*.jenis' => 'nullable|string',
            'rows.*.operator' => $this->getOperatorValidationRules($userOffice, false, 'ripple_mill'),
            'rows.*.sampel_boy' => $sampleBoyRequired
                ? $this->getSampleBoyValidationRules($userOffice, false)
                : $this->getSampleBoyValidationRules($userOffice),
            'rows.*.pengulangan' => 'nullable|boolean',
            'rows.*.remarks' => 'nullable|string|max:500',
            'rows.*.berat_sampel' => 'nullable|numeric|gt:0',
            'rows.*.berat_nut_utuh' => 'nullable|numeric|min:0',
            'rows.*.berat_nut_pecah' => 'nullable|numeric|min:0',
        ], [
            'rows.required' => 'Data sampel belum tersedia.',
            'rows.*.kode.required' => 'Kode tidak boleh kosong.',
            'rows.*.kode.in' => 'Kode Ripple Mill tidak valid.',
            'rows.*.operator.in' => 'Operator tidak sesuai dengan daftar office.',
            'rows.*.sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'rows.*.sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
            'rounded_time.date_format' => 'Format jam pengambilan harus HH:MM.',
            'rows.*.berat_sampel.gt' => 'Berat Sample harus lebih dari 0.',
        ]);

        $isKegiatanDispek = (bool) $request->boolean('kegiatan_dispek');
        if ($isKegiatanDispek && empty($validated['rounded_time'])) {
            throw ValidationException::withMessages([
                'rounded_time' => 'Jam pengambilan wajib diisi jika kegiatan dispatch dicentang.',
            ]);
        }

        $roundedTime = $this->resolveKernelSampleTimestamp(
            $row['tanggal_sampel'] ?? $validated['tanggal_sampel'] ?? null,
            $row['rounded_time'] ?? $validated['rounded_time'] ?? null,
            $isKegiatanDispek || $userOffice === 'YBS'
        );
        $requiredFields = ['berat_sampel', 'berat_nut_utuh', 'berat_nut_pecah'];
        $rowsToSave = [];

        foreach (($validated['rows'] ?? []) as $row) {
            $hasInput = false;
            foreach ($requiredFields as $field) {
                if ($this->hasAnyValue($row[$field] ?? null)) {
                    $hasInput = true;
                    break;
                }
            }

            if ($hasInput) {
                $rowsToSave[] = $row;
            }
        }

        if (empty($rowsToSave)) {
            throw ValidationException::withMessages([
                'rows' => 'Silakan isi minimal satu form Ripple Mill.',
            ]);
        }

        $limitMap = $this->getRippleMillLimitMap($userOffice);

        return DB::transaction(function () use ($request, $userOffice, $roundedTime, $rowsToSave, $limitMap, $requiredFields, $isKegiatanDispek, $sampleBoyRequired, $sampleBoyOptions) {
            $rowErrors = [];
            $savedRows = [];

            foreach ($rowsToSave as $row) {
                $kode = (string) ($row['kode'] ?? '');

                if ($sampleBoyRequired) {
                    $sampleBoy = trim((string) ($row['sampel_boy'] ?? ''));

                    if ($sampleBoy === '') {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy wajib dipilih dari daftar Office YBS.';
                        continue;
                    }

                    if (!in_array($sampleBoy, $sampleBoyOptions, true)) {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy tidak sesuai daftar Office YBS.';
                        continue;
                    }
                }

                foreach ($requiredFields as $field) {
                    if (!$this->hasAnyValue($row[$field] ?? null)) {
                        $rowErrors['rows.' . $kode . '.' . $field] = "Field {$field} wajib diisi untuk kode {$kode}.";
                    }
                }

                $requiredErrorCount = collect($requiredFields)
                    ->filter(fn($field) => array_key_exists('rows.' . $kode . '.' . $field, $rowErrors))
                    ->count();
                if ($requiredErrorCount > 0) {
                    continue;
                }

                $isPengulangan = (bool) ($row['pengulangan'] ?? false);

                try {
                    if (!$isKegiatanDispek) {
                        $this->validatePengulanganWindow(
                            KernelRippleMill::class,
                            $kode,
                            $userOffice,
                            $isPengulangan,
                            'Ripple Mill',
                            $roundedTime
                        );

                        $this->validateMachineWindowForKernelInput(
                            $kode,
                            $userOffice,
                            $roundedTime,
                            'Ripple Mill'
                        );
                    }
                } catch (ValidationException $e) {
                    $firstMessage = collect($e->errors())->flatten()->first();
                    $rowErrors['rows.' . $kode . '.kode'] = $firstMessage ?: "Validasi untuk kode {$kode} gagal.";
                    continue;
                }

                $beratSampel = (float) $row['berat_sampel'];
                $beratNutUtuh = (float) $row['berat_nut_utuh'];
                $beratNutPecah = (float) $row['berat_nut_pecah'];

                $sampleNutUtuh = round(($beratNutUtuh / $beratSampel) * 100, 6);
                $sampleNutPecah = round(($beratNutPecah / $beratSampel) * 100, 6);
                $efficiency = round(100 - $sampleNutPecah - $sampleNutUtuh, 6);

                // if ($efficiency > 101) {
                //     $rowErrors['rows.' . $kode . '.kode'] = "Total kernel losses untuk kode {$kode} melebihi batas wajar (101%).";
                //     continue;
                // }

                $rippleMillPayload = [
                    'user_id' => Auth::id(),
                    'office' => $userOffice,
                    'rounded_time' => $roundedTime,
                    'kegiatan_dispek' => $isKegiatanDispek,
                    'kode' => $kode,
                    'jenis' => $row['jenis'] ?? null,
                    'operator' => $row['operator'] ?? null,
                    'sampel_boy' => $row['sampel_boy'] ?? Auth::user()->name,
                    'pengulangan' => $isPengulangan,
                    'remarks' => $row['remarks'] ?? null,
                    'berat_sampel' => $beratSampel,
                    'berat_nut_utuh' => $beratNutUtuh,
                    'berat_nut_pecah' => $beratNutPecah,
                    'sample_nut_utuh' => $sampleNutUtuh,
                    'sample_nut_pecah' => $sampleNutPecah,
                    'efficiency' => $efficiency,
                    'limit_operator' => data_get($limitMap, $kode . '.operator', 'gt'),
                    'limit_value' => data_get($limitMap, $kode . '.value'),
                ];
                $kernelRippleMill = KernelRippleMill::create(
                    $this->prepareCreatePayload($rippleMillPayload, KernelRippleMill::class, $isPengulangan)
                );

                $savedRows[] = $kernelRippleMill;

                $this->logCreate(
                    $kernelRippleMill,
                    "Input data Ripple Mill untuk kode {$kernelRippleMill->kode}",
                    ['module' => 'ripple_mill', 'office' => $userOffice]
                );
            }

            if (!empty($rowErrors)) {
                throw ValidationException::withMessages($rowErrors);
            }

            if (empty($savedRows)) {
                throw ValidationException::withMessages([
                    'rows' => 'Tidak ada data Ripple Mill yang berhasil disimpan.',
                ]);
            }

            $message = count($savedRows) === 1
                ? '1 data Ripple Mill berhasil disimpan.'
                : count($savedRows) . ' data Ripple Mill berhasil disimpan.';

            $proofData = $this->buildRippleMillProofData($savedRows[0], $message);
            $proofData['entries'] = $this->buildKernelProofEntries($savedRows);
            $proofData['entries_metrics'] = $this->buildRippleMillEntriesMetrics($savedRows);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'proof' => $proofData]);
            }

            return redirect()->route('kernel.ripple-mill.index')
                ->with('success', $message)
                ->with('success_proof', $proofData);
        });
    }

    public function rippleMillEdit(KernelRippleMill $kernelRippleMill)
    {
        $this->ensureCanEditKernelLosses();

        $kodeOptions = $this->getRippleMillKodeOptions($kernelRippleMill->office);
        $jenisOptions = KernelRecord::getJenisOptions();
        $operatorOptions = $this->getOperatorOptionsByOffice($kernelRippleMill->office, 'ripple_mill');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($kernelRippleMill->office);

        return view('kernel.ripple-mill.edit', compact('kernelRippleMill', 'kodeOptions', 'jenisOptions', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function rippleMillUpdate(Request $request, KernelRippleMill $kernelRippleMill)
    {
        $this->ensureCanEditKernelLosses();

        $oldAttributes = $kernelRippleMill->getOriginal();

        $validated = $request->validate([
            'kode' => ['required', 'string', Rule::in(array_keys($this->getRippleMillKodeOptions($kernelRippleMill->office)))],
            'jenis' => 'required|string',
            'operator' => $this->getOperatorValidationRules($kernelRippleMill->office, true, 'ripple_mill'),
            'sampel_boy' => $this->getSampleBoyValidationRules($kernelRippleMill->office),
            'berat_sampel' => 'required|numeric|gt:0',
            'berat_nut_utuh' => 'required|numeric|min:0',
            'berat_nut_pecah' => 'required|numeric|min:0',
        ], [
            'sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
        ]);

        $beratSampel = (float) $validated['berat_sampel'];
        $sampleNutUtuh = round(((float) $validated['berat_nut_utuh'] / $beratSampel) * 100, 6);
        $sampleNutPecah = round(((float) $validated['berat_nut_pecah'] / $beratSampel) * 100, 6);
        $efficiency = round(100 - $sampleNutPecah - $sampleNutUtuh, 6);

        $limitMap = $this->getRippleMillLimitMap($kernelRippleMill->office);
        $kode = $validated['kode'];

        $kernelRippleMill->update([
            'kode' => $kode,
            'jenis' => $validated['jenis'],
            'operator' => $validated['operator'],
            'sampel_boy' => $validated['sampel_boy'],
            'berat_sampel' => $beratSampel,
            'berat_nut_utuh' => $validated['berat_nut_utuh'],
            'berat_nut_pecah' => $validated['berat_nut_pecah'],
            'sample_nut_utuh' => $sampleNutUtuh,
            'sample_nut_pecah' => $sampleNutPecah,
            'efficiency' => $efficiency,
            'limit_operator' => data_get($limitMap, $kode . '.operator', 'gt'),
            'limit_value' => data_get($limitMap, $kode . '.value'),
        ]);

        $this->logUpdate(
            $kernelRippleMill,
            $oldAttributes,
            "Update data Ripple Mill kode {$kernelRippleMill->kode}",
            ['module' => 'ripple_mill', 'office' => $kernelRippleMill->office]
        );

        return redirect()->route('kernel.ripple-mill.index')->with('success', 'Data Ripple Mill berhasil diperbarui.');
    }

    public function rippleMillDestroy(KernelRippleMill $kernelRippleMill)
    {
        $this->ensureCanDeleteKernelLosses();

        $this->logDelete(
            $kernelRippleMill,
            "Hapus data Ripple Mill kode {$kernelRippleMill->kode}",
            ['module' => 'ripple_mill', 'office' => $kernelRippleMill->office]
        );

        $kernelRippleMill->delete();

        return back()->with('success', 'Data Ripple Mill berhasil dihapus.');
    }

    public function destonerIndex(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelDestoner::with('user')
            ->whereBetween('created_at', $range)
            ->orderBy('created_at', 'desc');

        $this->applyOfficeFilter($query, $officeFilter);

        if ($request->filled('kode')) {
            $query->where('kode', $request->kode);
        }

        if (!Auth::user()->can('view kernel losses')) {
            $query->where('user_id', Auth::id());
        }

        $totalRows = (clone $query)->count();
        $todayStart = Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $todayEnd = Carbon::today()->endOfDay()->format('Y-m-d H:i:s');

        $statistics = [
            'total_records' => $totalRows,
            'records_today' => KernelDestoner::whereBetween('created_at', [$todayStart, $todayEnd])
                ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
                ->count(),
            'calculations_count' => $totalRows,
        ];

        $destonerRows = $query->paginate(15, ['*'], 'calculations');
        $destonerRows->appends($request->only(['start_date', 'end_date', 'kode', 'office']));

        $kodeOptions = $this->getDestonerKodeOptions($officeFilter);
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');

        return view('kernel.destoner.index', compact(
            'destonerRows',
            'statistics',
            'kodeOptions',
            'masterData',
            'startDate',
            'endDate',
            'officeFilter'
        ));
    }

    public function destonerCreate()
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data Destoner.');
        }

        $userOffice = $this->getUserOffice();
        $kodeOptions = $this->getDestonerKodeOptions($userOffice);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);
        $sampleBoyRequired = $userOffice === 'YBS' && !empty($sampleBoyOptions);
        $kodeFormGroups = $this->getDestonerFormGroups($kodeOptions, $userOffice);
        $jenisOptions = KernelRecord::getJenisOptions();
        $kernelLimitMap = $this->getDestonerLimitMap($userOffice);
        $operatorOptions = $this->getOperatorOptionsByOffice($userOffice, 'destoner');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($userOffice);

        return view('kernel.destoner.create', compact('kodeOptions', 'kodeFormGroups', 'jenisOptions', 'kernelLimitMap', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function destonerStore(Request $request)
    {
        if (!Auth::user()->can('create kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data Destoner.');
        }

        $userOffice = $this->getUserOffice();
        if (!$userOffice) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda harus memiliki Office yang ditentukan untuk dapat input data.'], 422);
            }
            return back()
                ->withInput()
                ->with('error', 'Anda harus memiliki Office yang ditentukan untuk dapat input data. Silakan hubungi Administrator.');
        }

        $kodeOptions = $this->getDestonerKodeOptions($userOffice);

        $validated = $request->validate([
            'kegiatan_dispek' => 'nullable|boolean',
            'tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rounded_time' => 'nullable|date_format:H:i',
            'rows' => 'required|array',
            'rows.*.kode' => ['required', 'string', Rule::in(array_keys($kodeOptions))],
            'rows.*.tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'rows.*.rounded_time' => 'nullable|date_format:H:i',
            'rows.*.jenis' => 'nullable|string',
            'rows.*.operator' => $this->getOperatorValidationRules($userOffice, false, 'destoner'),
            'rows.*.sampel_boy' => $sampleBoyRequired
                ? $this->getSampleBoyValidationRules($userOffice, false)
                : $this->getSampleBoyValidationRules($userOffice),
            'rows.*.pengulangan' => 'nullable|boolean',
            'rows.*.remarks' => 'nullable|string|max:500',
            'rows.*.berat_sampel' => 'nullable|numeric|gt:0',
            'rows.*.time' => 'nullable|numeric|gt:0',
            'rows.*.berat_nut' => 'nullable|numeric|min:0',
            'rows.*.berat_kernel' => 'nullable|numeric|min:0',
        ], [
            'rows.required' => 'Data sampel belum tersedia.',
            'rows.*.kode.required' => 'Kode tidak boleh kosong.',
            'rows.*.kode.in' => 'Kode Destoner tidak valid.',
            'rows.*.operator.in' => 'Operator tidak sesuai dengan daftar office.',
            'rows.*.sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'rows.*.sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
            'rounded_time.date_format' => 'Format jam pengambilan harus HH:MM.',
            'rows.*.berat_sampel.gt' => 'Berat Sampel harus lebih dari 0.',
            'rows.*.time.gt' => 'Time harus lebih dari 0.',
        ]);

        $isKegiatanDispek = (bool) $request->boolean('kegiatan_dispek');
        if ($isKegiatanDispek && empty($validated['rounded_time'])) {
            throw ValidationException::withMessages([
                'rounded_time' => 'Jam pengambilan wajib diisi jika kegiatan dispatch dicentang.',
            ]);
        }

        $roundedTime = $this->resolveKernelSampleTimestamp(
            $row['tanggal_sampel'] ?? $validated['tanggal_sampel'] ?? null,
            $row['rounded_time'] ?? $validated['rounded_time'] ?? null,
            $isKegiatanDispek || $userOffice === 'YBS'
        );
        $requiredFields = ['operator', 'berat_sampel', 'time', 'berat_nut', 'berat_kernel'];
        $rowsToSave = [];

        foreach (($validated['rows'] ?? []) as $row) {
            $hasInput = false;
            foreach (['berat_sampel', 'time', 'berat_nut', 'berat_kernel'] as $field) {
                if ($this->hasAnyValue($row[$field] ?? null)) {
                    $hasInput = true;
                    break;
                }
            }

            if ($hasInput) {
                $rowsToSave[] = $row;
            }
        }

        if (empty($rowsToSave)) {
            throw ValidationException::withMessages([
                'rows' => 'Silakan isi minimal satu form Destoner.',
            ]);
        }

        $limitMap = $this->getDestonerLimitMap($userOffice);

        return DB::transaction(function () use ($request, $userOffice, $roundedTime, $rowsToSave, $limitMap, $requiredFields, $isKegiatanDispek, $sampleBoyRequired, $sampleBoyOptions) {
            $rowErrors = [];
            $savedRows = [];

            foreach ($rowsToSave as $row) {
                $kode = (string) ($row['kode'] ?? '');

                if ($sampleBoyRequired) {
                    $sampleBoy = trim((string) ($row['sampel_boy'] ?? ''));

                    if ($sampleBoy === '') {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy wajib dipilih dari daftar Office YBS.';
                        continue;
                    }

                    if (!in_array($sampleBoy, $sampleBoyOptions, true)) {
                        $rowErrors['rows.' . $kode . '.sampel_boy'] = 'Sampel Boy tidak sesuai daftar Office YBS.';
                        continue;
                    }
                }

                foreach ($requiredFields as $field) {
                    if (!$this->hasAnyValue($row[$field] ?? null)) {
                        $rowErrors['rows.' . $kode . '.' . $field] = "Field {$field} wajib diisi untuk kode {$kode}.";
                    }
                }

                $requiredErrorCount = collect($requiredFields)
                    ->filter(fn($field) => array_key_exists('rows.' . $kode . '.' . $field, $rowErrors))
                    ->count();
                if ($requiredErrorCount > 0) {
                    continue;
                }

                $isPengulangan = (bool) ($row['pengulangan'] ?? false);

                try {
                    if (!$isKegiatanDispek) {
                        $this->validatePengulanganWindow(
                            KernelDestoner::class,
                            $kode,
                            $userOffice,
                            $isPengulangan,
                            'Destoner',
                            $roundedTime
                        );

                        $this->validateMachineWindowForKernelInput(
                            $kode,
                            $userOffice,
                            $roundedTime,
                            'Destoner'
                        );
                    }
                } catch (ValidationException $e) {
                    $firstMessage = collect($e->errors())->flatten()->first();
                    $rowErrors['rows.' . $kode . '.kode'] = $firstMessage ?: "Validasi untuk kode {$kode} gagal.";
                    continue;
                }

                $beratSampel = (float) $row['berat_sampel'];
                $time = (float) $row['time'];
                $beratNut = (float) $row['berat_nut'];
                $beratKernel = (float) $row['berat_kernel'];

                $konversiKg = $beratSampel / 1000;
                $rasioJamKg = $konversiKg * 3600 / $time;
                $persenNut = ($beratNut / $beratSampel) * 50;
                $persenKernel = ($beratKernel / $beratSampel) * 100;
                $totalLossesKernel = $persenKernel + $persenNut;
                $lossKernelJam = ($totalLossesKernel * $rasioJamKg) / 100;
                $lossKernelTbs = $lossKernelJam / 600;

                $destonerPayload = [
                    'user_id' => Auth::id(),
                    'office' => $userOffice,
                    'rounded_time' => $roundedTime,
                    'kegiatan_dispek' => $isKegiatanDispek,
                    'kode' => $kode,
                    'jenis' => $row['jenis'] ?? null,
                    'operator' => $row['operator'],
                    'sampel_boy' => $row['sampel_boy'] ?? Auth::user()->name,
                    'pengulangan' => $isPengulangan,
                    'remarks' => $row['remarks'] ?? null,
                    'berat_sampel' => $beratSampel,
                    'time' => $time,
                    'berat_nut' => $beratNut,
                    'berat_kernel' => $beratKernel,
                    'konversi_kg' => $konversiKg,
                    'rasio_jam_kg' => $rasioJamKg,
                    'persen_nut' => $persenNut,
                    'persen_kernel' => $persenKernel,
                    'total_losses_kernel' => $totalLossesKernel,
                    'loss_kernel_jam' => $lossKernelJam,
                    'loss_kernel_tbs' => $lossKernelTbs,
                    'limit_operator' => data_get($limitMap, $kode . '.operator', 'gt'),
                    'limit_value' => data_get($limitMap, $kode . '.value'),
                ];
                $kernelDestoner = KernelDestoner::create(
                    $this->prepareCreatePayload($destonerPayload, KernelDestoner::class, $isPengulangan)
                );

                $savedRows[] = $kernelDestoner;

                $this->logCreate(
                    $kernelDestoner,
                    "Input data Destoner untuk kode {$kernelDestoner->kode}",
                    ['module' => 'destoner', 'office' => $userOffice]
                );
            }

            if (!empty($rowErrors)) {
                throw ValidationException::withMessages($rowErrors);
            }

            if (empty($savedRows)) {
                throw ValidationException::withMessages([
                    'rows' => 'Tidak ada data Destoner yang berhasil disimpan.',
                ]);
            }

            $message = count($savedRows) === 1
                ? '1 data Destoner berhasil disimpan.'
                : count($savedRows) . ' data Destoner berhasil disimpan.';

            $proofData = $this->buildDestonerProofData($savedRows[0], $message);
            $proofData['entries'] = $this->buildKernelProofEntries($savedRows);
            $proofData['entries_metrics'] = $this->buildDestonerEntriesMetrics($savedRows);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'proof' => $proofData]);
            }

            return redirect()->route('kernel.destoner.index')
                ->with('success', $message)
                ->with('success_proof', $proofData);
        });
    }

    public function destonerEdit(KernelDestoner $kernelDestoner)
    {
        $this->ensureCanEditKernelLosses();

        $kodeOptions = $this->getDestonerKodeOptions($kernelDestoner->office);
        $jenisOptions = KernelRecord::getJenisOptions();
        $operatorOptions = $this->getOperatorOptionsByOffice($kernelDestoner->office, 'destoner');
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($kernelDestoner->office);

        return view('kernel.destoner.edit', compact('kernelDestoner', 'kodeOptions', 'jenisOptions', 'operatorOptions', 'sampleBoyOptions'));
    }

    public function destonerUpdate(Request $request, KernelDestoner $kernelDestoner)
    {
        $this->ensureCanEditKernelLosses();

        $oldAttributes = $kernelDestoner->getOriginal();

        $validated = $request->validate([
            'kode' => ['required', 'string', Rule::in(array_keys($this->getDestonerKodeOptions($kernelDestoner->office)))],
            'jenis' => 'required|string',
            'operator' => $this->getOperatorValidationRules($kernelDestoner->office, true, 'destoner'),
            'sampel_boy' => $this->getSampleBoyValidationRules($kernelDestoner->office),
            'berat_sampel' => 'required|numeric|gt:0',
            'time' => 'required|numeric|gt:0',
            'berat_nut' => 'required|numeric|min:0',
            'berat_kernel' => 'required|numeric|min:0',
        ], [
            'sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
        ]);

        $beratSampel = (float) $validated['berat_sampel'];
        $time = (float) $validated['time'];
        $beratNut = (float) $validated['berat_nut'];
        $beratKernel = (float) $validated['berat_kernel'];

        $konversiKg = round($beratSampel / 1000, 6);
        $rasioJamKg = round($konversiKg * 3600 / $time, 6);
        $persenNut = round(($beratNut / $beratSampel) * 50, 6);
        $persenKernel = round(($beratKernel / $beratSampel) * 100, 6);
        $totalLossesKernel = round($persenKernel + $persenNut, 6);
        $lossKernelJam = round(($totalLossesKernel * $rasioJamKg) / 100, 6);
        $lossKernelTbs = round($lossKernelJam / 300, 8);

        $limitMap = $this->getDestonerLimitMap($kernelDestoner->office);
        $kode = $validated['kode'];

        $kernelDestoner->update([
            'kode' => $kode,
            'jenis' => $validated['jenis'],
            'operator' => $validated['operator'],
            'sampel_boy' => $validated['sampel_boy'],
            'berat_sampel' => $beratSampel,
            'time' => $time,
            'berat_nut' => $beratNut,
            'berat_kernel' => $beratKernel,
            'konversi_kg' => $konversiKg,
            'rasio_jam_kg' => $rasioJamKg,
            'persen_nut' => $persenNut,
            'persen_kernel' => $persenKernel,
            'total_losses_kernel' => $totalLossesKernel,
            'loss_kernel_jam' => $lossKernelJam,
            'loss_kernel_tbs' => $lossKernelTbs,
            'limit_operator' => data_get($limitMap, $kode . '.operator', 'gt'),
            'limit_value' => data_get($limitMap, $kode . '.value'),
        ]);

        $this->logUpdate(
            $kernelDestoner,
            $oldAttributes,
            "Update data Destoner kode {$kernelDestoner->kode}",
            ['module' => 'destoner', 'office' => $kernelDestoner->office]
        );

        return redirect()->route('kernel.destoner.index')->with('success', 'Data Destoner berhasil diperbarui.');
    }

    public function destonerDestroy(KernelDestoner $kernelDestoner)
    {
        $this->ensureCanDeleteKernelLosses();

        $this->logDelete(
            $kernelDestoner,
            "Hapus data Destoner kode {$kernelDestoner->kode}",
            ['module' => 'destoner', 'office' => $kernelDestoner->office]
        );

        $kernelDestoner->delete();

        return back()->with('success', 'Data Destoner berhasil dihapus.');
    }

    public function destroyRecord(KernelRecord $kernelRecord)
    {
        $this->ensureCanDeleteKernelLosses();

        $this->logDelete(
            $kernelRecord,
            "Hapus data jenis & sampel kernel kode {$kernelRecord->kode}",
            ['module' => 'kernel_record', 'office' => $kernelRecord->office]
        );

        $kernelRecord->delete();
        return back()->with('success', 'Data jenis & sampel berhasil dihapus.');
    }

    public function destroyCalculation(KernelCalculation $kernelCalculation)
    {
        $this->ensureCanDeleteKernelLosses();

        $this->logDelete(
            $kernelCalculation,
            "Hapus data perhitungan kernel losses kode {$kernelCalculation->kode}",
            ['module' => 'kernel_losses', 'office' => $kernelCalculation->office]
        );

        $kernelCalculation->delete();
        return back()->with('success', 'Data perhitungan lab berhasil dihapus.');
    }

    public function rekap(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $officeFilter = $this->resolveOfficeFilter($request);

        [$allKodesData, $dataByDate] = $this->buildRekapData($startDate, $endDate, $officeFilter);
        $columnGroups = $this->getKernelColumnGroups($officeFilter);
        $masterMap = collect($allKodesData)->keyBy('kode');

        return view('kernel.rekap', compact('allKodesData', 'dataByDate', 'startDate', 'endDate', 'columnGroups', 'masterMap', 'officeFilter'));
    }

    public function exportRekap(Request $request)
    {
        if (!Auth::user()->can('view rekap kernel losses')) {
            abort(403);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $officeFilter = $this->resolveOfficeFilter($request);

        [$allKodesData, $dataByDate] = $this->buildRekapData($startDate, $endDate, $officeFilter);
        $columnGroups = $this->getKernelColumnGroups($officeFilter);

        $this->logExport('Rekap Kernel Losses', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'office' => $officeFilter,
        ]);

        $filename = 'Rekap_Kernel_Losses_'
            . \Carbon\Carbon::parse($startDate)->format('Ymd') . '_'
            . \Carbon\Carbon::parse($endDate)->format('Ymd') . '.xlsx';

        return Excel::download(
            new KernelRekapExport($dataByDate, $allKodesData, $startDate, $endDate, $columnGroups),
            $filename
        );
    }

    private function buildRekapData(string $startDate, string $endDate, string $officeFilter): array
    {
        $allowedKodes = $this->getKernelOfficeAllowedCodes($officeFilter);

        $allKodesData = $this->activeKernelMasterDataQuery($officeFilter)
            ->whereIn('kode', $allowedKodes)
            ->orderBy('kode')
            ->get()
            ->map(function ($m) {
                return [
                    'kode' => $m->kode,
                    'nama_sample' => $m->nama_sample,
                    'limit_operator' => $m->limit_operator,
                    'limit_value' => $m->limit_value,
                ];
            });

        $masterMap = $this->activeKernelMasterDataQuery($officeFilter)
            ->get()
            ->keyBy('kode');

        $calculations = $this->getUnifiedKernelReportRecords($startDate, $endDate, $officeFilter)
            ->sortBy(fn($calc) => $this->resolveDisplayTimestamp($calc)->getTimestamp())
            ->values();

        // Accumulate sum + count per date + kode
        // For %Moist, append _MOIST suffix to distinguish from %Dirty
        $grouped = [];
        foreach ($calculations as $calc) {
            $displayAt = $this->resolveDisplayTimestamp($calc);
            $date = $this->resolveProductionDateKeyByCutoff($displayAt);
            $kode = ($calc->source_module === 'Dirt & Moist (%Moist)')
                ? ($calc->kode . '_MOIST')
                : $calc->kode;
            if (!isset($grouped[$date][$kode])) {
                $grouped[$date][$kode] = ['sum' => 0.0, 'count' => 0];
            }
            $grouped[$date][$kode]['sum'] += (float) ($calc->kernel_losses ?? 0);
            $grouped[$date][$kode]['count'] += 1;
        }

        // Build final dataByDate with averages
        $dataByDate = [];
        foreach ($grouped as $date => $kodes) {
            foreach ($kodes as $kode => $stats) {
                $avg = $stats['count'] > 0 ? $stats['sum'] / $stats['count'] : 0.0;
                // Strip _MOIST suffix for master data lookup
                $masterKode = str_replace('_MOIST', '', $kode);
                $master = $masterMap->get($masterKode);
                $dataByDate[$date][$kode] = [
                    'avg_losses' => $avg,
                    'count' => $stats['count'],
                    'limit_operator' => $master ? $master->limit_operator : null,
                    'limit_value' => $master ? (float) $master->limit_value : null,
                ];
            }
        }
        ksort($dataByDate);

        return [$allKodesData, $dataByDate];
    }

    private function getKernelColumnGroups(?string $office = null): array
    {
        $allowedKodes = array_flip($this->getKernelOfficeAllowedCodes($office));

        $groups = [
            [
                'name' => 'KERNEL LOSSES',
                'color' => 'bg-amber-100 text-amber-800',
                'columns' => [
                    ['kode' => 'FC1', 'label' => 'FIBRE CYCLONE 1'],
                    ['kode' => 'FC2', 'label' => 'FIBRE CYCLONE 2'],
                    ['kode' => 'L1', 'label' => 'LTDS 1'],
                    ['kode' => 'L2', 'label' => 'LTDS 2'],
                    ['kode' => 'L3', 'label' => 'LTDS 3'],
                    ['kode' => 'L4', 'label' => 'LTDS 4'],
                    ['kode' => 'CWS', 'label' => 'CLAYBATH WET SHELL 1'],
                    ['kode' => 'CWS2', 'label' => 'CLAYBATH WET SHELL 2'],
                    ['kode' => 'CWS3', 'label' => 'CLAYBATH WET SHELL 3'],
                ],
            ],
            [
                'name' => '%Dirty',
                'color' => 'bg-orange-100 text-orange-800',
                'columns' => [
                    ['kode' => 'IN1', 'label' => 'INLET KERNEL SILO 1'],
                    ['kode' => 'IN2', 'label' => 'INLET KERNEL SILO 2'],
                    ['kode' => 'IN3', 'label' => 'INLET KERNEL SILO 3'],
                    ['kode' => 'IN4', 'label' => 'INLET KERNEL SILO 4'],
                    ['kode' => 'IN5', 'label' => 'INLET KERNEL SILO 5'],
                    ['kode' => 'OUT1', 'label' => 'OUTLET KERNEL SILO 1 TO BUNKER'],
                    ['kode' => 'OUT2', 'label' => 'OUTLET KERNEL SILO 2 TO BUNKER'],
                    ['kode' => 'OUT3', 'label' => 'OUTLET KERNEL SILO 3 TO BUNKER'],
                    ['kode' => 'OUT4', 'label' => 'OUTLET KERNEL SILO 4 TO BUNKER'],
                    ['kode' => 'OUT5', 'label' => 'OUTLET KERNEL SILO 5 TO BUNKER'],
                ],
            ],
            [
                'name' => '%Moist',
                'color' => 'bg-blue-100 text-blue-800',
                'columns' => [
                    ['kode' => 'OUT1_MOIST', 'label' => 'OUTLET KERNEL SILO 1 TO BUNKER'],
                    ['kode' => 'OUT2_MOIST', 'label' => 'OUTLET KERNEL SILO 2 TO BUNKER'],
                    ['kode' => 'OUT3_MOIST', 'label' => 'OUTLET KERNEL SILO 3 TO BUNKER'],
                    ['kode' => 'OUT4_MOIST', 'label' => 'OUTLET KERNEL SILO 4 TO BUNKER'],
                    ['kode' => 'OUT5_MOIST', 'label' => 'OUTLET KERNEL SILO 5 TO BUNKER'],
                ],
            ],
            [
                'name' => 'BN / TN',
                'color' => 'bg-green-100 text-green-800',
                'columns' => [
                    ['kode' => 'P1', 'label' => 'PRESS 1'],
                    ['kode' => 'P2', 'label' => 'PRESS 2'],
                    ['kode' => 'P3', 'label' => 'PRESS 3'],
                    ['kode' => 'P4', 'label' => 'PRESS 4'],
                    ['kode' => 'P5', 'label' => 'PRESS 5'],
                    ['kode' => 'P6', 'label' => 'PRESS 6'],
                    ['kode' => 'P7', 'label' => 'PRESS 7'],
                    ['kode' => 'P8', 'label' => 'PRESS 8'],
                    ['kode' => 'P9', 'label' => 'PRESS 9'],
                ],
            ],
            [
                'name' => 'Efficiency',
                'color' => 'bg-purple-100 text-purple-800',
                'columns' => [
                    ['kode' => 'R1', 'label' => 'Ripple Mill No. 1'],
                    ['kode' => 'R2', 'label' => 'Ripple Mill No. 2'],
                    ['kode' => 'R3', 'label' => 'Ripple Mill No. 3'],
                    ['kode' => 'R4', 'label' => 'Ripple Mill No. 4'],
                    ['kode' => 'R5', 'label' => 'Ripple Mill No. 5'],
                    ['kode' => 'R6', 'label' => 'Ripple Mill No. 6'],
                    ['kode' => 'R7', 'label' => 'Ripple Mill No. 7'],
                ],
            ],
            [
                'name' => 'DESTONER 1',
                'color' => 'bg-pink-100 text-pink-800',
                'columns' => [
                    ['kode' => 'D1', 'label' => 'LOSS KERNEL/JAM'],
                    ['kode' => 'D1', 'label' => 'LOSS KERNEL/TBS'],
                ],
            ],
            [
                'name' => 'DESTONER 2',
                'color' => 'bg-rose-100 text-rose-800',
                'columns' => [
                    ['kode' => 'D2', 'label' => 'LOSS KERNEL/JAM'],
                    ['kode' => 'D2', 'label' => 'LOSS KERNEL/TBS'],
                ],
            ],
        ];

        return collect($groups)
            ->map(function (array $group) use ($allowedKodes) {
                $columns = array_values(array_filter($group['columns'], function (array $column) use ($allowedKodes) {
                    return isset($allowedKodes[$column['kode']]);
                }));

                $group['columns'] = $columns;

                return $group;
            })
            ->filter(fn(array $group) => !empty($group['columns']))
            ->values()
            ->toArray();
    }

    public function performance(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $officeFilter = $this->resolveOfficeFilter($request);

        [$reportData, $allKodesData, $operators, $operatorActivities, $reportDates, $dailyPerformance, $allIndividualRecords, $operatorDailyPerformance] = $this->buildPerformanceData($startDate, $endDate, $officeFilter);

        // Paginate individual records (25 per page)
        $perPage = 25;
        $page = $request->get('page', 1);
        $sliced = array_slice($allIndividualRecords, ($page - 1) * $perPage, $perPage);
        $individualRecords = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            count($allIndividualRecords),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('kernel.performance', compact(
            'reportData',
            'allKodesData',
            'startDate',
            'endDate',
            'officeFilter',
            'operators',
            'operatorActivities',
            'reportDates',
            'dailyPerformance',
            'individualRecords',
            'operatorDailyPerformance'
        ));
    }

    public function exportPerformance(Request $request)
    {
        if (!Auth::user()->can('view performance kernel losses')) {
            abort(403);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $officeFilter = $this->resolveOfficeFilter($request);

        [$reportData, $allKodesData, $operators, $operatorActivities, $reportDates, $dailyPerformance, $allIndividualRecords, $operatorDailyPerformance] = $this->buildPerformanceData($startDate, $endDate, $officeFilter);

        $this->logExport('Performance Kernel Losses', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'office' => $officeFilter,
        ]);

        $filename = 'Performance_Kernel_'
            . Carbon::parse($startDate)->format('Ymd') . '_'
            . Carbon::parse($endDate)->format('Ymd') . '.xlsx';

        return Excel::download(
            new KernelPerformanceExport(
                $allIndividualRecords,
                $operators,
                $reportDates,
                $operatorDailyPerformance,
                $startDate,
                $endDate,
                $officeFilter
            ),
            $filename
        );
    }

    private function buildPerformanceData(string $startDate, string $endDate, string $officeFilter): array
    {
        $bobotConfigs = KernelBobotConfig::all()->keyBy('jenis');
        $allowedKodes = $this->getKernelOfficeAllowedCodes($officeFilter);

        // Get all active kodes that have a matching bobot config
        $allKodesData = $this->activeKernelMasterDataQuery($officeFilter)
            ->whereIn('kode', $allowedKodes)
            ->get()
            ->reject(function ($m) {
                return str_starts_with(strtoupper((string) $m->kode), 'D');
            })
            ->filter(function ($m) use ($bobotConfigs) {
                return $this->mapKodeToKernelConfig($m->kode, $bobotConfigs) !== null;
            })
            ->sortBy('kode')
            ->map(fn($m) => [
                'kode' => $m->kode,
                'nama_sample' => $m->nama_sample,
            ])
            ->values();

        $masterDataMap = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');

        // Get unified records from all kernel modules grouped by date+kode
        $calculations = $this->getUnifiedKernelReportRecords($startDate, $endDate, $officeFilter)
            ->reject(function ($row) {
                return str_starts_with(strtoupper((string) ($row->kode ?? '')), 'D');
            })
            ->sortBy(fn($calc) => $this->resolveDisplayTimestamp($calc)->getTimestamp())
            ->values();

        // Build individual records (flat, one row per calculation)
        $individualRecords = [];
        foreach ($calculations as $calc) {
            /** @var object $calc */
            $displayAt = $this->resolveDisplayTimestamp($calc);
            $productionDate = $this->resolveProductionDateKeyByCutoff($displayAt);
            $valuePercent = (float) ($calc->kernel_losses ?? 0) * 100;
            $config = $this->mapKodeToKernelConfig($calc->kode, $bobotConfigs);
            $bobot = $config ? $this->calculateKernelBobot($valuePercent, $config, $calc->kode) : null;
            $master = $masterDataMap->get($calc->kode);
            $individualRecords[] = [
                'date' => $productionDate,
                'time' => $displayAt->format('H:i'),
                'operator' => $calc->operator ?? '-',
                'sampel_boy' => $calc->sampel_boy ?? '-',
                'nama_sample' => $master ? $master->nama_sample : $calc->kode,
                'kode' => $calc->kode,
                'nilai_parameter' => $valuePercent,
                'bobot' => $bobot,
            ];
        }

        $grouped = [];
        foreach ($calculations as $calc) {
            /** @var object $calc */
            $displayAt = $this->resolveDisplayTimestamp($calc);
            $date = $this->resolveProductionDateKeyByCutoff($displayAt);
            $kode = $calc->kode;
            if (!isset($grouped[$date][$kode])) {
                $grouped[$date][$kode] = ['sum' => 0.0, 'count' => 0];
            }
            $grouped[$date][$kode]['sum'] += (float) ($calc->kernel_losses ?? 0);
            $grouped[$date][$kode]['count'] += 1;
        }

        ksort($grouped);

        $reportData = [];
        foreach ($grouped as $date => $kodes) {
            $dateData = ['date' => $date, 'kodes' => []];
            $bobotScores = [];

            foreach ($allKodesData as $kodeInfo) {
                $kode = $kodeInfo['kode'];

                if (isset($kodes[$kode]) && $kodes[$kode]['count'] > 0) {
                    $avgFraction = $kodes[$kode]['sum'] / $kodes[$kode]['count'];
                    $valuePercent = $avgFraction * 100;

                    $config = $this->mapKodeToKernelConfig($kode, $bobotConfigs);
                    $bobot = $config ? $this->calculateKernelBobot($valuePercent, $config, $kode) : null;

                    $dateData['kodes'][$kode] = [
                        'kernel_losses' => $valuePercent,
                        'bobot' => $bobot,
                    ];

                    if ($bobot !== null) {
                        $bobotScores[] = $bobot;
                    }
                } else {
                    $dateData['kodes'][$kode] = [
                        'kernel_losses' => null,
                        'bobot' => null,
                    ];
                }
            }

            $dateData['average_bobot'] = !empty($bobotScores)
                ? round(array_sum($bobotScores) / count($bobotScores), 2)
                : null;

            $reportData[] = $dateData;
        }

        // Get operator data for the same date range
        $operatorRecords = $calculations
            ->filter(function ($row) {
                return !empty($row->operator);
            })
            ->sortByDesc(fn($calc) => $this->resolveDisplayTimestamp($calc)->getTimestamp())
            ->values();

        $operators = $operatorRecords->pluck('operator')->unique()->sort()->values();

        $operatorActivities = $operatorRecords->groupBy('operator')->map(function ($records) {
            $group = collect($records);

            return [
                'total_input' => $group->count(),
                'last_input' => $this->resolveDisplayTimestamp($group->first()),
            ];
        });

        $reportDates = collect($reportData)->pluck('date');
        $dailyPerformance = collect($reportData)->keyBy('date')->map(function ($item) {
            $row = (array) $item;

            return ['average_bobot' => $row['average_bobot'] ?? null];
        });

        // Build per-operator daily performance from individual records
        $operatorDailyPerformance = [];
        foreach ($individualRecords as $rec) {
            $op = $rec['operator'];
            $date = $rec['date'];
            if ($op === '-' || $op === '' || $rec['bobot'] === null)
                continue;
            if (!isset($operatorDailyPerformance[$op][$date])) {
                $operatorDailyPerformance[$op][$date] = ['sum' => 0, 'count' => 0];
            }
            $operatorDailyPerformance[$op][$date]['sum'] += $rec['bobot'];
            $operatorDailyPerformance[$op][$date]['count'] += 1;
        }

        return [$reportData, $allKodesData, $operators, $operatorActivities, $reportDates, $dailyPerformance, $individualRecords, $operatorDailyPerformance];
    }

    private function getKernelOfficeAllowedCodes(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        $codeSets = match ($officeCode) {
            'SUN' => [
                self::KERNEL_LOSSES_KODES_SUN,
                self::DIRT_MOIST_KODES_SUN,
                self::QWT_KODES_SUN,
                self::RIPPLE_MILL_KODES_SUN,
                self::DESTONER_KODES_SUN,
            ],
            'SJN' => [
                self::KERNEL_LOSSES_KODES_SJN,
                self::DIRT_MOIST_KODES_SJN,
                self::QWT_KODES_SJN,
                self::RIPPLE_MILL_KODES_SJN,
                self::DESTONER_KODES_SJN,
            ],
            'ALL' => [
                self::KERNEL_LOSSES_KODES_YBS,
                self::DIRT_MOIST_KODES_YBS,
                self::QWT_KODES_YBS,
                self::RIPPLE_MILL_KODES_YBS,
                self::DESTONER_KODES_YBS,
            ],
            default => [
                self::KERNEL_LOSSES_KODES_YBS,
                self::DIRT_MOIST_KODES_YBS,
                self::QWT_KODES_YBS,
                self::RIPPLE_MILL_KODES_YBS,
                self::DESTONER_KODES_YBS,
            ],
        };

        $codes = collect($codeSets)
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $moistCodes = collect($codes)
            ->filter(fn($code) => str_starts_with((string) $code, 'OUT'))
            ->map(fn($code) => $code . '_MOIST')
            ->values()
            ->all();

        return collect($codes)
            ->merge($moistCodes)
            ->unique()
            ->values()
            ->all();
    }

    private function mapKodeToKernelConfig(string $kode, $bobotConfigs)
    {
        $prefix = preg_replace('/[0-9]+$/', '', $kode);

        $map = [
            'CWS' => 'Claybath',
            'FC' => 'Fibercyclone',
            'L' => 'LTDS',
            'IN' => 'Inlet Kernel Silo',
            'OUT' => 'Outlet Kernel Silo',
            'R' => 'Ripple Mill',
            'D' => 'CaCo3',
            'P' => 'Press',
        ];

        $jenis = $map[$prefix] ?? null;

        return ($jenis && isset($bobotConfigs[$jenis])) ? $bobotConfigs[$jenis] : null;
    }

    private function ensureCanEditKernelLosses(): void
    {
        if (!Auth::user() || !Auth::user()->can('edit kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk edit data kernel losses.');
        }
    }

    private function ensureCanDeleteKernelLosses(): void
    {
        if (!Auth::user() || !Auth::user()->can('delete kernel losses')) {
            abort(403, 'Anda tidak memiliki akses untuk hapus data kernel losses.');
        }
    }

    private function getKernelLossesKodeOptions(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        $orderedCodes = match ($officeCode) {
            'SUN' => self::KERNEL_LOSSES_KODES_SUN,
            'SJN' => self::KERNEL_LOSSES_KODES_SJN,
            'ALL' => self::KERNEL_LOSSES_KODES_YBS,
            default => self::KERNEL_LOSSES_KODES_YBS,
        };

        $options = $this->activeKernelMasterDataQuery($officeCode)
            ->whereIn('kode', $orderedCodes)
            ->pluck('nama_sample', 'kode')
            ->toArray();

        $orderedOptions = [];
        foreach ($orderedCodes as $kode) {
            if (isset($options[$kode])) {
                $orderedOptions[$kode] = $options[$kode];
            }
        }

        return $orderedOptions;
    }

    private function getKernelLossesFormGroups(array $kodeOptions, ?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        // Determine available codes based on office
        $availableCodes = match ($officeCode) {
            'SUN' => self::KERNEL_LOSSES_KODES_SUN,
            'SJN' => self::KERNEL_LOSSES_KODES_SJN,
            'ALL' => self::KERNEL_LOSSES_KODES_YBS,
            default => self::KERNEL_LOSSES_KODES_YBS,
        };

        $groups = [
            ['title' => 'Fibre Cyclone', 'codes' => ['FC1', 'FC2']],
            ['title' => 'LTDS', 'codes' => ['L1', 'L2', 'L3', 'L4']],
            ['title' => 'Claybath Wet Shell', 'codes' => ['CWS', 'CWS1', 'CWS2', 'CWS3']],
        ];

        $result = [];

        foreach ($groups as $group) {
            $items = [];
            foreach ($group['codes'] as $code) {
                // Only include codes that are available for this office
                if (isset($kodeOptions[$code]) && in_array($code, $availableCodes)) {
                    $items[] = [
                        'kode' => $code,
                        'label' => $kodeOptions[$code],
                    ];
                }
            }

            if (!empty($items)) {
                $result[] = [
                    'title' => $group['title'],
                    'items' => $items,
                ];
            }
        }

        return $result;
    }

    private function getQwtKodeOptions(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        $orderedCodes = match ($officeCode) {
            'SUN' => self::QWT_KODES_SUN,
            'SJN' => self::QWT_KODES_SJN,
            'ALL' => self::QWT_KODES_YBS,
            default => self::QWT_KODES_YBS,
        };

        $options = $this->activeKernelMasterDataQuery($officeCode)
            ->whereIn('kode', $orderedCodes)
            ->pluck('nama_sample', 'kode')
            ->toArray();

        $orderedOptions = [];
        foreach ($orderedCodes as $kode) {
            if (isset($options[$kode])) {
                $orderedOptions[$kode] = $options[$kode];
            }
        }

        return $orderedOptions;
    }

    private function getDirtMoistFormGroups(array $kodeOptions, ?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        // Determine available codes based on office
        $availableCodes = match ($officeCode) {
            'SUN' => self::DIRT_MOIST_KODES_SUN,
            'SJN' => self::DIRT_MOIST_KODES_SJN,
            'ALL' => self::DIRT_MOIST_KODES_YBS,
            default => self::DIRT_MOIST_KODES_YBS,
        };

        $groups = [
            ['title' => 'Inlet Kernel Silo', 'codes' => ['IN1', 'IN2', 'IN3', 'IN4', 'IN5']],
            ['title' => 'Outlet Kernel Silo To Bunker', 'codes' => ['OUT1', 'OUT2', 'OUT3', 'OUT4', 'OUT5']],
        ];

        $result = [];
        foreach ($groups as $group) {
            $items = [];
            foreach ($group['codes'] as $code) {
                // Only include codes that are available for this office
                if (isset($kodeOptions[$code]) && in_array($code, $availableCodes)) {
                    $items[] = ['kode' => $code, 'label' => $kodeOptions[$code]];
                }
            }

            if (!empty($items)) {
                $result[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $result;
    }

    private function getQwtFormGroups(array $kodeOptions, ?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        // Determine available codes based on office
        $availableCodes = match ($officeCode) {
            'SUN' => self::QWT_KODES_SUN,
            'SJN' => self::QWT_KODES_SJN,
            'ALL' => self::QWT_KODES_YBS,
            default => self::QWT_KODES_YBS,
        };

        $groups = [
            ['title' => 'Press 1 - 9', 'codes' => ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9']],
        ];

        $result = [];
        foreach ($groups as $group) {
            $items = [];
            foreach ($group['codes'] as $code) {
                // Only include codes that are available for this office
                if (isset($kodeOptions[$code]) && in_array($code, $availableCodes)) {
                    $items[] = ['kode' => $code, 'label' => $kodeOptions[$code]];
                }
            }

            if (!empty($items)) {
                $result[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $result;
    }

    private function getQwtLimitMap(?string $office = null): array
    {
        return $this->activeKernelMasterDataQuery($office)
            ->whereIn('kode', array_keys($this->getQwtKodeOptions($office)))
            ->get()
            ->mapWithKeys(function ($item) {
                $operator = $item->limit_operator ?? 'le';
                $value = (float) ($item->limit_value ?? 0);

                return [
                    $item->kode => [
                        'bn_tn' => [
                            'operator' => $operator,
                            'value' => $value,
                        ],
                        'moist' => [
                            'operator' => $operator,
                            'value' => $value,
                        ],
                    ],
                ];
            })
            ->toArray();
    }

    private function getDestonerKodeOptions(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        $orderedCodes = match ($officeCode) {
            'SUN' => self::DESTONER_KODES_SUN,
            'SJN' => self::DESTONER_KODES_SJN,
            'ALL' => self::DESTONER_KODES_YBS,
            default => self::DESTONER_KODES_YBS,
        };

        $options = $this->activeKernelMasterDataQuery($officeCode)
            ->whereIn('kode', $orderedCodes)
            ->pluck('nama_sample', 'kode')
            ->toArray();

        $orderedOptions = [];
        foreach ($orderedCodes as $kode) {
            if (isset($options[$kode])) {
                $orderedOptions[$kode] = $options[$kode];
            }
        }

        return $orderedOptions;
    }

    private function getDestonerLimitMap(?string $office = null): array
    {
        return $this->activeKernelMasterDataQuery($office)
            ->whereIn('kode', array_keys($this->getDestonerKodeOptions($office)))
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->kode => [
                        'operator' => $item->limit_operator,
                        'value' => (float) $item->limit_value,
                    ],
                ];
            })
            ->toArray();
    }

    private function getDestonerFormGroups(array $kodeOptions, ?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        // Determine available codes based on office
        $availableCodes = match ($officeCode) {
            'SUN' => self::DESTONER_KODES_SUN,
            'SJN' => self::DESTONER_KODES_SJN,
            'ALL' => self::DESTONER_KODES_YBS,
            default => self::DESTONER_KODES_YBS,
        };

        $groups = [
            ['title' => 'Destoner', 'codes' => ['D1', 'D2']],
        ];

        $result = [];
        foreach ($groups as $group) {
            $items = [];
            foreach ($group['codes'] as $code) {
                // Only include codes that are available for this office
                if (isset($kodeOptions[$code]) && in_array($code, $availableCodes)) {
                    $items[] = ['kode' => $code, 'label' => $kodeOptions[$code]];
                }
            }

            if (!empty($items)) {
                $result[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $result;
    }

    private function getRippleMillKodeOptions(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        $orderedCodes = match ($officeCode) {
            'SUN' => self::RIPPLE_MILL_KODES_SUN,
            'SJN' => self::RIPPLE_MILL_KODES_SJN,
            'ALL' => self::RIPPLE_MILL_KODES_YBS,
            default => self::RIPPLE_MILL_KODES_YBS,
        };

        $options = $this->activeKernelMasterDataQuery($officeCode)
            ->whereIn('kode', $orderedCodes)
            ->pluck('nama_sample', 'kode')
            ->toArray();

        $orderedOptions = [];
        foreach ($orderedCodes as $kode) {
            if (isset($options[$kode])) {
                $orderedOptions[$kode] = $options[$kode];
            }
        }

        return $orderedOptions;
    }

    private function getRippleMillLimitMap(?string $office = null): array
    {
        return $this->activeKernelMasterDataQuery($office)
            ->whereIn('kode', array_keys($this->getRippleMillKodeOptions($office)))
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->kode => [
                        'operator' => $item->limit_operator,
                        'value' => (float) $item->limit_value,
                    ],
                ];
            })
            ->toArray();
    }

    private function getRippleMillFormGroups(array $kodeOptions, ?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        // Determine available codes based on office
        $availableCodes = match ($officeCode) {
            'SUN' => self::RIPPLE_MILL_KODES_SUN,
            'SJN' => self::RIPPLE_MILL_KODES_SJN,
            'ALL' => self::RIPPLE_MILL_KODES_YBS,
            default => self::RIPPLE_MILL_KODES_YBS,
        };

        $groups = [
            ['title' => 'Ripple Mill No. 1 - 7', 'codes' => ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7']],
        ];

        $result = [];
        foreach ($groups as $group) {
            $items = [];
            foreach ($group['codes'] as $code) {
                // Only include codes that are available for this office
                if (isset($kodeOptions[$code]) && in_array($code, $availableCodes)) {
                    $items[] = ['kode' => $code, 'label' => $kodeOptions[$code]];
                }
            }

            if (!empty($items)) {
                $result[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $result;
    }

    private function getDirtMoistKodeOptions(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        $orderedCodes = match ($officeCode) {
            'SUN' => self::DIRT_MOIST_KODES_SUN,
            'SJN' => self::DIRT_MOIST_KODES_SJN,
            'ALL' => self::DIRT_MOIST_KODES_YBS,
            default => self::DIRT_MOIST_KODES_YBS,
        };

        $options = $this->activeKernelMasterDataQuery($officeCode)
            ->whereIn('kode', $orderedCodes)
            ->pluck('nama_sample', 'kode')
            ->toArray();

        $orderedOptions = [];
        foreach ($orderedCodes as $kode) {
            if (isset($options[$kode])) {
                $orderedOptions[$kode] = $options[$kode];
            }
        }

        return $orderedOptions;
    }

    private function getDirtMoistLimitMap(?string $office = null): array
    {
        return $this->activeKernelMasterDataQuery($office)
            ->whereIn('kode', array_keys($this->getDirtMoistKodeOptions($office)))
            ->get()
            ->mapWithKeys(function ($item) {
                $operator = $item->limit_operator ?? 'le';
                $value = (float) ($item->limit_value ?? 0);

                return [
                    $item->kode => [
                        'dirty' => [
                            'operator' => $operator,
                            'value' => $value,
                        ],
                        'moist' => [
                            'operator' => $operator,
                            'value' => $value,
                        ],
                    ],
                ];
            })
            ->toArray();
    }

    private function getPengulanganIntervalHours(string $kode, ?string $office = null): ?int
    {
        // For SUN office, all intervals are 2 hours
        if ($office === 'SUN') {
            return 2;
        }

        // For YBS and other offices (original logic)
        if (preg_match('/^(FC|L|R)/', $kode)) {
            return 2;
        }

        if (preg_match('/^(CWS|IN|OUT)/', $kode)) {
            return 1;
        }

        if (preg_match('/^(P|D)/', $kode)) {
            return 4;
        }

        return null;
    }

    private function validateMachineWindowForKernelInput(string $kode, ?string $office, Carbon $referenceTime, string $moduleName): void
    {
        if (!$office) {
            return;
        }

        $targetCode = $this->normalizeMachineCode($kode);
        if ($targetCode === '') {
            return;
        }

        $currentHour = (int) $referenceTime->format('H');
        $processDates = [$referenceTime->toDateString()];
        if ($currentHour < 7) {
            array_unshift($processDates, $referenceTime->copy()->subDay()->toDateString());
        }

        $processRows = KernelProsses::query()
            ->with('mesin')
            ->whereIn('process_date', $processDates)
            ->where('office', $office)
            ->get();

        if ($processRows->isEmpty()) {
            if (strtoupper((string) $office) !== 'YBS' && $currentHour >= 7) {
                // After 07:00, allow next cycle input even when process schedule is not set yet.
                return;
            }

            throw ValidationException::withMessages([
                'kode' => "Warning: Informasi proses untuk tanggal {$referenceTime->format('d/m/Y')} office {$office} belum tersedia. Input {$moduleName} belum bisa disimpan.",
            ]);
        }

        $windows = [];
        foreach ($processRows as $processRow) {
            foreach ($processRow->mesin as $machine) {
                if (!$machine instanceof KernelMesin) {
                    continue;
                }

                $machineName = strtoupper(trim((string) $machine->machine_name));
                $codes = $this->resolveMachineCodesByName($machineName);
                if (!in_array($targetCode, $codes, true)) {
                    continue;
                }

                $startLabel = substr((string) ($machine->production_start_time ?? ''), 0, 5);
                $endLabel = substr((string) ($machine->production_end_time ?? ''), 0, 5);
                $startMinutes = $this->timeToHourMinutes($startLabel);
                $endMinutes = $this->timeToHourMinutes($endLabel);

                if ($startMinutes === null || $endMinutes === null) {
                    continue;
                }

                if ($currentHour >= 7 && $this->isEarlyMorningCarryOverWindow($startMinutes, $endMinutes)) {
                    continue;
                }

                $windows[] = [
                    'start' => $startMinutes,
                    'end' => $endMinutes,
                    'label' => $startLabel . '-' . $endLabel,
                ];
            }
        }

        if (empty($windows)) {
            throw ValidationException::withMessages([
                'kode' => "Warning: Mesin untuk kode {$kode} belum dicentang/jam mesin hidup belum diisi pada Informasi Proses hari ini. Input {$moduleName} belum bisa disimpan.",
            ]);
        }

        $currentMinutes = $this->timeToHourMinutes($referenceTime->format('H:i'));
        if ($currentMinutes === null) {
            return;
        }

        foreach ($windows as $window) {
            if ($this->isHourWithinRange($currentMinutes, (int) $window['start'], (int) $window['end'])) {
                return;
            }
        }

        $availableWindows = collect($windows)
            ->pluck('label')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        throw ValidationException::withMessages([
            'kode' => "Warning: Jam saat submit ({$referenceTime->format('H:i')}) di luar jam mesin hidup untuk kode {$kode}. Jam yang diizinkan: {$availableWindows}.",
        ]);
    }

    private function isEarlyMorningCarryOverWindow(int $startMinutes, int $endMinutes): bool
    {
        return $startMinutes < 420 && $endMinutes <= 420;
    }

    private function normalizeMachineCode(string $code): string
    {
        return strtoupper(str_replace([' ', '-', '_'], '', trim($code)));
    }

    private function resolveMachineCodesByName(string $machineName): array
    {
        if (preg_match('/^FIBRE\s*CYCLONE\s*(\d+)$/', $machineName, $match)) {
            return ['FC' . $match[1]];
        }

        if (preg_match('/^LTDS\s+(\d+)$/', $machineName, $match)) {
            return ['L' . $match[1]];
        }

        if (preg_match('/^CLAYBATH\s*WET\s*SHELL\s*(\d+)$/', $machineName, $match)) {
            $index = $match[1];
            return $index === '1' ? ['CWS', 'CWS1'] : ['CWS' . $index];
        }

        if (preg_match('/^INLET\s*KERNEL\s*SILO\s*(\d+)$/', $machineName, $match)) {
            return ['IN' . $match[1]];
        }

        if (preg_match('/^OUTLET\s*KERNEL\s*SILO\s*(\d+)\s*TO\s*BUNKER$/', $machineName, $match)) {
            return ['OUT' . $match[1]];
        }

        if (preg_match('/^PRESS\s+(\d+)$/', $machineName, $match)) {
            return ['P' . $match[1]];
        }

        if (preg_match('/^RIPPLE\s*MILL\s*(?:NO\.?\s*)?(\d+)$/', $machineName, $match)) {
            return ['R' . $match[1]];
        }

        if (preg_match('/^DESTONER\s+(\d+)$/', $machineName, $match)) {
            return ['D' . $match[1]];
        }

        return [];
    }

    private function timeToHourMinutes(string $time): ?int
    {
        $value = trim($time);
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return $hour * 60 + $minute;
    }

    private function isHourWithinRange(int $point, int $start, int $end): bool
    {
        if ($end >= $start) {
            return $point >= $start && $point <= $end;
        }

        return $point >= $start || $point <= $end;
    }

    private function validatePengulanganWindow(string $modelClass, string $kode, ?string $office, bool $isPengulangan, string $moduleName, ?Carbon $referenceTime = null): void
    {
        $intervalHours = $this->getPengulanganIntervalHours($kode, $office);
        if (!$intervalHours) {
            throw ValidationException::withMessages([
                'pengulangan' => "Kode {$kode} belum memiliki aturan interval sampel ulang.",
            ]);
        }

        $query = $modelClass::query()
            ->where('kode', $kode)
            ->when($office, fn($q) => $q->where('office', $office));

        if ($this->hasPengulanganColumn($modelClass)) {
            $query->where('pengulangan', false);
        }

        $lastNormalSample = $query
            ->orderByRaw('COALESCE(rounded_time, created_at) DESC')
            ->first();

        if (!$lastNormalSample) {
            if (!$isPengulangan) {
                return;
            }

            throw ValidationException::withMessages([
                'pengulangan' => "Belum ada data awal untuk kode {$kode} pada modul {$moduleName}. Centang sampel ulang setelah data awal tersedia.",
            ]);
        }

        $referenceTime = $referenceTime ?? $this->getRoundedTime();
        $lastSampleTime = $lastNormalSample->rounded_time ?? $lastNormalSample->created_at;
        $elapsedSeconds = $lastSampleTime->diffInSeconds($referenceTime, false);
        $minSeconds = $intervalHours * 3600;

        if ($elapsedSeconds < $minSeconds && !$isPengulangan) {
            $elapsedHours = max(0, $elapsedSeconds) / 3600;
            throw ValidationException::withMessages([
                'pengulangan' => "Input {$kode} masih dalam interval {$intervalHours} jam sejak data normal terakhir ({$elapsedHours} jam). Tandai sebagai sampel ulang.",
            ]);
        }

        if ($elapsedSeconds >= $minSeconds && $isPengulangan) {
            $elapsedHours = max(0, $elapsedSeconds) / 3600;
            throw ValidationException::withMessages([
                'pengulangan' => "Input {$kode} sudah melewati interval {$intervalHours} jam sejak data normal terakhir ({$elapsedHours} jam), sehingga tidak boleh dicentang sebagai sampel ulang.",
            ]);
        }
    }

    private function hasPengulanganColumn(string $modelClass): bool
    {
        if (array_key_exists($modelClass, $this->pengulanganColumnCache)) {
            return $this->pengulanganColumnCache[$modelClass];
        }

        $table = (new $modelClass())->getTable();
        $exists = Schema::hasColumn($table, 'pengulangan');
        $this->pengulanganColumnCache[$modelClass] = $exists;

        return $exists;
    }

    private function prepareCreatePayload(array $payload, string $modelClass, bool $isPengulangan): array
    {
        if ($this->hasPengulanganColumn($modelClass)) {
            $payload['pengulangan'] = $isPengulangan;
        }

        return array_filter($payload, fn($value) => $value !== null);
    }

    private function getOperatorOptionsByOffice(?string $office, string $module): array
    {
        if (!$office) {
            return [];
        }

        return config('operator-options.' . $module . '.' . $office, []);
    }

    private function getSampleBoyOptionsByOffice(?string $office): array
    {
        if (!$office) {
            return [];
        }

        $officeCode = strtoupper(trim((string) $office));

        return config('operator-options.sample_boy.' . $officeCode, []);
    }

    private function getOperatorValidationRules(?string $office, bool $required = false, string $module = 'kernel'): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:255'];
        $operatorOptions = $this->getOperatorOptionsByOffice($office, $module);

        if (!empty($operatorOptions)) {
            $rules[] = Rule::in($operatorOptions);
        }

        return $rules;
    }

    private function getSampleBoyValidationRules(?string $office, bool $required = true): array
    {
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($office);

        $rules = [$required ? 'required' : 'nullable', 'string', 'max:255'];

        if ($required && !empty($sampleBoyOptions)) {
            $rules[] = Rule::in($sampleBoyOptions);
        }

        return $rules;
    }

    private function getUserOffice(): ?string
    {
        return Auth::user()->office;
    }

    private function resolveOfficeFilter(?Request $request = null): string
    {
        $userOffice = $this->getUserOffice();

        if ($userOffice) {
            return $userOffice;
        }

        return $request?->input('office', 'YBS') ?? 'YBS';
    }

    private function applyOfficeFilter($query, string $officeFilter)
    {
        if ($officeFilter !== 'all') {
            $query->where('office', $officeFilter);
        }

        return $query;
    }

    private function activeKernelMasterDataQuery(?string $office = null)
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? '')));

        return KernelMasterData::query()
            ->where('is_active', true)
            ->when($officeCode !== '' && $officeCode !== 'ALL', fn($q) => $q->where('office', $officeCode));
    }

    private function resolveProductionDateRange(string $startDate, string $endDate, ?string $officeFilter = null): array
    {
        $resolvedOffice = $officeFilter ?? $this->resolveOfficeFilter();

        [$startAt] = $this->resolveProductionWindowForDate($startDate, $resolvedOffice);
        [, $endAt] = $this->resolveProductionWindowForDate($endDate, $resolvedOffice);

        if ($endAt->lt($startAt)) {
            $endAt = $startAt->copy()->endOfDay();
        }

        return [
            $startAt->format('Y-m-d H:i:s'),
            $endAt->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveVisibleDateRange(string $startDate, string $endDate, ?string $officeFilter = null): array
    {
        $productionRange = $this->resolveProductionDateRange($startDate, $endDate, $officeFilter);
        $calendarStart = Carbon::parse($startDate)->startOfDay();
        $calendarEnd = Carbon::parse($endDate)->endOfDay();

        $effectiveStart = Carbon::parse($productionRange[0])->min($calendarStart);
        $effectiveEnd = Carbon::parse($productionRange[1])->max($calendarEnd);

        return [
            $effectiveStart->format('Y-m-d H:i:s'),
            $effectiveEnd->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveProductionWindowForDate(string $date, string $officeFilter): array
    {
        $baseDate = Carbon::parse($date)->startOfDay();

        $rows = KernelProsses::query()
            ->whereDate('process_date', $date)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get(['team_1_start_time', 'team_2_start_time', 'team_2_end_time']);

        $team1Start = collect($rows)
            ->pluck('team_1_start_time')
            ->map(fn($value) => $this->extractProductionTimeValue($value, true))
            ->first(fn($value) => $value !== null);

        $team2Window = collect($rows)
            ->map(function ($row) {
                return [
                    'start' => $this->extractProductionTimeValue($row->team_2_start_time ?? null, true),
                    'end' => $this->extractProductionTimeValue($row->team_2_end_time ?? null, true),
                ];
            })
            ->first(fn($window) => !empty($window['start']) && !empty($window['end']));

        $team2Start = $team2Window['start'] ?? null;
        $team2End = $team2Window['end']
            ?? collect($rows)
                ->pluck('team_2_end_time')
                ->map(fn($value) => $this->extractProductionTimeValue($value))
                ->first(fn($value) => $value !== null);

        $startAt = $team1Start
            ? Carbon::parse($date . ' ' . $team1Start)->setSecond(0)
            : $baseDate->copy();

        if (!$team2End) {
            return [$startAt, $baseDate->copy()->endOfDay()];
        }

        $endAt = Carbon::parse($date . ' ' . $team2End)->setSecond(59);
        if ($team2Start) {
            $team2StartAt = Carbon::parse($date . ' ' . $team2Start)->setSecond(0);
            if ($endAt->lte($team2StartAt)) {
                $endAt->addDay();
            }
        } elseif ($endAt->lte($startAt)) {
            $endAt->addDay();
        }

        return [$startAt, $endAt];
    }

    private function extractProductionTimeValue(mixed $value, bool $allowMidnight = false): ?string
    {
        $time = substr(trim((string) $value), 0, 5);
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }

        // In stored process rows, 00:00 is commonly a placeholder for non-input team.
        if (!$allowMidnight && $time === '00:00') {
            return null;
        }

        return $time;
    }

    private function getRoundedTime(): Carbon
    {
        $now = now();
        $minute = (int) $now->format('i');
        $roundedMinute = $minute < 30 ? 0 : 30;

        return $now->copy()->setTime((int) $now->format('H'), $roundedMinute, 0);
    }

    private function roundTimeToHalfHourString(string $time): string
    {
        $value = trim($time);
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $this->getRoundedTime()->format('H:i');
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));

        return sprintf('%02d:%02d', $hour, $minute < 30 ? 0 : 30);
    }

    private function resolveInputRoundedTime(?string $input): Carbon
    {
        $value = trim((string) $input);
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $this->getRoundedTime();
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));

        return now()->copy()->setTime($hour, $minute, 0);
    }

    private function resolveKernelSampleTimestamp(?string $sampleDate, ?string $sampleTime, bool $useManualTime): Carbon
    {
        $dateValue = trim((string) ($sampleDate ?? ''));
        $timeValue = trim((string) ($sampleTime ?? ''));

        if ($dateValue === '' && !$useManualTime) {
            return $this->getRoundedTime();
        }

        if ($dateValue === '') {
            $dateValue = now()->toDateString();
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $timeValue)) {
            $timeValue = $useManualTime ? now()->format('H:i') : $this->getRoundedTime()->format('H:i');
        }

        $timeValue = $this->roundTimeToHalfHourString($timeValue);

        return Carbon::parse($dateValue . ' ' . $timeValue)->setSecond(0);
    }

    private function buildProofInput(string $label, $value, string $unit = '', int $decimals = 2): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'unit' => $unit,
            'decimals' => $decimals,
        ];
    }

    private function buildProofMetric(
        string $label,
        $value,
        string $unit = '',
        int $decimals = 2,
        ?string $limitOperator = null,
        $limitValue = null,
        ?int $limitDecimals = null
    ): array {
        return [
            'label' => $label,
            'value' => $value,
            'unit' => $unit,
            'decimals' => $decimals,
            'limit_operator' => $limitOperator,
            'limit_value' => $limitValue,
            'limit_decimals' => $limitDecimals ?? $decimals,
        ];
    }

    private function buildKernelProofBase(string $module, string $message, $row, ?KernelMasterData $master): array
    {
        return [
            'module' => $module,
            'message' => $message,
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'tanggal_input' => $row->created_at->format('d/m/Y H:i:s'),
            'jam_proses' => $row->rounded_time
                ? $row->rounded_time->format('H:i')
                : $row->created_at->format('H:i'),
            'office' => $row->office,
            'input_by' => $row->user?->name ?? Auth::user()->name,
            'kode' => $row->kode,
            'kode_label' => $row->kode . ' - ' . ($master?->nama_sample ?? '-'),
            'jenis' => $row->jenis ?? '-',
            'operator' => $row->operator ?? '-',
            'sampel_boy' => $row->sampel_boy ?? '-',
        ];
    }

    private function buildKernelProofEntries(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $kodes = collect($rows)
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values();

        $offices = collect($rows)
            ->pluck('office')
            ->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = KernelMasterData::query()
            ->where('is_active', true)
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get(['office', 'kode', 'nama_sample']);

        $masterByOfficeAndKode = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        return collect($rows)->map(function ($row) use ($masterByOfficeAndKode, $masterFallbackByKode) {
            $createdAt = $row->created_at instanceof Carbon
                ? $row->created_at->copy()
                : Carbon::parse((string) $row->created_at);

            $roundedTime = $row->rounded_time ?? null;
            $jamProses = $roundedTime
                ? ($roundedTime instanceof Carbon ? $roundedTime->format('H:i') : Carbon::parse((string) $roundedTime)->format('H:i'))
                : $createdAt->format('H:i');

            $kode = (string) ($row->kode ?? '');
            $officeCode = strtoupper(trim((string) ($row->office ?? '')));
            $master = $masterByOfficeAndKode->get($officeCode . '|' . $kode)
                ?? $masterFallbackByKode->get($kode);

            return [
                'tanggal_input' => $createdAt->format('d/m/Y H:i:s'),
                'jam_proses' => $jamProses,
                'kode' => $kode,
                'kode_label' => $kode . ' - ' . ($master->nama_sample ?? '-'),
                'jenis' => $row->jenis ?? '-',
                'operator' => $row->operator ?? '-',
                'sampel_boy' => $row->sampel_boy ?? '-',
                'input_by' => $row->user?->name ?? Auth::user()->name,
            ];
        })->values()->all();
    }

    private function buildKernelLossesEntriesMetrics(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $kodes = collect($rows)
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values();

        $offices = collect($rows)
            ->pluck('office')
            ->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = KernelMasterData::query()
            ->where('is_active', true)
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get(['office', 'kode', 'nama_sample', 'limit_operator', 'limit_value']);

        $masterByOfficeAndKode = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        return collect($rows)->map(function ($calc) use ($masterByOfficeAndKode, $masterFallbackByKode) {
            $kode = (string) ($calc->kode ?? '');
            $officeCode = strtoupper(trim((string) ($calc->office ?? '')));
            $master = $masterByOfficeAndKode->get($officeCode . '|' . $kode)
                ?? $masterFallbackByKode->get($kode);
            $lossPercent = (float) ($calc->kernel_losses ?? 0) * 100;
            $createdAt = $calc->created_at instanceof Carbon
                ? $calc->created_at->copy()
                : Carbon::parse((string) $calc->created_at);

            return [
                'kode_label' => ($calc->kode ?? '-') . ' - ' . ($master->nama_sample ?? '-'),
                'tanggal_input' => $createdAt->format('d/m/Y H:i:s'),
                'metric' => $this->buildProofMetric(
                    'Kernel Losses',
                    $lossPercent,
                    '%',
                    2,
                    $master?->limit_operator,
                    $master?->limit_value,
                    2
                ),
                'inputs' => [
                    $this->buildProofInput('Berat Sampel', $calc->berat_sampel, ' g', 2),
                    $this->buildProofInput('Nut Utuh - Nut', $calc->nut_utuh_nut, ' g', 2),
                    $this->buildProofInput('Nut Utuh - Kernel', $calc->nut_utuh_kernel, ' g', 2),
                    $this->buildProofInput('Nut Pecah - Nut', $calc->nut_pecah_nut, ' g', 2),
                    $this->buildProofInput('Nut Pecah - Kernel', $calc->nut_pecah_kernel, ' g', 2),
                    $this->buildProofInput('Kernel Utuh', $calc->kernel_utuh, ' g', 2),
                    $this->buildProofInput('Kernel Pecah', $calc->kernel_pecah, ' g', 2),
                ],
            ];
        })->values()->all();
    }

    private function buildKernelLossesProofData(KernelCalculation $calc, string $message): array
    {
        $master = $this->activeKernelMasterDataQuery($calc->office)
            ->where('kode', $calc->kode)
            ->first();
        $lossPercent = (float) ($calc->kernel_losses ?? 0) * 100;

        $proof = $this->buildKernelProofBase('kernel', $message, $calc, $master);
        $proof['metrics'] = [
            $this->buildProofMetric(
                'Kernel Losses',
                $lossPercent,
                '%',
                2,
                $master?->limit_operator,
                $master?->limit_value,
                2
            ),
        ];
        $proof['inputs'] = [
            $this->buildProofInput('Berat Sampel', $calc->berat_sampel, ' g', 2),
            $this->buildProofInput('Nut Utuh - Nut', $calc->nut_utuh_nut, ' g', 2),
            $this->buildProofInput('Nut Utuh - Kernel', $calc->nut_utuh_kernel, ' g', 2),
            $this->buildProofInput('Nut Pecah - Nut', $calc->nut_pecah_nut, ' g', 2),
            $this->buildProofInput('Nut Pecah - Kernel', $calc->nut_pecah_kernel, ' g', 2),
            $this->buildProofInput('Kernel Utuh', $calc->kernel_utuh, ' g', 2),
            $this->buildProofInput('Kernel Pecah', $calc->kernel_pecah, ' g', 2),
        ];

        return $proof;
    }

    private function buildDirtMoistProofData(KernelDirtMoistCalculation $calc, string $message): array
    {
        $master = $this->activeKernelMasterDataQuery($calc->office)
            ->where('kode', $calc->kode)
            ->first();
        $limitMap = $this->getDirtMoistLimitMap($calc->office);
        $limitConfig = $limitMap[$calc->kode] ?? ['dirty' => null, 'moist' => null];

        $proof = $this->buildKernelProofBase('dirt_moist', $message, $calc, $master);
        $proof['metrics'] = [
            $this->buildProofMetric(
                'Dirty to Sampel',
                $calc->dirty_to_sampel,
                '%',
                2,
                data_get($limitConfig, 'dirty.operator'),
                data_get($limitConfig, 'dirty.value'),
                2
            ),
            $this->buildProofMetric(
                'Moist',
                $calc->moist_percent,
                '%',
                2,
                data_get($limitConfig, 'moist.operator'),
                data_get($limitConfig, 'moist.value'),
                2
            ),
        ];
        $proof['inputs'] = [
            $this->buildProofInput('Berat Sampel', $calc->berat_sampel, ' g', 2),
            $this->buildProofInput('Berat Dirty', $calc->berat_dirty, ' g', 2),
            $this->buildProofInput('Moist', $calc->moist_percent, ' %', 2),
        ];

        return $proof;
    }

    private function buildDirtMoistEntriesMetrics(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $kodes = collect($rows)
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values();

        $offices = collect($rows)
            ->pluck('office')
            ->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = KernelMasterData::query()
            ->where('is_active', true)
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get(['office', 'kode', 'nama_sample']);

        $masterByOfficeAndKode = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        return collect($rows)->map(function ($calc) use ($masterByOfficeAndKode, $masterFallbackByKode) {
            $kode = (string) ($calc->kode ?? '');
            $officeCode = strtoupper(trim((string) ($calc->office ?? '')));

            $master = $masterByOfficeAndKode->get($officeCode . '|' . $kode)
                ?? $masterFallbackByKode->get($kode);

            $limitMap = $this->getDirtMoistLimitMap($calc->office);
            $limitConfig = $limitMap[$kode] ?? ['dirty' => null, 'moist' => null];

            return [
                'kode_label' => $kode . ' - ' . ($master->nama_sample ?? '-'),
                'metrics' => [
                    $this->buildProofMetric(
                        'Dirty to Sampel',
                        $calc->dirty_to_sampel,
                        '%',
                        2,
                        data_get($limitConfig, 'dirty.operator'),
                        data_get($limitConfig, 'dirty.value'),
                        2
                    ),
                    $this->buildProofMetric(
                        'Moist',
                        $calc->moist_percent,
                        '%',
                        2,
                        data_get($limitConfig, 'moist.operator'),
                        data_get($limitConfig, 'moist.value'),
                        2
                    ),
                ],
                'inputs' => [
                    $this->buildProofInput('Berat Sampel', $calc->berat_sampel, ' g', 2),
                    $this->buildProofInput('Berat Dirty', $calc->berat_dirty, ' g', 2),
                    $this->buildProofInput('Moist', $calc->moist_percent, ' %', 2),
                ],
            ];
        })->values()->all();
    }

    private function buildQwtProofData(KernelQwt $row, string $message): array
    {
        $master = $this->activeKernelMasterDataQuery($row->office)
            ->where('kode', $row->kode)
            ->first();

        $proof = $this->buildKernelProofBase('qwt', $message, $row, $master);
        $proof['metrics'] = [
            $this->buildProofMetric(
                'BN / TN',
                $row->bn_tn,
                '%',
                2,
                $row->bn_tn_limit_operator,
                $row->bn_tn_limit_value,
                2
            ),
            $this->buildProofMetric(
                'Moisture',
                $row->moisture,
                '%',
                2,
                $row->moist_limit_operator,
                $row->moist_limit_value,
                2
            ),
        ];
        $proof['inputs'] = [
            $this->buildProofInput('Sampel Setelah Kuarter', $row->sampel_setelah_kuarter, ' g', 2),
            $this->buildProofInput('Berat Nut Utuh', $row->berat_nut_utuh, ' g', 2),
            $this->buildProofInput('Berat Nut Pecah', $row->berat_nut_pecah, ' g', 2),
            $this->buildProofInput('Berat Kernel Utuh', $row->berat_kernel_utuh, ' g', 2),
            $this->buildProofInput('Berat Kernel Pecah', $row->berat_kernel_pecah, ' g', 2),
            $this->buildProofInput('Berat Cangkang', $row->berat_cangkang, ' g', 2),
            $this->buildProofInput('Berat Batu', $row->berat_batu, ' g', 2),
            $this->buildProofInput('Ampere Screw', $row->ampere_screw, '', 2),
            $this->buildProofInput('Tekanan Hydraulic', $row->tekanan_hydraulic, '', 2),
            $this->buildProofInput('Kecepatan Screw', $row->kecepatan_screw, '', 2),
        ];

        return $proof;
    }

    private function buildQwtEntriesMetrics(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $kodes = collect($rows)
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values();

        $offices = collect($rows)
            ->pluck('office')
            ->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = KernelMasterData::query()
            ->where('is_active', true)
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get(['office', 'kode', 'nama_sample']);

        $masterByOfficeAndKode = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        return collect($rows)->map(function ($row) use ($masterByOfficeAndKode, $masterFallbackByKode) {
            $kode = (string) ($row->kode ?? '');
            $officeCode = strtoupper(trim((string) ($row->office ?? '')));
            $master = $masterByOfficeAndKode->get($officeCode . '|' . $kode)
                ?? $masterFallbackByKode->get($kode);

            return [
                'kode_label' => $kode . ' - ' . ($master->nama_sample ?? '-'),
                'metrics' => [
                    $this->buildProofMetric(
                        'BN / TN',
                        $row->bn_tn,
                        '%',
                        2,
                        $row->bn_tn_limit_operator,
                        $row->bn_tn_limit_value,
                        2
                    ),
                    $this->buildProofMetric(
                        'Moisture',
                        $row->moisture,
                        '%',
                        2,
                        $row->moist_limit_operator,
                        $row->moist_limit_value,
                        2
                    ),
                ],
                'inputs' => [
                    $this->buildProofInput('Sampel Setelah Kuarter', $row->sampel_setelah_kuarter, ' g', 2),
                    $this->buildProofInput('Berat Nut Utuh', $row->berat_nut_utuh, ' g', 2),
                    $this->buildProofInput('Berat Nut Pecah', $row->berat_nut_pecah, ' g', 2),
                    $this->buildProofInput('Berat Kernel Utuh', $row->berat_kernel_utuh, ' g', 2),
                    $this->buildProofInput('Berat Kernel Pecah', $row->berat_kernel_pecah, ' g', 2),
                    $this->buildProofInput('Berat Cangkang', $row->berat_cangkang, ' g', 2),
                    $this->buildProofInput('Berat Batu', $row->berat_batu, ' g', 2),
                    $this->buildProofInput('Ampere Screw', $row->ampere_screw, '', 2),
                    $this->buildProofInput('Tekanan Hydraulic', $row->tekanan_hydraulic, '', 2),
                    $this->buildProofInput('Kecepatan Screw', $row->kecepatan_screw, '', 2),
                ],
            ];
        })->values()->all();
    }

    private function buildRippleMillProofData(KernelRippleMill $row, string $message): array
    {
        $master = $this->activeKernelMasterDataQuery($row->office)
            ->where('kode', $row->kode)
            ->first();

        $proof = $this->buildKernelProofBase('ripple_mill', $message, $row, $master);
        $proof['metrics'] = [
            $this->buildProofMetric(
                'Efficiency',
                $row->efficiency,
                '%',
                2,
                $master?->limit_operator,
                $master?->limit_value,
                2
            ),
        ];
        $proof['inputs'] = [
            $this->buildProofInput('Berat Sampel', $row->berat_sampel, ' g', 2),
            $this->buildProofInput('Berat Nut Utuh', $row->berat_nut_utuh, ' g', 2),
            $this->buildProofInput('Berat Nut Pecah', $row->berat_nut_pecah, ' g', 2),
        ];

        return $proof;
    }

    private function buildRippleMillEntriesMetrics(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $kodes = collect($rows)
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values();

        $offices = collect($rows)
            ->pluck('office')
            ->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = KernelMasterData::query()
            ->where('is_active', true)
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get(['office', 'kode', 'nama_sample']);

        $masterByOfficeAndKode = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        return collect($rows)->map(function ($row) use ($masterByOfficeAndKode, $masterFallbackByKode) {
            $kode = (string) ($row->kode ?? '');
            $officeCode = strtoupper(trim((string) ($row->office ?? '')));
            $master = $masterByOfficeAndKode->get($officeCode . '|' . $kode)
                ?? $masterFallbackByKode->get($kode);

            return [
                'kode_label' => $kode . ' - ' . ($master->nama_sample ?? '-'),
                'metrics' => [
                    $this->buildProofMetric(
                        'Efficiency',
                        $row->efficiency,
                        '%',
                        2,
                        $master?->limit_operator,
                        $master?->limit_value,
                        2
                    ),
                ],
                'inputs' => [
                    $this->buildProofInput('Berat Sampel', $row->berat_sampel, ' g', 2),
                    $this->buildProofInput('Berat Nut Utuh', $row->berat_nut_utuh, ' g', 2),
                    $this->buildProofInput('Berat Nut Pecah', $row->berat_nut_pecah, ' g', 2),
                ],
            ];
        })->values()->all();
    }

    private function buildDestonerProofData(KernelDestoner $row, string $message): array
    {
        $master = $this->activeKernelMasterDataQuery($row->office)
            ->where('kode', $row->kode)
            ->first();

        $proof = $this->buildKernelProofBase('destoner', $message, $row, $master);
        $proof['metrics'] = [
            $this->buildProofMetric('Total Losses Kernel', $row->total_losses_kernel, '%', 4, null, null, 4),
            $this->buildProofMetric(
                'Loss Kernel/TBS',
                $row->loss_kernel_tbs,
                '',
                8,
                $row->limit_operator,
                $row->limit_value,
                3
            ),
        ];
        $proof['inputs'] = [
            $this->buildProofInput('Berat Sampel', $row->berat_sampel, ' g', 2),
            $this->buildProofInput('Time', $row->time, ' detik', 2),
            $this->buildProofInput('Berat Nut', $row->berat_nut, ' g', 2),
            $this->buildProofInput('Berat Kernel', $row->berat_kernel, ' g', 2),
        ];

        return $proof;
    }

    private function buildDestonerEntriesMetrics(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $kodes = collect($rows)
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values();

        $offices = collect($rows)
            ->pluck('office')
            ->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = KernelMasterData::query()
            ->where('is_active', true)
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get(['office', 'kode', 'nama_sample']);

        $masterByOfficeAndKode = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        return collect($rows)->map(function ($row) use ($masterByOfficeAndKode, $masterFallbackByKode) {
            $kode = (string) ($row->kode ?? '');
            $officeCode = strtoupper(trim((string) ($row->office ?? '')));
            $master = $masterByOfficeAndKode->get($officeCode . '|' . $kode)
                ?? $masterFallbackByKode->get($kode);

            return [
                'kode_label' => $kode . ' - ' . ($master->nama_sample ?? '-'),
                'metrics' => [
                    $this->buildProofMetric('Total Losses Kernel', $row->total_losses_kernel, '%', 4, null, null, 4),
                    $this->buildProofMetric(
                        'Loss Kernel/TBS',
                        $row->loss_kernel_tbs,
                        '',
                        8,
                        $row->limit_operator,
                        $row->limit_value,
                        3
                    ),
                ],
                'inputs' => [
                    $this->buildProofInput('Berat Sampel', $row->berat_sampel, ' g', 2),
                    $this->buildProofInput('Time', $row->time, ' detik', 2),
                    $this->buildProofInput('Berat Nut', $row->berat_nut, ' g', 2),
                    $this->buildProofInput('Berat Kernel', $row->berat_kernel, ' g', 2),
                ],
            ];
        })->values()->all();
    }

    private function calculateKernelBobot(float $value, $config, ?string $kode = null): int
    {
        $normalizedKode = strtoupper((string) $kode);

        if (str_starts_with($normalizedKode, 'IN')) {
            // Inlet Kernel Silo conversion (uses highest matching score first).
            if ($value == 6.80)
                return 100;
            if ($value >= 6.49 && $value <= 7.10)
                return 90;
            if ($value >= 6.00 && $value <= 7.40)
                return 80;
            if ($value >= 5.49 && $value <= 7.70)
                return 70;
            if ($value >= 5.00 && $value <= 8.00)
                return 60;
            if ($value >= 4.50 && $value <= 9.00)
                return 50;
            return 0;
        }

        if (str_starts_with($normalizedKode, 'OUT')) {
            // Outlet Kernel Silo conversion (uses highest matching score first).
            if ($value >= 6.50 && $value <= 7.50)
                return 100;
            if ($value >= 6.10 && $value <= 7.80)
                return 90;
            if ($value >= 5.50 && $value <= 8.00)
                return 80;
            if ($value >= 5.10 && $value <= 9.00)
                return 70;
            if ($value >= 4.50 && $value <= 10.00)
                return 60;
            if ($value >= 0.00 && $value <= 20.00)
                return 50;
            return 0;
        }

        if ($config->direction === 'desc') {
            // Higher is better (≥)
            if ($value >= $config->limit_100)
                return 100;
            if ($value >= $config->limit_90)
                return 90;
            if ($value >= $config->limit_80)
                return 80;
            if ($value >= $config->limit_70)
                return 70;
            if ($value >= $config->limit_60)
                return 60;
            if ($value >= $config->limit_50)
                return 50;
            return 0;
        }

        // asc: Lower is better (≤)
        if ($value <= $config->limit_100)
            return 100;
        if ($value <= $config->limit_90)
            return 90;
        if ($value <= $config->limit_80)
            return 80;
        if ($value <= $config->limit_70)
            return 70;
        if ($value <= $config->limit_60)
            return 60;
        if ($value <= $config->limit_50)
            return 50;
        return 0;
    }

    public function laporan(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses')) {
            abort(403);
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);

        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $kernelQuery = KernelCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, function ($query) use ($kode) {
                $query->where('kode', $kode);
            })
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter));

        $dirtMoistQuery = KernelDirtMoistCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, function ($query) use ($kode) {
                $query->where('kode', $kode);
            })
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter));

        $qwtQuery = KernelQwt::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, function ($query) use ($kode) {
                $query->where('kode', $kode);
            })
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter));

        $rippleMillQuery = KernelRippleMill::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, function ($query) use ($kode) {
                $query->where('kode', $kode);
            })
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter));

        $destonerQuery = KernelDestoner::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, function ($query) use ($kode) {
                $query->where('kode', $kode);
            })
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter));

        $totalRecords = (clone $kernelQuery)->count();
        $avgLosses = (clone $kernelQuery)->avg('kernel_losses');

        $moduleCounts = [
            'kernel' => $totalRecords,
            'dirt_moist' => (clone $dirtMoistQuery)->count(),
            'qwt' => (clone $qwtQuery)->count(),
            'ripple_mill' => (clone $rippleMillQuery)->count(),
            'destoner' => (clone $destonerQuery)->count(),
        ];

        $calculations = $kernelQuery
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'kernel_page');
        $calculations->appends($request->except('kernel_page'));

        $dirtMoistCalculations = $dirtMoistQuery
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'dirt_moist_page');
        $dirtMoistCalculations->appends($request->except('dirt_moist_page'));

        $kernelQwtRows = $qwtQuery
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'qwt_page');
        $kernelQwtRows->appends($request->except('qwt_page'));

        $rippleMillRows = $rippleMillQuery
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'ripple_mill_page');
        $rippleMillRows->appends($request->except('ripple_mill_page'));

        $destonerRows = $destonerQuery
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'destoner_page');
        $destonerRows->appends($request->except('destoner_page'));

        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $kodeOptions = KernelMasterData::getKodeDropdown($officeFilter);

        return view('kernel.laporan', compact(
            'calculations',
            'dirtMoistCalculations',
            'kernelQwtRows',
            'rippleMillRows',
            'destonerRows',
            'masterData',
            'kodeOptions',
            'startDate',
            'endDate',
            'officeFilter',
            'kode',
            'totalRecords',
            'avgLosses',
            'moduleCounts'
        ));
    }

    public function exportLaporan(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses')) {
            abort(403);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $kode = $request->input('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $data = KernelCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->sortBy('created_at')
            ->values();

        $this->logExport('Laporan Kernel Losses', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kode' => $kode,
            'office' => $officeFilter,
        ]);

        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');

        $filename = 'Laporan_Kernel_Losses_' . $startDate . '_to_' . $endDate . '.xlsx';

        return Excel::download(
            new KernelLaporanExport($data, $masterData, $startDate, $endDate, $kode),
            $filename
        );
    }

    private function getUnifiedKernelReportRecords(string $startDate, string $endDate, ?string $officeFilter = null)
    {
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $officeFilter = $officeFilter ?? $this->resolveOfficeFilter();

        $kernelRows = KernelCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->map(function ($row) {
                $row->source_module = 'Kernel Losses';
                return $row;
            });

        $dirtMoistRows = KernelDirtMoistCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->map(function ($row) {
                $metricPercent = (float) ($row->dirty_to_sampel ?? 0);

                return (object) [
                    'created_at' => $row->created_at,
                    'rounded_time' => $row->rounded_time,
                    'kegiatan_dispek' => $row->kegiatan_dispek,
                    'office' => $row->office,
                    'kode' => $row->kode,
                    'jenis' => $row->jenis,
                    'operator' => $row->operator,
                    'sampel_boy' => $row->sampel_boy,
                    'remarks' => $row->remarks,
                    'berat_sampel' => $row->berat_sampel,
                    'nut_utuh_nut' => null,
                    'nut_utuh_kernel' => null,
                    'nut_pecah_nut' => null,
                    'nut_pecah_kernel' => null,
                    'kernel_utuh' => null,
                    'kernel_pecah' => null,
                    'kernel_to_sampel_nut_utuh' => null,
                    'kernel_to_sampel_nut_pecah' => null,
                    'kernel_utuh_to_sampel' => null,
                    'kernel_pecah_to_sampel' => null,
                    'kernel_losses' => $metricPercent / 100,
                    'user' => $row->user,
                    'source_module' => 'Dirt & Moist (%Dirty)',
                ];
            });

        $moistRows = KernelDirtMoistCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->map(function ($row) {
                $metricPercent = (float) ($row->moist_percent ?? 0);

                return (object) [
                    'created_at' => $row->created_at,
                    'rounded_time' => $row->rounded_time,
                    'kegiatan_dispek' => $row->kegiatan_dispek,
                    'office' => $row->office,
                    'kode' => $row->kode,
                    'jenis' => $row->jenis,
                    'operator' => $row->operator,
                    'sampel_boy' => $row->sampel_boy,
                    'remarks' => $row->remarks,
                    'berat_sampel' => $row->berat_sampel,
                    'nut_utuh_nut' => null,
                    'nut_utuh_kernel' => null,
                    'nut_pecah_nut' => null,
                    'nut_pecah_kernel' => null,
                    'kernel_utuh' => null,
                    'kernel_pecah' => null,
                    'kernel_to_sampel_nut_utuh' => null,
                    'kernel_to_sampel_nut_pecah' => null,
                    'kernel_utuh_to_sampel' => null,
                    'kernel_pecah_to_sampel' => null,
                    'kernel_losses' => $metricPercent / 100,
                    'user' => $row->user,
                    'source_module' => 'Dirt & Moist (%Moist)',
                ];
            });

        $qwtRows = KernelQwt::with('user')
            ->whereBetween('created_at', $range)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->map(function ($row) {
                $metricPercent = (float) ($row->bn_tn ?? 0);

                return (object) [
                    'created_at' => $row->created_at,
                    'rounded_time' => $row->rounded_time,
                    'kegiatan_dispek' => $row->kegiatan_dispek,
                    'office' => $row->office,
                    'kode' => $row->kode,
                    'jenis' => $row->jenis,
                    'operator' => $row->operator,
                    'sampel_boy' => $row->sampel_boy,
                    'remarks' => $row->remarks,
                    'berat_sampel' => $row->sampel_setelah_kuarter,
                    'nut_utuh_nut' => null,
                    'nut_utuh_kernel' => null,
                    'nut_pecah_nut' => null,
                    'nut_pecah_kernel' => null,
                    'kernel_utuh' => null,
                    'kernel_pecah' => null,
                    'kernel_to_sampel_nut_utuh' => null,
                    'kernel_to_sampel_nut_pecah' => null,
                    'kernel_utuh_to_sampel' => null,
                    'kernel_pecah_to_sampel' => null,
                    'kernel_losses' => $metricPercent / 100,
                    'user' => $row->user,
                    'source_module' => 'QWT (BN/TN)',
                ];
            });

        $rippleRows = KernelRippleMill::with('user')
            ->whereBetween('created_at', $range)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->map(function ($row) {
                $metricPercent = (float) ($row->efficiency ?? 0);

                return (object) [
                    'created_at' => $row->created_at,
                    'rounded_time' => $row->rounded_time,
                    'kegiatan_dispek' => $row->kegiatan_dispek,
                    'office' => $row->office,
                    'kode' => $row->kode,
                    'jenis' => $row->jenis,
                    'operator' => $row->operator,
                    'sampel_boy' => $row->sampel_boy,
                    'remarks' => $row->remarks,
                    'berat_sampel' => $row->berat_sampel,
                    'nut_utuh_nut' => null,
                    'nut_utuh_kernel' => null,
                    'nut_pecah_nut' => null,
                    'nut_pecah_kernel' => null,
                    'kernel_utuh' => null,
                    'kernel_pecah' => null,
                    'kernel_to_sampel_nut_utuh' => null,
                    'kernel_to_sampel_nut_pecah' => null,
                    'kernel_utuh_to_sampel' => null,
                    'kernel_pecah_to_sampel' => null,
                    'kernel_losses' => $metricPercent / 100,
                    'user' => $row->user,
                    'source_module' => 'Ripple Mill (Efficiency)',
                ];
            });

        $destonerRows = KernelDestoner::with('user')
            ->whereBetween('created_at', $range)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->map(function ($row) {
                $metricPercent = (float) ($row->total_losses_kernel ?? 0);

                return (object) [
                    'created_at' => $row->created_at,
                    'rounded_time' => $row->rounded_time,
                    'kegiatan_dispek' => $row->kegiatan_dispek,
                    'office' => $row->office,
                    'kode' => $row->kode,
                    'jenis' => $row->jenis,
                    'operator' => $row->operator,
                    'sampel_boy' => $row->sampel_boy,
                    'remarks' => $row->remarks,
                    'berat_sampel' => $row->berat_sampel,
                    'nut_utuh_nut' => null,
                    'nut_utuh_kernel' => null,
                    'nut_pecah_nut' => null,
                    'nut_pecah_kernel' => null,
                    'kernel_utuh' => null,
                    'kernel_pecah' => null,
                    'kernel_to_sampel_nut_utuh' => null,
                    'kernel_to_sampel_nut_pecah' => null,
                    'kernel_utuh_to_sampel' => null,
                    'kernel_pecah_to_sampel' => null,
                    'kernel_losses' => $metricPercent / 100,
                    'user' => $row->user,
                    'source_module' => 'Destoner (Total Losses)',
                ];
            });

        return collect()
            ->concat($kernelRows)
            ->concat($dirtMoistRows)
            ->concat($moistRows)
            ->concat($qwtRows)
            ->concat($rippleRows)
            ->concat($destonerRows);
    }

    private function resolveDisplayTimestamp(object $row): Carbon
    {
        $rounded = data_get($row, 'rounded_time');
        if ($rounded instanceof Carbon) {
            return $rounded->copy();
        }

        if (!empty($rounded)) {
            return Carbon::parse((string) $rounded);
        }

        $createdAt = data_get($row, 'created_at');
        if ($createdAt instanceof Carbon) {
            return $createdAt->copy();
        }

        return Carbon::parse((string) $createdAt);
    }

    private function resolveProductionDateKeyByCutoff(Carbon $timestamp, int $cutoffHour = 7): string
    {
        $productionMoment = $timestamp->copy();
        if ((int) $productionMoment->format('H') < $cutoffHour) {
            $productionMoment->subDay();
        }

        return $productionMoment->format('Y-m-d');
    }

    // ─── Individual module laporan pages ───────────────────────────────────────

    public function laporanDirtMoist(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelDirtMoistCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at');

        $totalRecords = (clone $query)->count();
        $rows = $query->paginate(25)->appends($request->except('page'));
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $kodeOptions = KernelMasterData::getKodeDropdown($officeFilter);

        $avgDirty = KernelDirtMoistCalculation::whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->avg('dirty_to_sampel');
        $avgMoist = KernelDirtMoistCalculation::whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->avg('moist_percent');

        return view('kernel.laporan-dirt-moist', compact(
            'rows',
            'masterData',
            'kodeOptions',
            'startDate',
            'endDate',
            'officeFilter',
            'kode',
            'totalRecords',
            'avgDirty',
            'avgMoist'
        ));
    }

    public function exportLaporanDirtMoist(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $data = KernelDirtMoistCalculation::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at')
            ->get();

        $this->logExport('Laporan Dirt & Moist', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kode' => $kode,
            'office' => $officeFilter,
        ]);

        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $filename = 'laporan-dirt-moist-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(
            new KernelDirtMoistExport($data, $masterData, $startDate, $endDate, $kode),
            $filename
        );
    }

    public function laporanQwt(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelQwt::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at');

        $totalRecords = (clone $query)->count();
        $rows = $query->paginate(25)->appends($request->except('page'));
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $kodeOptions = KernelMasterData::getKodeDropdown($officeFilter);

        $avgBnTn = KernelQwt::whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->avg('bn_tn');
        $avgMoist = KernelQwt::whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->avg('moisture');

        return view('kernel.laporan-qwt', compact(
            'rows',
            'masterData',
            'kodeOptions',
            'startDate',
            'endDate',
            'officeFilter',
            'kode',
            'totalRecords',
            'avgBnTn',
            'avgMoist'
        ));
    }

    public function exportLaporanQwt(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $data = KernelQwt::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at')
            ->get();

        $this->logExport('Laporan QWT Fibre Press', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kode' => $kode,
            'office' => $officeFilter,
        ]);

        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $filename = 'laporan-qwt-fibre-press-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(
            new KernelQwtExport($data, $masterData, $startDate, $endDate, $kode),
            $filename
        );
    }

    public function laporanRippleMill(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelRippleMill::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at');

        $totalRecords = (clone $query)->count();
        $rows = $query->paginate(25)->appends($request->except('page'));
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $kodeOptions = KernelMasterData::getKodeDropdown($officeFilter);

        $avgEfficiency = KernelRippleMill::whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->avg('efficiency');

        return view('kernel.laporan-ripple-mill', compact(
            'rows',
            'masterData',
            'kodeOptions',
            'startDate',
            'endDate',
            'officeFilter',
            'kode',
            'totalRecords',
            'avgEfficiency'
        ));
    }

    public function exportLaporanRippleMill(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $data = KernelRippleMill::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at')
            ->get();

        $this->logExport('Laporan Ripple Mill', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kode' => $kode,
            'office' => $officeFilter,
        ]);

        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $filename = 'laporan-ripple-mill-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(
            new KernelRippleMillExport($data, $masterData, $startDate, $endDate, $kode),
            $filename
        );
    }

    public function laporanDestoner(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $query = KernelDestoner::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at');

        $totalRecords = (clone $query)->count();
        $rows = $query->paginate(25)->appends($request->except('page'));
        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $kodeOptions = KernelMasterData::getKodeDropdown($officeFilter);

        $avgLossKernelTbs = KernelDestoner::whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->avg('loss_kernel_tbs');

        return view('kernel.laporan-destoner', compact(
            'rows',
            'masterData',
            'kodeOptions',
            'startDate',
            'endDate',
            'officeFilter',
            'kode',
            'totalRecords',
            'avgLossKernelTbs'
        ));
    }

    public function exportLaporanDestoner(Request $request)
    {
        if (!Auth::user()->can('view laporan kernel losses'))
            abort(403);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $kode = $request->get('kode');
        $officeFilter = $this->resolveOfficeFilter($request);
        $range = $this->resolveVisibleDateRange($startDate, $endDate, $officeFilter);

        $data = KernelDestoner::with('user')
            ->whereBetween('created_at', $range)
            ->when($kode, fn($q) => $q->where('kode', $kode))
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('created_at')
            ->get();

        $this->logExport('Laporan Destoner', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kode' => $kode,
            'office' => $officeFilter,
        ]);

        $masterData = $this->activeKernelMasterDataQuery($officeFilter)->get()->keyBy('kode');
        $filename = 'laporan-destoner-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(
            new KernelDestonerExport($data, $masterData, $startDate, $endDate, $kode),
            $filename
        );
    }
}
