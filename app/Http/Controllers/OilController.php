<?php

namespace App\Http\Controllers;

use App\Models\OilCalculation;
use App\Models\OilMasterData;
use App\Models\OilRecord;
use App\Models\BobotConfig;
use App\Models\DailyBobotAverage;
use App\Models\User;
use App\Services\OilService;
use App\Exports\OlwbExport;
use App\Exports\PerformanceExport;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class OilController extends Controller
{
    use LogsActivity;

    protected OilService $oilService;

    public function __construct(OilService $oilService)
    {
        $this->oilService = $oilService;
    }

    /**
     * Display a listing of lab calculations (oil loss records).
     */
    public function index(Request $request)
    {
        // Default filter date: hari ini untuk start dan end
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Office filter: user dengan office hanya boleh lihat office-nya sendiri
        $userOffice = Auth::user()->office;
        $officeFilter = $userOffice ?: $request->input('office', 'YBS');
        // Query for Mode 2: Lab Calculations (numeric data)
        // Multi-tenancy: filter by selected office or user's office
        $calculationsQuery = OilCalculation::with(['user']);

        // Apply office filter jika bukan 'all'
        if ($officeFilter !== 'all') {
            $calculationsQuery->where('office', $officeFilter);
        }

        $calculationsQuery->whereBetween('tanggal_sampel', [$startDate, $endDate])
            ->orderBy('tanggal_sampel', 'desc')
            ->orderBy('created_at', 'desc');

        // Query for Mode 1: Lab Records (non-numeric data)
        // Multi-tenancy: filter by selected office or user's office
        $recordsQuery = OilRecord::with(['user']);

        // Apply office filter jika bukan 'all'
        if ($officeFilter !== 'all') {
            $recordsQuery->where('office', $officeFilter);
        }

        $recordsQuery->whereBetween('tanggal_sampel', [$startDate, $endDate])
            ->orderBy('tanggal_sampel', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by kode if provided (optional)
        if ($request->filled('kode')) {
            $calculationsQuery->where('kode', $request->kode);
            $recordsQuery->where('kode', $request->kode);
        }

        // Check permission - if user can only view own results
        if (!Auth::user()->can('view oil losses')) {
            $calculationsQuery->where('user_id', Auth::id());
            $recordsQuery->where('user_id', Auth::id());
        }

        // Get statistics based on FILTERED queries (before pagination)
        $totalCalculations = (clone $calculationsQuery)->count();
        $totalRecords = (clone $recordsQuery)->count();
        $todayProductionDate = now()->toDateString();

        // Statistics query
        $todayCalculations = OilCalculation::whereDate('tanggal_sampel', $todayProductionDate);
        $todayRecords = OilRecord::whereDate('tanggal_sampel', $todayProductionDate);

        // Apply office filter to today's statistics
        if ($officeFilter !== 'all') {
            $todayCalculations->where('office', $officeFilter);
            $todayRecords->where('office', $officeFilter);
        }

        $statistics = [
            'total_records' => $totalCalculations + $totalRecords,
            'records_today' => $todayCalculations->count() + $todayRecords->count(),
            'calculations_count' => $totalCalculations,
            'records_count' => $totalRecords,
        ];

        // Paginate results
        $oilLosses = $calculationsQuery->paginate(15, ['*'], 'calculations');
        $oilRecords = $recordsQuery->paginate(15, ['*'], 'records');

        // Preserve query parameters in pagination links
        $oilLosses->appends($request->only(['start_date', 'end_date', 'kode', 'office']));
        $oilRecords->appends($request->only(['start_date', 'end_date', 'kode', 'office']));

        // Get all kode options for filter dropdown
        $kodeOptions = OilMasterData::getKodeDropdown(Auth::user()->office);

        return view('oil.index', compact('oilLosses', 'oilRecords', 'statistics', 'kodeOptions', 'startDate', 'endDate', 'officeFilter'));
    }

    /**
     * Show the form for creating a new lab record with dual-mode input.
     */
    public function create()
    {
        // Allow both 'create oil results' (PPIC) and 'input oil losses' (Sampel Boy)
        if (!Auth::user()->can('create oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data oil losses.');
        }

        // Get dropdown options from master data
        $kodeOptions = OilMasterData::getKodeDropdown(Auth::user()->office);
        $jenisOptions = OilMasterData::getJenisDropdown();
        $operatorOptions = $this->getOperatorOptionsByOffice(Auth::user()->office);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice(Auth::user()->office);

        return view('oil.create', compact('kodeOptions', 'jenisOptions', 'operatorOptions', 'sampleBoyOptions'));
    }

    /**
     * Store lab data with dual-mode input (non-numeric or numeric).
     * Tanggal dan jam otomatis dari created_at timestamp.
     */
    public function store(Request $request)
    {
        // Allow both 'create oil results' (PPIC) and 'input oil losses' (Sampel Boy)
        if (!Auth::user()->can('create oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk input data oil losses.');
        }

        // Validate: User MUST have office assigned to input data
        if (!Auth::user()->office) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda harus memiliki Office yang ditentukan untuk dapat input data.'], 422);
            }
            return back()
                ->withInput()
                ->with('error', 'Anda harus memiliki Office yang ditentukan untuk dapat input data. Silakan hubungi Administrator.');
        }

        // Custom validation: Jika 1 field di mode diisi, semua field di mode tersebut WAJIB diisi
        // HANYA cek field yang user input manual (kode & operator)
        // Jenis dan Sampel Boy diabaikan karena punya default value
        $mode1UserFields = ['kode', 'operator'];
        $phase = $this->detectMode2Phase($request);
        $mode2Fields = ['cawan_kosong', 'berat_basah', 'cawan_sample_kering', 'labu_kosong', 'oil_labu'];

        // Cek apakah ada field Mode 1 yang user isi manual (kode atau operator)
        $mode1HasAnyValue = collect($mode1UserFields)->contains(fn($field) => $request->filled($field));

        // Cek apakah ada field Mode 2 yang diisi
        $mode2HasAnyValue = collect($mode2Fields)->contains(fn($field) => $request->filled($field));

        $validated = $request->validate([
            'kode' => $mode1HasAnyValue ? 'required|string|exists:oil_master_data,kode' : 'nullable|string|exists:oil_master_data,kode',
            'operator' => $mode1HasAnyValue ? 'required|string|max:255' : 'nullable|string|max:255',
            'jenis' => 'nullable|string',
            'sampel_boy' => 'nullable|string|max:255',
            'parameter_lain' => 'nullable|string|max:500',
            'phase' => 'nullable|in:initial,final,complete',
            'tanggal_sampel_mode1' => $mode1HasAnyValue ? 'required|date|before_or_equal:today' : 'nullable|date|before_or_equal:today',
            'kode_mode2' => $mode2HasAnyValue ? 'required|string|exists:oil_master_data,kode' : 'nullable|string|exists:oil_master_data,kode',
            'cawan_kosong' => 'nullable|numeric|min:0',
            'berat_basah' => 'nullable|numeric|min:0',
            'cawan_sample_kering' => 'nullable|numeric|min:0',
            'labu_kosong' => 'nullable|numeric|min:0',
            'oil_labu' => 'nullable|numeric|min:0',
            'tanggal_sampel_mode2' => $mode2HasAnyValue ? 'required|date|before_or_equal:today' : 'nullable|date|before_or_equal:today',
            'mode1_rows.*.tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'mode1_rows.*.jam_sampel' => 'nullable|date_format:H:i',
            'mode1_rows.*.sampel_boy' => Auth::user()->office === 'YBS'
                ? $this->getSampleBoyValidationRules(Auth::user()->office, false)
                : $this->getSampleBoyValidationRules(Auth::user()->office),
            'mode2_rows.*.tanggal_sampel' => 'nullable|date|before_or_equal:today',
            'mode2_rows.*.jam_sampel' => 'nullable|date_format:H:i',
        ], [
            'kode.required' => 'Kode wajib diisi jika Anda mengisi Operator',
            'kode.exists' => 'Kode tidak valid atau tidak ditemukan di master data',
            'operator.required' => 'Operator wajib diisi jika Anda mengisi Kode',
            'kode_mode2.required' => 'Kode wajib diisi jika Anda mengisi Mode Angka',
            'kode_mode2.exists' => 'Kode tidak valid atau tidak ditemukan di master data',
            'cawan_kosong.required' => 'Cawan Kosong wajib diisi jika Anda mengisi Mode Angka',
            'berat_basah.required' => 'Berat Basah wajib diisi jika Anda mengisi Mode Angka',
            'labu_kosong.required' => 'Labu Kosong wajib diisi pada Tahap 1 atau input sekaligus.',
            'cawan_sample_kering.required' => 'Cawan + Sample Kering wajib diisi pada Tahap 2 atau input sekaligus.',
            'oil_labu.required' => 'Oil + Labu wajib diisi jika Anda mengisi Mode Angka',
            'tanggal_sampel_mode1.before_or_equal' => 'Tanggal Sampel tidak boleh melebihi hari ini.',
            'tanggal_sampel_mode2.before_or_equal' => 'Tanggal Sampel tidak boleh melebihi hari ini.',
            'mode1_rows.*.tanggal_sampel.before_or_equal' => 'Tanggal Sampel pada Mode Non-Angka tambahan tidak boleh melebihi hari ini.',
            'mode1_rows.*.sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'mode1_rows.*.sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
            'mode2_rows.*.tanggal_sampel.before_or_equal' => 'Tanggal Sampel pada Mode Angka tambahan tidak boleh melebihi hari ini.',
        ]);

        // Server-side safety: enforce phase completeness for main Mode Angka payload.
        $this->assertMode2RowPhaseCompleteness([
            'kode_mode2' => $validated['kode_mode2'] ?? null,
            'cawan_kosong' => $validated['cawan_kosong'] ?? null,
            'berat_basah' => $validated['berat_basah'] ?? null,
            'cawan_sample_kering' => $validated['cawan_sample_kering'] ?? null,
            'labu_kosong' => $validated['labu_kosong'] ?? null,
            'oil_labu' => $validated['oil_labu'] ?? null,
        ], 'Mode Angka utama');

        try {
            DB::beginTransaction();

            $savedMode1 = [];
            $savedMode2 = [];
            $messages = [];
            $result = ['message' => null, 'results' => []];

            $hasPrimaryMode1 = !empty($validated['kode']) || !empty($validated['operator']);
            $hasPrimaryMode2 = !empty($validated['kode_mode2'])
                && collect(['cawan_kosong', 'berat_basah', 'cawan_sample_kering', 'labu_kosong', 'oil_labu'])
                    ->contains(fn($field) => array_key_exists($field, $validated) && $validated[$field] !== null && $validated[$field] !== '');

            if ($hasPrimaryMode1 || $hasPrimaryMode2) {
                $result = $this->oilService->store($validated, Auth::id(), Auth::user()->office);

                if (isset($result['results']['mode1']) && $result['results']['mode1'] instanceof OilRecord) {
                    $savedMode1[] = $result['results']['mode1'];
                }

                if (isset($result['results']['mode2']) && $result['results']['mode2'] instanceof OilCalculation) {
                    $savedMode2[] = $result['results']['mode2'];
                }

                if (!empty($result['message'])) {
                    $messages[] = $result['message'];
                }
            }

            $additionalRowsResult = $this->storeAdditionalRows(
                $request,
                Auth::id(),
                Auth::user()->office,
                $validated['tanggal_sampel_mode1'] ?? now()->toDateString(),
                $validated['tanggal_sampel_mode2'] ?? now()->toDateString()
            );
            $savedMode1 = array_merge($savedMode1, $additionalRowsResult['mode1']);
            $savedMode2 = array_merge($savedMode2, $additionalRowsResult['mode2']);
            if (!empty($additionalRowsResult['messages'])) {
                $messages = array_merge($messages, $additionalRowsResult['messages']);
            }

            if (empty($savedMode1) && empty($savedMode2)) {
                throw new Exception('Minimal satu form harus diisi sebelum disimpan.');
            }

            $message = implode(' ', array_filter($messages));
            if ($message === '') {
                $message = $result['message'] ?? 'Data berhasil disimpan.';
            }

            $affectedDates = collect($savedMode1)
                ->merge($savedMode2)
                ->map(fn($item) => $item->tanggal_sampel instanceof Carbon ? $item->tanggal_sampel->toDateString() : (string) $item->tanggal_sampel)
                ->filter()
                ->unique()
                ->values();

            $affectedDates->each(fn($date) => $this->recalculateDailyBobotAverage($date));

            // Log activity
            if (isset($result['results'])) {
                $loggedModel = null;
                $kode = null;
                $mode = [];

                if (isset($result['results']['mode1'])) {
                    $loggedModel = $result['results']['mode1'];
                    $kode = $loggedModel->kode;
                    $mode[] = 'non-numeric';
                }

                if (isset($result['results']['mode2'])) {
                    $loggedModel = $result['results']['mode2'];
                    $kode = $loggedModel->kode;
                    $mode[] = 'numeric';
                }

                if ($loggedModel && $kode) {
                    $this->logCreate(
                        $loggedModel,
                        "Input data oil losses untuk kode {$kode}",
                        ['mode' => implode(' + ', $mode)]
                    );
                }

            }

            $proofData = $this->buildSuccessProofData(
                $savedMode1[0] ?? null,
                $savedMode2[0] ?? null,
                $message,
                $savedMode1,
                $savedMode2,
            );

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'proof' => $proofData,
                ]);
            }

            return redirect()
                ->route('oil.index')
                ->with('success', $message)
                ->with('success_proof', $proofData);

        } catch (Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    private function storeAdditionalRows(Request $request, int $userId, string $userOffice, string $defaultMode1Date, string $defaultMode2Date): array
    {
        $savedMode1 = [];
        $savedMode2 = [];
        $messages = [];

        $mode1Rows = collect($request->input('mode1_rows', []))
            ->filter(fn($row) => is_array($row));

        foreach ($mode1Rows as $row) {
            $kode = trim((string) ($row['kode'] ?? ''));
            $operator = trim((string) ($row['operator'] ?? ''));
            $sampelBoy = trim((string) ($row['sampel_boy'] ?? ''));

            if ($kode === '' && $operator === '') {
                continue;
            }

            if ($kode === '' || $operator === '') {
                throw new Exception('Untuk data tambahan Mode Non-Angka, Kode dan Operator wajib diisi bersamaan.');
            }

            if ($userOffice === 'YBS' && !empty($this->getSampleBoyOptionsByOffice($userOffice)) && $sampelBoy === '') {
                throw new Exception('Untuk data tambahan Mode Non-Angka, Sampel Boy wajib dipilih.');
            }

            $payload = [
                'kode' => $kode,
                'jenis' => $row['jenis'] ?? 'TBS',
                'operator' => $operator,
                'sampel_boy' => $sampelBoy !== '' ? $sampelBoy : Auth::user()->name,
                'parameter_lain' => $row['parameter_lain'] ?? null,
                'tanggal_sampel' => $row['tanggal_sampel'] ?? $defaultMode1Date,
                'jam_sampel' => $row['jam_sampel'] ?? null,
            ];

            $result = $this->oilService->store($payload, $userId, $userOffice);
            if (isset($result['results']['mode1']) && $result['results']['mode1'] instanceof OilRecord) {
                $savedMode1[] = $result['results']['mode1'];
            }
            if (!empty($result['message'])) {
                $messages[] = $result['message'];
            }
        }

        $mode2Rows = collect($request->input('mode2_rows', []))
            ->filter(fn($row) => is_array($row));

        foreach ($mode2Rows as $row) {
            $this->assertMode2RowPhaseCompleteness($row, 'Mode Angka tambahan');

            $kodeMode2 = trim((string) ($row['kode_mode2'] ?? ''));
            $numericFields = ['cawan_kosong', 'berat_basah', 'cawan_sample_kering', 'labu_kosong', 'oil_labu'];
            $hasAnyValue = collect($numericFields)->contains(function ($field) use ($row) {
                return array_key_exists($field, $row) && $row[$field] !== null && $row[$field] !== '';
            });

            if ($kodeMode2 === '' && !$hasAnyValue) {
                continue;
            }

            if ($kodeMode2 === '') {
                throw new Exception('Untuk data tambahan Mode Angka, Kode wajib diisi.');
            }

            $payload = [
                'kode_mode2' => $kodeMode2,
                'cawan_kosong' => $row['cawan_kosong'] ?? null,
                'berat_basah' => $row['berat_basah'] ?? null,
                'cawan_sample_kering' => $row['cawan_sample_kering'] ?? null,
                'labu_kosong' => $row['labu_kosong'] ?? null,
                'oil_labu' => $row['oil_labu'] ?? null,
                'tanggal_sampel' => $row['tanggal_sampel'] ?? $defaultMode2Date,
                'jam_sampel' => $row['jam_sampel'] ?? null,
            ];

            $result = $this->oilService->store($payload, $userId, $userOffice);
            if (isset($result['results']['mode2']) && $result['results']['mode2'] instanceof OilCalculation) {
                $savedMode2[] = $result['results']['mode2'];
            }
            if (!empty($result['message'])) {
                $messages[] = $result['message'];
            }
        }

        return [
            'mode1' => $savedMode1,
            'mode2' => $savedMode2,
            'messages' => $messages,
        ];
    }

    private function assertMode2RowPhaseCompleteness(array $row, string $contextLabel): void
    {
        $kodeMode2 = trim((string) ($row['kode_mode2'] ?? ''));
        $numericFields = ['cawan_kosong', 'berat_basah', 'cawan_sample_kering', 'labu_kosong', 'oil_labu'];

        $hasAnyNumeric = collect($numericFields)->contains(function ($field) use ($row) {
            $value = $row[$field] ?? null;
            return $value !== null && $value !== '';
        });

        if (!$hasAnyNumeric && $kodeMode2 === '') {
            return;
        }

        if ($kodeMode2 === '') {
            throw new Exception("{$contextLabel}: Kode wajib diisi jika ada input angka.");
        }

        $phase = $this->detectMode2PhaseFromArray($row);

        if ($phase === 'initial') {
            foreach (['cawan_kosong', 'berat_basah', 'labu_kosong'] as $field) {
                if (($row[$field] ?? null) === null || ($row[$field] ?? '') === '') {
                    throw new Exception("{$contextLabel}: Tahap 1 harus lengkap (Cawan Kosong, Berat Sampel Basah, Labu Kosong). Kode: {$kodeMode2}.");
                }
            }
        } elseif ($phase === 'final') {
            if (($row['cawan_sample_kering'] ?? null) === null || ($row['cawan_sample_kering'] ?? '') === '') {
                throw new Exception("{$contextLabel}: Tahap 2 membutuhkan Cawan + Sampel Kering. Kode: {$kodeMode2}.");
            }
        } elseif ($phase === 'complete') {
            if (($row['oil_labu'] ?? null) === null || ($row['oil_labu'] ?? '') === '') {
                throw new Exception("{$contextLabel}: Tahap Akhir membutuhkan Oil + Labu. Kode: {$kodeMode2}.");
            }
        }
    }

    private function detectMode2PhaseFromArray(array $row): string
    {
        $hasStep1 = collect(['cawan_kosong', 'berat_basah', 'labu_kosong'])->contains(function ($field) use ($row) {
            $value = $row[$field] ?? null;
            return $value !== null && $value !== '';
        });

        $hasStep2 = ($row['cawan_sample_kering'] ?? null) !== null && ($row['cawan_sample_kering'] ?? '') !== '';
        $hasFinal = ($row['oil_labu'] ?? null) !== null && ($row['oil_labu'] ?? '') !== '';

        if ($hasFinal) {
            return 'complete';
        }

        if ($hasStep2) {
            return 'final';
        }

        if ($hasStep1) {
            return 'initial';
        }

        return 'initial';
    }

    /**
     * Detect phase otomatis dari field Mode 2 yang terisi.
     */
    private function detectMode2Phase(Request $request): string
    {
        $hasStep1 = collect(['cawan_kosong', 'berat_basah', 'labu_kosong'])
            ->contains(fn($field) => $request->filled($field));

        $hasStep2 = $request->filled('cawan_sample_kering');
        $hasFinal = $request->filled('oil_labu');

        // Jika oil+labu diisi, dianggap tahap akhir (complete).
        if ($hasFinal) {
            return 'complete';
        }

        // Mapping enum lama: gunakan "final" untuk tahap ke-2 (middle step).
        if ($hasStep2) {
            return 'final';
        }

        if ($hasStep1) {
            return 'initial';
        }

        return 'initial';
    }

    /**
     * Display the specified oil calculation with results.
     */
    public function show(OilCalculation $oilCalculation)
    {
        // Allow both 'view oil results' (PPIC) and 'view oil losses' (Sampel Boy, others)
        if (!Auth::user()->can('view oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk melihat data oil losses.');
        }

        $oilCalculation->load(['user']);

        return view('oil.show', ['oilLoss' => $oilCalculation]);
    }

    /**
     * Show the form for editing the specified oil record.
     */
    public function edit(OilCalculation $oilCalculation)
    {
        // Allow both 'edit oil results' (PPIC) and 'edit oil losses' (PPIC)
        if (!Auth::user()->can('edit oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk edit data oil losses.');
        }

        $kodeOptions = OilMasterData::getKodeDropdown(Auth::user()->office);
        $jenisOptions = OilMasterData::getJenisDropdown();
        $operatorOptions = $this->getOperatorOptionsByOffice($oilCalculation->office);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($oilCalculation->office);

        return view('oil.edit', [
            'oilLoss' => $oilCalculation,
            'kodeOptions' => $kodeOptions,
            'jenisOptions' => $jenisOptions,
            'operatorOptions' => $operatorOptions,
            'sampleBoyOptions' => $sampleBoyOptions,
        ]);
    }

    /**
     * Update the specified oil calculation (numeric data + related record).
     */
    public function update(Request $request, OilCalculation $oilCalculation)
    {
        // Allow both 'edit oil results' (PPIC) and 'edit oil losses' (PPIC)
        if (!Auth::user()->can('edit oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk edit data oil losses.');
        }

        $validated = $request->validate([
            // Mode 1: Non-numeric fields (optional)
            'kode' => 'nullable|string|exists:oil_master_data,kode',
            'jenis' => 'nullable|string',
            'operator' => $this->getOperatorValidationRules($oilCalculation->office),
            'sampel_boy' => $this->getSampleBoyValidationRules($oilCalculation->office),
            'tanggal_sampel' => 'required|date|before_or_equal:today',
            // Mode 2: Numeric fields
            'cawan_kosong' => 'required|numeric|min:0',
            'berat_basah' => 'required|numeric|min:0',
            'cawan_sample_kering' => 'required|numeric|min:0',
            'labu_kosong' => 'required|numeric|min:0',
            'oil_labu' => 'required|numeric|min:0',
        ], [
            'tanggal_sampel.before_or_equal' => 'Tanggal Sampel tidak boleh melebihi hari ini.',
            'sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
        ]);

        try {
            $originalKode = $oilCalculation->kode;
            $originalSampleDate = $oilCalculation->tanggal_sampel
                ? Carbon::parse($oilCalculation->tanggal_sampel)->toDateString()
                : $oilCalculation->created_at->toDateString();
            $newSampleDate = Carbon::parse($validated['tanggal_sampel'])->toDateString();

            // Get kode for calculation (use validated kode or existing kode)
            $kodeForCalculation = $validated['kode'] ?? $oilCalculation->kode;

            // Calculate derived values using OilService
            $result = $this->oilService->calculate([
                'cawan_kosong' => $validated['cawan_kosong'],
                'berat_basah' => $validated['berat_basah'],
                'cawan_sample_kering' => $validated['cawan_sample_kering'],
                'labu_kosong' => $validated['labu_kosong'],
                'oil_labu' => $validated['oil_labu'],
            ], $kodeForCalculation, $oilCalculation->office);

            // Update OilCalculation (numeric data)
            $oilCalculation->update([
                'kode' => $kodeForCalculation,
                'cawan_kosong' => $result['cawan_kosong'],
                'berat_basah' => $result['berat_basah'],
                'cawan_sample_kering' => $result['cawan_sample_kering'],
                'labu_kosong' => $result['labu_kosong'],
                'oil_labu' => $result['oil_labu'],
                'total_cawan_basah' => $result['total_cawan_basah'],
                'sampel_setelah_oven' => $result['sampel_setelah_oven'],
                'minyak' => $result['minyak'],
                'moist' => $result['moist'],
                'dmwm' => $result['dmwm'],
                'olwb' => $result['olwb'],
                'oldb' => $result['oldb'],
                'oil_losses' => $result['oil_losses'],
                'limitOLWB' => $result['limitOLWB'],
                'limitOLDB' => $result['limitOLDB'],
                'limitOL' => $result['limitOL'],
                'limit_operator' => $result['limit_operator'],
                'persen' => $result['persen'],
                'persen4' => $result['persen4'],
                'tanggal_sampel' => $newSampleDate,
            ]);

            // Update or create related OilRecord (non-numeric data) if provided
            if ($validated['kode'] && $validated['jenis']) {
                $relatedRecord = OilRecord::where('kode', $originalKode)
                    ->whereDate('tanggal_sampel', $originalSampleDate)
                    ->first();

                if ($relatedRecord) {
                    // Update existing record
                    $relatedRecord->update([
                        'kode' => $validated['kode'],
                        'jenis' => $validated['jenis'],
                        'operator' => $validated['operator'],
                        'sampel_boy' => $validated['sampel_boy'],
                        'tanggal_sampel' => $newSampleDate,
                    ]);
                } else {
                    // Create new record if doesn't exist
                    OilRecord::create([
                        'kode' => $validated['kode'],
                        'jenis' => $validated['jenis'],
                        'operator' => $validated['operator'],
                        'sampel_boy' => $validated['sampel_boy'],
                        'tanggal_sampel' => $newSampleDate,
                        'user_id' => Auth::id(),
                        'created_at' => $oilCalculation->created_at,
                    ]);
                }
            }

            // Recalculate daily bobot average for the calculation date
            collect([$originalSampleDate, $newSampleDate])
                ->filter()
                ->unique()
                ->each(fn($date) => $this->recalculateDailyBobotAverage($date));

            // Log activity
            $this->logActivity(
                'update',
                "Update data oil losses kode {$oilCalculation->kode}",
                $oilCalculation
            );

            $proofData = $this->buildSuccessProofData(
                $relatedRecord ?? null,
                $oilCalculation,
                'Data berhasil diupdate!'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil diupdate!',
                    'proof' => $proofData,
                ]);
            }

            return redirect()
                ->route('oil.index')
                ->with('success', 'Data berhasil diupdate!')
                ->with('success_proof', $proofData);

        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified oil calculation.
     */
    public function destroy(OilCalculation $oilCalculation)
    {
        // Allow both 'delete oil results' (PPIC) and 'delete oil losses' (PPIC)
        if (!Auth::user()->can('delete oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk hapus data oil losses.');
        }

        $calculationDate = $oilCalculation->tanggal_sampel
            ? Carbon::parse($oilCalculation->tanggal_sampel)->toDateString()
            : $oilCalculation->created_at->toDateString();
        $kode = $oilCalculation->kode;
        $oilCalculationId = $oilCalculation->id;

        // Log before delete
        $this->logDelete(
            $oilCalculation,
            "Hapus data oil losses kode {$kode} (ID: {$oilCalculationId})"
        );

        $oilCalculation->delete();

        // Recalculate daily bobot average after deletion
        $this->recalculateDailyBobotAverage($calculationDate);

        return redirect()
            ->route('oil.index')
            ->with('success', 'Data perhitungan berhasil dihapus!');
    }

    /**
     * Remove the specified oil record (soft delete for non-numeric data).
     */
    public function destroyRecord(OilRecord $oilRecord)
    {
        // Allow both 'delete oil results' (PPIC) and 'delete oil losses' (PPIC)
        if (!Auth::user()->can('delete oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk hapus data oil losses.');
        }

        $kode = $oilRecord->kode;
        $oilRecordId = $oilRecord->id;

        // Log before delete
        $this->logDelete(
            $oilRecord,
            "Hapus data oil record (non-angka) kode {$kode} (ID: {$oilRecordId})"
        );

        $oilRecord->delete(); // Soft delete karena model menggunakan SoftDeletes trait

        return redirect()
            ->route('oil.index')
            ->with('success', 'Data non-angka berhasil dihapus!');
    }

    /**
     * Show the form for editing a non-numeric lab record.
     */
    public function editRecord(OilRecord $oilRecord)
    {
        if (!Auth::user()->can('edit oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk edit data.');
        }

        // Get dropdown options
        $kodeOptions = OilMasterData::getKodeDropdown(Auth::user()->office);
        $jenisOptions = OilMasterData::getJenisDropdown();
        $operatorOptions = $this->getOperatorOptionsByOffice($oilRecord->office);
        $sampleBoyOptions = $this->getSampleBoyOptionsByOffice($oilRecord->office);

        return view('oil.edit-record', compact('oilRecord', 'kodeOptions', 'jenisOptions', 'operatorOptions', 'sampleBoyOptions'));
    }

    /**
     * Update a non-numeric lab record.
     */
    public function updateRecord(Request $request, OilRecord $oilRecord)
    {
        if (!Auth::user()->can('edit oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk edit data.');
        }

        $validated = $request->validate([
            'kode' => 'required|string|exists:oil_master_data,kode',
            'jenis' => 'required|string',
            'operator' => $this->getOperatorValidationRules($oilRecord->office, true),
            'sampel_boy' => $this->getSampleBoyValidationRules($oilRecord->office),
            'tanggal_sampel' => 'required|date|before_or_equal:today',
        ], [
            'kode.required' => 'Kode wajib diisi',
            'kode.exists' => 'Kode tidak valid atau tidak ditemukan di master data',
            'jenis.required' => 'Jenis wajib diisi',
            'operator.required' => 'Operator wajib diisi',
            'operator.in' => 'Operator tidak sesuai dengan daftar office.',
            'sampel_boy.required' => 'Sampel Boy wajib dipilih dari daftar Office YBS.',
            'sampel_boy.in' => 'Sampel Boy tidak sesuai daftar Office YBS.',
            'tanggal_sampel.before_or_equal' => 'Tanggal Sampel tidak boleh melebihi hari ini.',
        ]);

        try {
            // Update the record
            $originalSampleDate = $oilRecord->tanggal_sampel
                ? Carbon::parse($oilRecord->tanggal_sampel)->toDateString()
                : $oilRecord->created_at->toDateString();

            $oilRecord->update([
                'kode' => $validated['kode'],
                'jenis' => $validated['jenis'],
                'operator' => $validated['operator'],
                'sampel_boy' => $validated['sampel_boy'] ?? $oilRecord->sampel_boy,
                'tanggal_sampel' => $validated['tanggal_sampel'] ?? $originalSampleDate,
                // Don't update user_id - keep original creator
            ]);

            // Recalculate daily bobot average for the calculation date
            collect([$originalSampleDate, $validated['tanggal_sampel'] ?? $originalSampleDate])
                ->filter()
                ->unique()
                ->each(fn($date) => $this->recalculateDailyBobotAverage($date));

            // Log activity
            $this->logActivity(
                'update',
                "Update data jenis sampel kode {$oilRecord->kode}",
                $oilRecord
            );

            $proofData = $this->buildSuccessProofData(
                $oilRecord,
                null,
                'Data jenis sampel berhasil diupdate!'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data jenis sampel berhasil diupdate!',
                    'proof' => $proofData,
                ]);
            }

            return redirect()
                ->route('oil.index')
                ->with('success', 'Data jenis sampel berhasil diupdate!')
                ->with('success_proof', $proofData);

        } catch (Exception $e) {
            Log::error('Update OilRecord Error: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal update data: ' . $e->getMessage()], 500);
            }
            return back()
                ->withInput()
                ->with('error', 'Gagal update data: ' . $e->getMessage());
        }
    }

    /**
     * Export oil loss data.
     */
    public function export(Request $request)
    {
        $this->authorize('export oil data');

        // TODO: Implement export to Excel/PDF
        return back()->with('info', 'Fitur export sedang dalam pengembangan.');
    }

    public function report(Request $request)
    {
        // Allow both 'view oil samples' (PPIC) and 'view performance' (Asisten Lab, others)
        if (!Auth::user()->can('view performance oil losses')) {
            abort(403, 'Anda tidak memiliki akses untuk melihat performance.');
        }

        return view('oil.report');
    }
    /**
     * Display OLWB table view with dates as rows and kode as columns.
     */
    public function olwbIndex(Request $request)
    {
        // Allow both 'view oil samples' (PPIC) and 'view olwb' (Asisten Lab, others)
        if (!Auth::user()->can('view olwb')) {
            abort(403, 'Anda tidak memiliki akses untuk melihat data OLWB.');
        }

        // Default date range: awal bulan sampai hari ini
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Office filter: user dengan office hanya boleh lihat office-nya sendiri
        $userOffice = Auth::user()->office;
        $officeFilter = $userOffice ?: $request->input('office', 'YBS');
        // Get all kode with pivot from master data (ordered)
        $allKodesData = OilMasterData::where('is_active', true)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('kode')
            ->get()
            ->map(function ($masterData) {
                return [
                    'kode' => $masterData->kode,
                    'pivot' => $masterData->pivot ?: $masterData->kode,
                ];
            });

        // Get oil calculations data within date range
        $calculationsQuery = OilCalculation::where('status', 'complete')
            ->whereBetween('tanggal_sampel', [$startDate, $endDate]);

        // Apply office filter jika bukan 'all'
        if ($officeFilter !== 'all') {
            $calculationsQuery->where('office', $officeFilter);
        }

        $calculations = $calculationsQuery->orderBy('tanggal_sampel', 'asc')->orderBy('created_at', 'asc')->get();

        // Group data by date and kode
        $dataByDate = [];
        foreach ($calculations as $calc) {
            $date = $calc->tanggal_sampel
                ? Carbon::parse($calc->tanggal_sampel)->toDateString()
                : $this->resolveProductionDateKey($calc->created_at);
            $kode = $calc->kode;

            if (!isset($dataByDate[$date])) {
                $dataByDate[$date] = [];
            }

            // Store OLWB value for this date and kode
            $dataByDate[$date][$kode] = [
                'olwb' => $calc->olwb,
                'limitOLWB' => $calc->limitOLWB,
                'limit_operator' => $calc->limit_operator,
                'id' => $calc->id,
            ];
        }

        // Sort dates
        ksort($dataByDate);

        return view('oil.olwb', compact('allKodesData', 'dataByDate', 'startDate', 'endDate', 'officeFilter'));
    }

    /**
     * Export OLWB data to Excel
     */
    public function exportOlwb(Request $request)
    {
        // Allow both 'view oil samples' (PPIC) and 'view olwb' (Asisten Lab, others)
        if (!Auth::user()->can('view olwb') && !Auth::user()->can('export olwb reports')) {
            abort(403, 'Anda tidak memiliki akses untuk export data OLWB.');
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Office filter: user dengan office hanya boleh lihat office-nya sendiri
        $userOffice = Auth::user()->office;
        $officeFilter = $userOffice ?: $request->input('office', 'YBS');
        // Get all kode with pivot from master data (ordered)
        $allKodesData = OilMasterData::where('is_active', true)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->orderBy('kode')
            ->get()
            ->map(function ($masterData) {
                return [
                    'kode' => $masterData->kode,
                    'pivot' => $masterData->pivot ?: $masterData->kode,
                ];
            });

        // Get oil calculations data within date range
        $calculationsQuery = OilCalculation::where('status', 'complete')
            ->whereBetween('tanggal_sampel', [$startDate, $endDate]);

        // Apply office filter jika bukan 'all'
        if ($officeFilter !== 'all') {
            $calculationsQuery->where('office', $officeFilter);
        }

        $calculations = $calculationsQuery->orderBy('tanggal_sampel', 'asc')->orderBy('created_at', 'asc')->get();

        // Group data by date and kode
        $dataByDate = [];
        foreach ($calculations as $calc) {
            $date = $calc->tanggal_sampel
                ? Carbon::parse($calc->tanggal_sampel)->toDateString()
                : $this->resolveProductionDateKey($calc->created_at);
            $kode = $calc->kode;

            if (!isset($dataByDate[$date])) {
                $dataByDate[$date] = [];
            }

            $dataByDate[$date][$kode] = [
                'olwb' => $calc->olwb,
                'limitOLWB' => $calc->limitOLWB,
                'limit_operator' => $calc->limit_operator,
                'id' => $calc->id,
            ];
        }

        ksort($dataByDate);

        // Generate filename with date range
        $startFormatted = Carbon::parse($startDate)->format('Ymd');
        $endFormatted = Carbon::parse($endDate)->format('Ymd');
        $filename = "OLWB_{$startFormatted}_{$endFormatted}.xlsx";

        return Excel::download(new OlwbExport($dataByDate, $allKodesData, $startDate, $endDate), $filename);
    }

    /**
     * Display bobot report with performance calculations
     */
    public function reportIndex(Request $request)
    {
        // Default filter date: hari ini untuk start dan end
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Office filter: user dengan office hanya boleh lihat office-nya sendiri
        $userOffice = Auth::user()->office;
        $officeFilter = $userOffice ?: $request->input('office', 'YBS');
        // Get all bobot configs
        $bobotConfigs = BobotConfig::all()->keyBy('jenis');

        // Get ALL kodes from OilMasterData that have jenis mapping to bobot configs
        // Return with pivot info for header display
        $allKodesData = OilMasterData::where('is_active', true)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->filter(function ($masterData) use ($bobotConfigs) {
                // Check if jenis can be mapped to bobot config
                $jenisConfig = $this->mapJenisToConfig($masterData->jenis, $bobotConfigs);
                return $jenisConfig !== null;
            })
            ->sortBy('kode')
            ->map(function ($masterData) {
                return [
                    'kode' => $masterData->kode,
                    'pivot' => $masterData->pivot ?: $masterData->kode,
                    'jenis' => $masterData->jenis,
                ];
            })
            ->values();

        // Get all dates in range that have calculations
        $datesQuery = OilCalculation::selectRaw('DATE(tanggal_sampel) as date')
            ->where('status', 'complete')
            ->whereBetween('tanggal_sampel', [$startDate, $endDate]);

        // Apply office filter jika bukan 'all'
        if ($officeFilter !== 'all') {
            $datesQuery->where('office', $officeFilter);
        }

        $dates = $datesQuery->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date');

        // OPTIMIZATION: Load ALL calculations in ONE query instead of query per date
        $allCalculationsQuery = OilCalculation::selectRaw('*, DATE(tanggal_sampel) as calc_date')
            ->where('status', 'complete')
            ->whereBetween('tanggal_sampel', [$startDate, $endDate]);

        if ($officeFilter !== 'all') {
            $allCalculationsQuery->where('office', $officeFilter);
        }

        // Group calculations by date in memory (no repeated queries)
        $calculationsByDate = $allCalculationsQuery->get()
            ->groupBy('calc_date')
            ->map(fn($calcs) => $calcs->keyBy('kode'));

        $reportData = [];

        foreach ($dates as $date) {
            // Get calculations from cached data (no query)
            $calculations = $calculationsByDate->get($date, collect());

            $dateData = [
                'date' => $date,
                'kodes' => [],
            ];

            $pressScores = [];
            $clarificationScores = [];

            // Process each kode
            foreach ($allKodesData as $kodeInfo) {
                $kode = $kodeInfo['kode'];
                $jenis = $kodeInfo['jenis'];

                if (isset($calculations[$kode]) && $calculations[$kode]->olwb !== null) {
                    $olwbValue = $calculations[$kode]->olwb;

                    // Map jenis from master data to bobot config
                    $jenisConfig = $this->mapJenisToConfig($jenis, $bobotConfigs);

                    if ($jenisConfig) {
                        $bobotScore = $this->calculateBobot($olwbValue, $jenisConfig);
                    } else {
                        $bobotScore = null;
                    }

                    $dateData['kodes'][$kode] = [
                        'olwb' => $olwbValue,
                        'bobot' => $bobotScore,
                    ];

                    // Categorize by Press or Clarification
                    if ($bobotScore !== null) {
                        $jenisUpper = strtoupper($jenis);
                        if (str_contains($jenisUpper, 'PRESS')) {
                            $pressScores[] = $bobotScore;
                        } else {
                            $clarificationScores[] = $bobotScore;
                        }
                    }
                } else {
                    $dateData['kodes'][$kode] = [
                        'olwb' => null,
                        'bobot' => null,
                    ];
                }
            }

            // Calculate separate averages for Press and Clarification
            $averagePress = !empty($pressScores) ? round(array_sum($pressScores) / count($pressScores), 2) : null;
            $averageClarification = !empty($clarificationScores) ? round(array_sum($clarificationScores) / count($clarificationScores), 2) : null;

            $dateData['average_press'] = $averagePress;
            $dateData['average_clarification'] = $averageClarification;

            // Store/update daily average in database (use average of both if available)
            $overallForDb = null;
            if ($averagePress !== null && $averageClarification !== null) {
                $overallForDb = round(($averagePress + $averageClarification) / 2, 2);
            } elseif ($averagePress !== null) {
                $overallForDb = $averagePress;
            } elseif ($averageClarification !== null) {
                $overallForDb = $averageClarification;
            }

            if ($overallForDb !== null) {
                DailyBobotAverage::updateOrCreate(
                    ['date' => $date],
                    ['average_score' => $overallForDb]
                );
            }

            $reportData[] = $dateData;
        }

        // Get operator data for the same date range with optimized join
        // Use LEFT JOIN and select only needed columns for better performance
        $operatorRecordsQuery = OilRecord::select('oil_records.id', 'oil_records.kode', 'oil_records.operator', 'oil_records.created_at', 'oil_records.tanggal_sampel', 'oil_master_data.jenis')
            ->leftJoin('oil_master_data', function ($join) {
                $join->on('oil_records.kode', '=', 'oil_master_data.kode')
                    ->on('oil_records.office', '=', 'oil_master_data.office');
            })
            ->whereBetween('oil_records.tanggal_sampel', [$startDate, $endDate])
            ->whereNotNull('oil_records.operator')
            ->where('oil_records.operator', '!=', '');

        // Apply office filter jika bukan 'all'
        if ($officeFilter !== 'all') {
            $operatorRecordsQuery->where('oil_records.office', $officeFilter);
        }

        $operatorRecords = $operatorRecordsQuery->orderBy('oil_records.tanggal_sampel', 'desc')->orderBy('oil_records.created_at', 'desc')->get();

        // Group operators by Press and Clarification
        $operatorPress = $operatorRecords->filter(function ($record) {
            return str_contains(strtoupper($record->jenis), 'PRESS');
        })->unique('operator')->pluck('operator')->sort()->values();

        $operatorClarification = $operatorRecords->filter(function ($record) {
            return !str_contains(strtoupper($record->jenis), 'PRESS');
        })->unique('operator')->pluck('operator')->sort()->values();

        // Get recent activities for each operator
        $operatorActivities = $operatorRecords->groupBy('operator')->map(function ($records) {
            return [
                'total_input' => $records->count(),
                'last_input' => $records->first()->created_at,
                'jenis_types' => $records->pluck('jenis')->unique()->sort()->values(),
            ];
        });

        // Prepare dates and daily performance for operator table
        $reportDates = collect($reportData)->pluck('date');
        $dailyPerformance = collect($reportData)->keyBy('date')->map(function ($item) {
            return [
                'average_press' => $item['average_press'],
                'average_clarification' => $item['average_clarification'],
            ];
        });

        return view('oil.report', compact(
            'reportData',
            'allKodesData',
            'startDate',
            'endDate',
            'officeFilter',
            'operatorPress',
            'operatorClarification',
            'operatorActivities',
            'reportDates',
            'dailyPerformance'
        ));
    }

    /**
     * Export Performance report to Excel
     */
    public function exportPerformance(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Office filter: user dengan office hanya boleh lihat office-nya sendiri
        $userOffice = Auth::user()->office;
        $officeFilter = $userOffice ?: $request->input('office', 'YBS');
        // Get all bobot configs
        $bobotConfigs = BobotConfig::all()->keyBy('jenis');

        // Get ALL kodes from OilMasterData that have jenis mapping to bobot configs
        $allKodesData = OilMasterData::where('is_active', true)
            ->when($officeFilter !== 'all', fn($q) => $q->where('office', $officeFilter))
            ->get()
            ->filter(function ($masterData) use ($bobotConfigs) {
                $jenisConfig = $this->mapJenisToConfig($masterData->jenis, $bobotConfigs);
                return $jenisConfig !== null;
            })
            ->sortBy('kode')
            ->map(function ($masterData) {
                return [
                    'kode' => $masterData->kode,
                    'pivot' => $masterData->pivot ?: $masterData->kode,
                    'jenis' => $masterData->jenis,
                ];
            })
            ->values();

        // Get all dates in range that have calculations
        $datesQuery = OilCalculation::selectRaw('DATE(tanggal_sampel) as date')
            ->where('status', 'complete')
            ->whereBetween('tanggal_sampel', [$startDate, $endDate]);

        // Apply office filter jika bukan 'all'
        if ($officeFilter !== 'all') {
            $datesQuery->where('office', $officeFilter);
        }

        $dates = $datesQuery->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date');

        // OPTIMIZATION: Load ALL calculations in ONE query instead of query per date
        $allCalculationsForExport = OilCalculation::selectRaw('*, DATE(tanggal_sampel) as calc_date')
            ->where('status', 'complete')
            ->whereBetween('tanggal_sampel', [$startDate, $endDate]);

        if ($officeFilter !== 'all') {
            $allCalculationsForExport->where('office', $officeFilter);
        }

        // Group calculations by date in memory (no repeated queries)
        $calculationsByDateForExport = $allCalculationsForExport->get()
            ->groupBy('calc_date')
            ->map(fn($calcs) => $calcs->keyBy('kode'));

        $reportData = [];

        foreach ($dates as $date) {
            // Get calculations from cached data (no query)
            $calculations = $calculationsByDateForExport->get($date, collect());

            $dateData = [
                'date' => $date,
                'kodes' => [],
            ];

            $pressScores = [];
            $clarificationScores = [];

            foreach ($allKodesData as $kodeInfo) {
                $kode = $kodeInfo['kode'];
                $jenis = $kodeInfo['jenis'];

                if (isset($calculations[$kode]) && $calculations[$kode]->olwb !== null) {
                    $olwbValue = $calculations[$kode]->olwb;
                    $jenisConfig = $this->mapJenisToConfig($jenis, $bobotConfigs);

                    if ($jenisConfig) {
                        $bobotScore = $this->calculateBobot($olwbValue, $jenisConfig);
                    } else {
                        $bobotScore = null;
                    }

                    $dateData['kodes'][$kode] = [
                        'olwb' => $olwbValue,
                        'bobot' => $bobotScore,
                    ];

                    if ($bobotScore !== null) {
                        $jenisUpper = strtoupper($jenis);
                        if (str_contains($jenisUpper, 'PRESS')) {
                            $pressScores[] = $bobotScore;
                        } else {
                            $clarificationScores[] = $bobotScore;
                        }
                    }
                } else {
                    $dateData['kodes'][$kode] = [
                        'olwb' => null,
                        'bobot' => null,
                    ];
                }
            }

            $averagePress = !empty($pressScores) ? round(array_sum($pressScores) / count($pressScores), 2) : null;
            $averageClarification = !empty($clarificationScores) ? round(array_sum($clarificationScores) / count($clarificationScores), 2) : null;

            $dateData['average_press'] = $averagePress;
            $dateData['average_clarification'] = $averageClarification;

            $reportData[] = $dateData;
        }

        // Get operator data
        $operatorRecords = OilRecord::select('oil_records.*', 'oil_master_data.jenis')
            ->join('oil_master_data', 'oil_records.kode', '=', 'oil_master_data.kode')
            ->whereBetween('oil_records.tanggal_sampel', [$startDate, $endDate])
            ->whereNotNull('oil_records.operator')
            ->where('oil_records.operator', '!=', '')
            ->when($officeFilter !== 'all', fn($q) => $q->where('oil_records.office', $officeFilter))
            ->orderBy('oil_records.tanggal_sampel', 'desc')
            ->orderBy('oil_records.created_at', 'desc')
            ->get();

        $operatorPress = $operatorRecords->filter(function ($record) {
            return str_contains(strtoupper($record->jenis), 'PRESS');
        })->unique('operator')->pluck('operator')->sort()->values();

        $operatorClarification = $operatorRecords->filter(function ($record) {
            return !str_contains(strtoupper($record->jenis), 'PRESS');
        })->unique('operator')->pluck('operator')->sort()->values();

        $reportDates = collect($reportData)->pluck('date');
        $dailyPerformance = collect($reportData)->keyBy('date')->map(function ($item) {
            return [
                'average_press' => $item['average_press'],
                'average_clarification' => $item['average_clarification'],
            ];
        });

        // Generate filename
        $startFormatted = Carbon::parse($startDate)->format('Ymd');
        $endFormatted = Carbon::parse($endDate)->format('Ymd');
        $filename = "Performance_{$startFormatted}_{$endFormatted}.xlsx";

        return Excel::download(
            new PerformanceExport(
                $reportData,
                $allKodesData,
                $operatorPress,
                $operatorClarification,
                $reportDates,
                $dailyPerformance,
                $startDate,
                $endDate
            ),
            $filename
        );
    }

    /**
     * Map jenis from OilMasterData to BobotConfig
     * Uses case-insensitive matching with name variations
     */
    private function mapJenisToConfig($jenis, $bobotConfigs)
    {
        $jenisUpper = strtoupper(trim($jenis));

        // Direct mapping with case-insensitive comparison
        foreach ($bobotConfigs as $configJenis => $config) {
            $configJenisUpper = strtoupper($configJenis);

            // Exact match
            if ($jenisUpper === $configJenisUpper) {
                return $config;
            }

            // Pattern matching for variations
            if ($jenisUpper === 'FEED DECANTER' && str_contains($configJenisUpper, 'FEED')) {
                return $config;
            }
            if ($jenisUpper === 'BUNCH PRESS' && str_contains($configJenisUpper, 'BUNCH PRESS')) {
                return $config;
            }
            if ($jenisUpper === 'PRESS' && $configJenisUpper === 'PRESS') {
                return $config;
            }
            if ($jenisUpper === 'EFFLUENT' && $configJenisUpper === 'EFFLUENT') {
                return $config;
            }
        }

        return null;
    }

    /**
     * Calculate bobot score based on OLWB value and limits
     */
    private function calculateBobot($olwbValue, $config)
    {
        // Check from highest bobot to lowest
        if ($olwbValue <= $config->limit_100) {
            return 100;
        } elseif ($olwbValue <= $config->limit_90) {
            return 90;
        } elseif ($olwbValue <= $config->limit_80) {
            return 80;
        } elseif ($olwbValue <= $config->limit_70) {
            return 70;
        } elseif ($olwbValue <= $config->limit_60) {
            return 60;
        } elseif ($olwbValue <= $config->limit_50) {
            return 50;
        } else {
            return 0; // Below all thresholds
        }
    }

    /**
     * Recalculate and update daily bobot average for a specific date
     */
    private function recalculateDailyBobotAverage($date)
    {
        // Get all bobot configs
        $bobotConfigs = BobotConfig::all()->keyBy('jenis');

        // Get all calculations for this date with master data eager loaded to prevent N+1
        $calculations = OilCalculation::where('status', 'complete')->whereDate('tanggal_sampel', $date)
            ->with(['masterData:id,kode,jenis'])
            ->get();

        if ($calculations->isEmpty()) {
            // No data for this date, delete the average record if exists
            DailyBobotAverage::where('date', $date)->delete();
            return;
        }

        // Pre-load all master data in one query to avoid N+1
        $kodes = $calculations->pluck('kode')->unique();
        $offices = $calculations->pluck('office')
            ->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = OilMasterData::query()
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get();

        $masterDataCache = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        $bobotScores = [];

        foreach ($calculations as $calc) {
            if ($calc->olwb === null)
                continue;

            // Get master data from cache (no query)
            $officeCode = strtoupper(trim((string) ($calc->office ?? '')));
            $masterData = $masterDataCache->get($officeCode . '|' . $calc->kode)
                ?? $masterFallbackByKode->get($calc->kode);
            if (!$masterData)
                continue;

            // Map jenis to bobot config
            $jenisConfig = $this->mapJenisToConfig($masterData->jenis, $bobotConfigs);
            if (!$jenisConfig)
                continue;

            // Calculate bobot score
            $bobotScore = $this->calculateBobot($calc->olwb, $jenisConfig);

            // Include ALL bobot scores including 0 (0 means poor performance, still valid)
            $bobotScores[] = $bobotScore;
        }

        // Calculate and store average
        if (!empty($bobotScores)) {
            $averageBobot = round(array_sum($bobotScores) / count($bobotScores), 2);
            DailyBobotAverage::updateOrCreate(
                ['date' => $date],
                ['average_score' => $averageBobot]
            );
        } else {
            // No valid bobot scores, delete the average record if exists
            DailyBobotAverage::where('date', $date)->delete();
        }
    }

    private function resolveProductionRange(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveProductionDateKey(Carbon $timestamp): string
    {
        return $timestamp->copy()->toDateString();
    }

    /**
     * Build flash payload for proof modal after create/update.
     */
    private function buildSuccessProofData(
        ?OilRecord $mode1Record,
        ?OilCalculation $mode2Calculation,
        string $message,
        array $mode1Records = [],
        array $mode2Calculations = []
    ): array {
        if (empty($mode1Records) && $mode1Record) {
            $mode1Records = [$mode1Record];
        }
        if (empty($mode2Calculations) && $mode2Calculation) {
            $mode2Calculations = [$mode2Calculation];
        }

        $kodes = collect([
            $mode1Record?->kode,
            $mode2Calculation?->kode,
            ...collect($mode1Records)->pluck('kode')->all(),
            ...collect($mode2Calculations)->pluck('kode')->all(),
        ])->filter()->unique()->values();

        $offices = collect([
            $mode1Record?->office,
            $mode2Calculation?->office,
            ...collect($mode1Records)->pluck('office')->all(),
            ...collect($mode2Calculations)->pluck('office')->all(),
        ])->filter()
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->unique()
            ->values();

        $masterRows = OilMasterData::query()
            ->whereIn('kode', $kodes)
            ->when($offices->isNotEmpty(), fn($q) => $q->whereIn('office', $offices))
            ->get(['office', 'kode', 'pivot']);

        $masterDataByOfficeKode = $masterRows->keyBy(
            fn($row) => strtoupper(trim((string) $row->office)) . '|' . (string) $row->kode
        );

        $masterDataFallbackByKode = $masterRows->groupBy('kode')
            ->map(fn($group) => $group->first());

        $mode2Calculation?->loadMissing(['initialUser', 'finalUser', 'user']);
        collect($mode2Calculations)->each(function ($calculation) {
            if ($calculation instanceof OilCalculation) {
                $calculation->loadMissing(['initialUser', 'finalUser', 'user']);
            }
        });

        $mode1Entries = collect($mode1Records)
            ->filter(fn($record) => $record instanceof OilRecord)
            ->map(fn($record) => $this->formatMode1ProofData($record, $masterDataByOfficeKode, $masterDataFallbackByKode))
            ->values()
            ->all();

        $mode2Entries = collect($mode2Calculations)
            ->filter(fn($calc) => $calc instanceof OilCalculation)
            ->map(fn($calc) => $this->formatMode2ProofData($calc, $masterDataByOfficeKode, $masterDataFallbackByKode))
            ->values()
            ->all();

        return [
            'message' => $message,
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'active_tab' => $mode2Calculation && !$mode1Record ? 'calculations' : 'records',
            'mode1' => $mode1Record ? $this->formatMode1ProofData($mode1Record, $masterDataByOfficeKode, $masterDataFallbackByKode) : null,
            'mode2' => $mode2Calculation ? $this->formatMode2ProofData($mode2Calculation, $masterDataByOfficeKode, $masterDataFallbackByKode) : null,
            'mode1_entries' => $mode1Entries,
            'mode2_entries' => $mode2Entries,
        ];
    }

    /**
     * Format non-numeric record data for success proof modal.
     */
    private function formatMode1ProofData(OilRecord $record, $masterDataByOfficeKode, $masterDataFallbackByKode): array
    {
        $officeCode = strtoupper(trim((string) ($record->office ?? '')));
        $masterData = $masterDataByOfficeKode->get($officeCode . '|' . $record->kode)
            ?? $masterDataFallbackByKode->get($record->kode);

        return [
            'tanggal_input' => $record->created_at->format('d/m/Y H:i:s'),
            'tanggal_sampel' => $record->tanggal_sampel ? Carbon::parse($record->tanggal_sampel)->format('d/m/Y') : '-',
            'kode_label' => OilMasterData::getKodeDisplay($record->kode, $record->pivot ?: $masterData?->pivot, $record->office),
            'jenis' => $record->jenis,
            'operator' => $record->operator,
            'sampel_boy' => $record->sampel_boy,
            'input_by' => $record->user?->name,
            'office' => $record->office,
        ];
    }

    /**
     * Format numeric calculation data for success proof modal.
     */
    private function formatMode2ProofData(OilCalculation $calculation, $masterDataByOfficeKode, $masterDataFallbackByKode): array
    {
        $officeCode = strtoupper(trim((string) ($calculation->office ?? '')));
        $masterData = $masterDataByOfficeKode->get($officeCode . '|' . $calculation->kode)
            ?? $masterDataFallbackByKode->get($calculation->kode);

        return [
            'phase' => $calculation->phase,
            'status' => $calculation->status,
            'tanggal_input' => $calculation->created_at->format('d/m/Y H:i:s'),
            'tanggal_sampel' => $calculation->tanggal_sampel ? Carbon::parse($calculation->tanggal_sampel)->format('d/m/Y') : '-',
            'kode_label' => OilMasterData::getKodeDisplay($calculation->kode, $masterData?->pivot, $calculation->office),
            'kode' => $calculation->kode,
            'cawan_kosong' => $calculation->cawan_kosong,
            'berat_basah' => $calculation->berat_basah,
            'cawan_sample_kering' => $calculation->cawan_sample_kering,
            'labu_kosong' => $calculation->labu_kosong,
            'oil_labu' => $calculation->oil_labu,
            'moist' => $calculation->moist,
            'dmwm' => $calculation->dmwm,
            'olwb' => $calculation->olwb,
            'oldb' => $calculation->oldb,
            'oil_losses' => $calculation->oil_losses,
            'limitOLWB' => $calculation->limitOLWB,
            'limitOLDB' => $calculation->limitOLDB,
            'limitOL' => $calculation->limitOL,
            'input_by' => $calculation->user?->name,
            'initial_input_by' => $calculation->initialUser?->name,
            'final_input_by' => $calculation->finalUser?->name,
            'office' => $calculation->office,
        ];
    }

    /**
     * Get operator options by office.
     */
    private function getOperatorOptionsByOffice(?string $office): array
    {
        if (!$office) {
            return [];
        }

        $operatorOptions = config('operator-options.oil.' . $office, []);
        $sampleBoyNames = $this->getSampleBoyUserNamesByOffice($office, 'Sampel Boy Oil Losses');

        return array_values(array_filter($operatorOptions, function (string $name) use ($sampleBoyNames): bool {
            return !in_array(strtolower(trim($name)), $sampleBoyNames, true);
        }));
    }

    private function getSampleBoyOptionsByOffice(?string $office): array
    {
        if (!$office) {
            return [];
        }

        $officeCode = strtoupper(trim((string) $office));

        return array_values(array_unique(array_merge(
            config('operator-options.sample_boy.' . $officeCode, []),
            User::role('Sampel Boy Oil Losses')
                ->where('office', $officeCode)
                ->pluck('name')
                ->all()
        )));
    }

    private function getSampleBoyUserNamesByOffice(string $office, string $role): array
    {
        return User::role($role)
            ->where('office', strtoupper(trim($office)))
            ->pluck('name')
            ->map(fn (string $name): string => strtolower(trim($name)))
            ->all();
    }

    /**
     * Build validation rules for operator by office.
     */
    private function getOperatorValidationRules(?string $office, bool $required = false): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:255'];
        $operatorOptions = $this->getOperatorOptionsByOffice($office);

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
}


