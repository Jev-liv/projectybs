<?php

namespace App\Http\Controllers;

use App\Exports\PerformanceSampelBoyExport;
use App\Exports\ProcessAnalysisExport;
use App\Models\AnalisaUsb;
use App\Models\FfaMoisture;
use App\Models\KernelCalculation;
use App\Models\KernelDirtMoistCalculation;
use App\Models\KernelDestoner;
use App\Models\KernelMasterData;
use App\Models\KernelMesin;
use App\Models\KernelProsses;
use App\Models\KernelQwt;
use App\Models\KernelRippleMill;
use App\Models\OilFoss;
use App\Models\SpintestMesin;
use App\Models\SpintestProsses;
use App\Models\SpintestCot;
use App\Models\SpintestCst;
use App\Models\SpintestFeedDecanter;
use App\Models\SpintestLightPhase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ProcessController extends Controller
{
    private array $timedCodeRowsCache = [];
    private array $spintestAnalysisRowsCache = [];

    private const TEAM_OPTIONS = ['Tim 1', 'Tim 2'];
    private const CST_MACHINES_YBS = ['CST1', 'CST2'];
    private const CST_MACHINES_SUN = ['CST1', 'CST2'];
    private const DECANTER_MACHINES_YBS = ['Alfa Laval 1', 'Alfa Laval 2', 'GEA', 'Flottweg'];
    private const DECANTER_MACHINES_SUN = ['GEA1', 'GEA2'];
    private const LIGHT_PHASE_MACHINES_YBS = ['Alfa Laval 1', 'Alfa Laval 2', 'GEA', 'Flottweg'];
    private const LIGHT_PHASE_MACHINES_SUN = ['GEA1', 'GEA2'];
    private const USB_ROW_NUMBERS_YBS = [1, 2, 3, 4, 5, 6, 7, 8];
    private const USB_ROW_NUMBERS_SUN = [1, 2, 3, 4];
    private const SPINTEST_SAMPLING_INTERVAL_MINUTES = 120;

    // Hardcoded master mesin per PT.
    // Silakan ubah daftar di konstanta ini sesuai kebutuhan masing-masing PT.
    private const MACHINE_GROUPS_YBS = [
        'FIBRE CYCLONE' => [
            'FIBRE CYCLONE 1',
            'FIBRE CYCLONE 2',
        ],
        'LTDS' => [
            'LTDS 1',
            'LTDS 2',
            'LTDS 3',
            'LTDS 4',
        ],
        'CLAYBATH WET SHELL' => [
            'CLAYBATH WET SHELL 1',
            'CLAYBATH WET SHELL 2',
            'CLAYBATH WET SHELL 3',
        ],
        'INLET KERNEL SILO' => [
            'INLET KERNEL SILO 1',
            'INLET KERNEL SILO 2',
            'INLET KERNEL SILO 3',
            'INLET KERNEL SILO 4',
            'INLET KERNEL SILO 5',
        ],
        'OUTLET KERNEL SILO TO BUNKER' => [
            'OUTLET KERNEL SILO 1 TO BUNKER',
            'OUTLET KERNEL SILO 2 TO BUNKER',
            'OUTLET KERNEL SILO 3 TO BUNKER',
            'OUTLET KERNEL SILO 4 TO BUNKER',
            'OUTLET KERNEL SILO 5 TO BUNKER',
        ],
        'PRESS' => [
            'PRESS 1',
            'PRESS 2',
            'PRESS 3',
            'PRESS 4',
            'PRESS 5',
            'PRESS 6',
            'PRESS 7',
            'PRESS 8',
            'PRESS 9',
        ],
        'RIPPLE MILL' => [
            'RIPPLE MILL NO. 1',
            'RIPPLE MILL NO. 2',
            'RIPPLE MILL NO. 3',
            'RIPPLE MILL NO. 4',
            'RIPPLE MILL NO. 5',
            'RIPPLE MILL NO. 6',
            'RIPPLE MILL NO. 7',
        ],
        'DESTONER' => [
            'DESTONER 1',
            'DESTONER 2',
        ],
        'ANALISA MOISTURE & SPINTEST' => [
            'FFA & MOISTURE',
            'SPINTEST COT',
            'CST 1',
            'CST 2',
            'FEED DECANTER ALFA LAVAL 1',
            'FEED DECANTER ALFA LAVAL 2',
            'FEED DECANTER GEA',
            'FEED DECANTER FLOTTWEG',
            'LIGHT PHASE ALFA LAVAL 1',
            'LIGHT PHASE ALFA LAVAL 2',
            'LIGHT PHASE GEA',
            'LIGHT PHASE FLOTTWEG',
        ],
        'LAP JANGKOS' => [
            'STERILIZER 1',
            'STERILIZER 2',
            'STERILIZER 3',
            'STERILIZER 4',
            'STERILIZER 5',
            'STERILIZER 6',
            'STERILIZER 7',
            'STERILIZER 8',
        ],
        'OIL LOSS FOSS' => [
            'COT IN',
            'COT 2',
            'CST',
            'FD1',
            'FD2',
            'FD3',
            'FD4',
            'HP1',
            'HP2',
            'HP3',
            'HP4',
            'SD1',
            'SD2',
            'SD3',
            'SD4',
            'HPL1',
            'HPL2',
            'HPL3',
            'FE',
            'FBP1',
            'FBP2',
            'FBP3',
            'FBP4',
            'FBP5',
            'FP1',
            'FP2',
            'FP3',
            'FP4',
            'FP5',
            'FP6',
            'FP7',
            'FP8',
            'FP9',
        ],
    ];

    // Ubah jika daftar SUN berbeda dengan YBS.
    private const MACHINE_GROUPS_SUN = [
        'FIBRE CYCLONE' => [
            'FIBRE CYCLONE 1',
            'FIBRE CYCLONE 2',
        ],
        'LTDS' => [
            'LTDS 1',
            'LTDS 2',
        ],
        'CLAYBATH WET SHELL' => [
            'CLAYBATH WET SHELL 1',
        ],
        'INLET KERNEL SILO' => [
            'INLET KERNEL SILO 1',
            'INLET KERNEL SILO 2',
        ],
        'OUTLET KERNEL SILO TO BUNKER' => [
            'OUTLET KERNEL SILO 1 TO BUNKER',
            'OUTLET KERNEL SILO 2 TO BUNKER',
            'OUTLET KERNEL SILO 3 TO BUNKER',
            'OUTLET KERNEL SILO 4 TO BUNKER',
        ],
        'PRESS' => [
            'PRESS 1',
            'PRESS 2',
            'PRESS 3',
            'PRESS 4',
        ],
        'RIPPLE MILL' => [
            'RIPPLE MILL NO. 1',
            'RIPPLE MILL NO. 2',
            'RIPPLE MILL NO. 3',
        ],
        'DESTONER' => [
            'DESTONER 1',
            'DESTONER 2',
        ],
        'ANALISA MOISTURE & SPINTEST' => [
            'FFA & MOISTURE',
            'SPINTEST COT',
            'UNDERFLOW CST 1',
            'UNDERFLOW CST 2',
            'FEED DECANTER GEA 1',
            'FEED DECANTER GEA 2',
            'LIGHT PHASE GEA 1',
            'LIGHT PHASE GEA 2',
        ],
        'LAP JANGKOS' => [
            'STERILIZER 1',
            'STERILIZER 2',
            'STERILIZER 3',
            'STERILIZER 4',
        ],
    ];

    // Ubah jika daftar SJN berbeda dengan YBS.
    private const MACHINE_GROUPS_SJN = self::MACHINE_GROUPS_YBS;

    // YBS: interval sampling per kelompok mesin (tetap harus berturut-turut)
    private const PERFORMANCE_GROUP_INTERVAL_MINUTES = [
        'fibre_cyclone' => 120,
        'ltds' => 120,
        'claybath_wet_shell' => 60,
        'inlet_kernel_silo' => 60,
        'outlet_kernel_silo' => 60,
        'press' => 240,
        'eficiency' => 120,
        'destoner' => 240,
    ];

    // SUN office: all sampling interval 2 hours
    private const PERFORMANCE_GROUP_INTERVAL_MINUTES_SUN = [
        'fibre_cyclone' => 120,
        'ltds' => 120,
        'claybath_wet_shell' => 120,
        'inlet_kernel_silo' => 120,
        'outlet_kernel_silo' => 120,
        'press' => 240,
        'eficiency' => 120,
        'destoner' => 120,
    ];

    private const PERFORMANCE_CODE_GROUPS = [
        'fibre_cyclone' => ['FC1', 'FC2'],
        'ltds' => ['L1', 'L2', 'L3', 'L4'],
        'claybath_wet_shell' => ['CWS', 'CWS1', 'CWS2', 'CWS3'],
        'inlet_kernel_silo' => ['IN1', 'IN2', 'IN3', 'IN4', 'IN5'],
        'outlet_kernel_silo' => ['OUT1', 'OUT2', 'OUT3', 'OUT4', 'OUT5'],
        'press' => ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9'],
        'eficiency' => ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7'],
        'destoner' => ['D1', 'D2'],
    ];

    private const PERFORMANCE_DETAIL_CODES = [
        ['code' => 'FC1', 'label' => 'FC1', 'aliases' => ['FC1']],
        ['code' => 'FC2', 'label' => 'FC2', 'aliases' => ['FC2']],
        ['code' => 'L1', 'label' => 'LTDS1', 'aliases' => ['L1']],
        ['code' => 'L2', 'label' => 'LTDS2', 'aliases' => ['L2']],
        ['code' => 'L3', 'label' => 'LTDS3', 'aliases' => ['L3']],
        ['code' => 'L4', 'label' => 'LTDS4', 'aliases' => ['L4']],
        ['code' => 'CWS1', 'label' => 'CWS1', 'aliases' => ['CWS', 'CWS1']],
        ['code' => 'CWS2', 'label' => 'CWS2', 'aliases' => ['CWS2']],
        ['code' => 'CWS3', 'label' => 'CWS3', 'aliases' => ['CWS3']],
        ['code' => 'IN1', 'label' => 'IN1', 'aliases' => ['IN1']],
        ['code' => 'IN2', 'label' => 'IN2', 'aliases' => ['IN2']],
        ['code' => 'IN3', 'label' => 'IN3', 'aliases' => ['IN3']],
        ['code' => 'IN4', 'label' => 'IN4', 'aliases' => ['IN4']],
        ['code' => 'IN5', 'label' => 'IN5', 'aliases' => ['IN5']],
        ['code' => 'OUT1', 'label' => 'OUT1', 'aliases' => ['OUT1']],
        ['code' => 'OUT2', 'label' => 'OUT2', 'aliases' => ['OUT2']],
        ['code' => 'OUT3', 'label' => 'OUT3', 'aliases' => ['OUT3']],
        ['code' => 'OUT4', 'label' => 'OUT4', 'aliases' => ['OUT4']],
        ['code' => 'OUT5', 'label' => 'OUT5', 'aliases' => ['OUT5']],
        ['code' => 'P1', 'label' => 'P1', 'aliases' => ['P1']],
        ['code' => 'P2', 'label' => 'P2', 'aliases' => ['P2']],
        ['code' => 'P3', 'label' => 'P3', 'aliases' => ['P3']],
        ['code' => 'P4', 'label' => 'P4', 'aliases' => ['P4']],
        ['code' => 'P5', 'label' => 'P5', 'aliases' => ['P5']],
        ['code' => 'P6', 'label' => 'P6', 'aliases' => ['P6']],
        ['code' => 'P7', 'label' => 'P7', 'aliases' => ['P7']],
        ['code' => 'P8', 'label' => 'P8', 'aliases' => ['P8']],
        ['code' => 'P9', 'label' => 'P9', 'aliases' => ['P9']],
        ['code' => 'R1', 'label' => 'R1', 'aliases' => ['R1']],
        ['code' => 'R2', 'label' => 'R2', 'aliases' => ['R2']],
        ['code' => 'R3', 'label' => 'R3', 'aliases' => ['R3']],
        ['code' => 'R4', 'label' => 'R4', 'aliases' => ['R4']],
        ['code' => 'R5', 'label' => 'R5', 'aliases' => ['R5']],
        ['code' => 'R6', 'label' => 'R6', 'aliases' => ['R6']],
        ['code' => 'R7', 'label' => 'R7', 'aliases' => ['R7']],
        ['code' => 'D1', 'label' => 'D1', 'aliases' => ['D1']],
        ['code' => 'D2', 'label' => 'D2', 'aliases' => ['D2']],
    ];

    private const SPINTEST_PERFORMANCE_COLUMNS = [
        ['key' => 'ffa_moisture', 'label' => 'FFA dan Moisture', 'detail_keys' => ['ffa_moisture']],
        ['key' => 'spintest_cot', 'label' => 'Spintest COT', 'detail_keys' => ['spintest_cot']],
        ['key' => 'underflow_cst', 'label' => 'Underflow CST (CST 1 & CST 2)', 'detail_keys' => ['underflow_cst_1', 'underflow_cst_2']],
        ['key' => 'feed_decanter', 'label' => 'Feed Decanter (Alfa Laval 1, Alfa Laval 2, GEA, Flottweg)', 'detail_keys' => ['feed_decanter_alfa_laval_1', 'feed_decanter_alfa_laval_2', 'feed_decanter_gea', 'feed_decanter_flottweg']],
        ['key' => 'light_phase', 'label' => 'Light Phase (Alfa Laval 1, Alfa Laval 2, GEA, Flottweg)', 'detail_keys' => ['light_phase_alfa_laval_1', 'light_phase_alfa_laval_2', 'light_phase_gea', 'light_phase_flottweg']],
        ['key' => 'sterilizer', 'label' => 'Sterilizer 1-8', 'detail_keys' => ['sterilizer_1', 'sterilizer_2', 'sterilizer_3', 'sterilizer_4', 'sterilizer_5', 'sterilizer_6', 'sterilizer_7', 'sterilizer_8']],
        ['key' => 'cot', 'label' => 'COT (COT IN & COT 2)', 'detail_keys' => ['cot_in', 'cot_2']],
        ['key' => 'cst', 'label' => 'CST', 'detail_keys' => ['cst']],
        ['key' => 'fd', 'label' => 'FD (FD1-4)', 'detail_keys' => ['fd1', 'fd2', 'fd3', 'fd4']],
        ['key' => 'hp', 'label' => 'HP (HP 1-4)', 'detail_keys' => ['hp1', 'hp2', 'hp3', 'hp4']],
        ['key' => 'sd', 'label' => 'SD (SD1-4)', 'detail_keys' => ['sd1', 'sd2', 'sd3', 'sd4']],
        ['key' => 'hpl', 'label' => 'HPL (HPL1-3)', 'detail_keys' => ['hpl1', 'hpl2', 'hpl3']],
        ['key' => 'fe', 'label' => 'FE', 'detail_keys' => ['fe']],
        ['key' => 'fbp', 'label' => 'FBP (FBP1-5)', 'detail_keys' => ['fbp1', 'fbp2', 'fbp3', 'fbp4', 'fbp5']],
        ['key' => 'fp', 'label' => 'FP (FP1-9)', 'detail_keys' => ['fp1', 'fp2', 'fp3', 'fp4', 'fp5', 'fp6', 'fp7', 'fp8', 'fp9']],
        ['key' => 'perf_total', 'label' => 'Perf Total', 'detail_keys' => []],
    ];

    private const SPINTEST_PERFORMANCE_COLUMNS_SUN = [
        ['key' => 'ffa_moisture', 'label' => 'FFA dan Moisture', 'detail_keys' => ['ffa_moisture']],
        ['key' => 'spintest_cot', 'label' => 'Spintest COT', 'detail_keys' => ['spintest_cot']],
        ['key' => 'underflow_cst', 'label' => 'Underflow CST (CST 1 & CST 2)', 'detail_keys' => ['underflow_cst_1', 'underflow_cst_2']],
        ['key' => 'feed_decanter', 'label' => 'Feed Decanter (GEA1, GEA2)', 'detail_keys' => ['feed_decanter_gea']],
        ['key' => 'light_phase', 'label' => 'Light Phase (GEA1, GEA2)', 'detail_keys' => ['light_phase_gea']],
        ['key' => 'sterilizer', 'label' => 'Sterilizer 1-4', 'detail_keys' => ['sterilizer_1', 'sterilizer_2', 'sterilizer_3', 'sterilizer_4']],
        ['key' => 'perf_total', 'label' => 'Perf Total', 'detail_keys' => []],
    ];

    public function index(Request $request)
    {
        $roleFlags = $this->resolveProcessRoleFlags();
        $teamMembers = $this->getTeamMembersByOffice();

        $userOffice = trim((string) (auth()->user()->office ?? ''));
        $officeFilter = $userOffice !== ''
            ? $userOffice
            : trim((string) $request->input('office', 'all'));

        $officeOptions = ['YBS', 'SUN', 'SJN'];
        if ($officeFilter !== 'all' && !in_array($officeFilter, $officeOptions, true)) {
            $officeFilter = $userOffice !== '' ? $userOffice : 'all';
        }

        $records = KernelProsses::query()
            ->withCount('mesin')
            ->when($officeFilter !== 'all', fn($query) => $query->where('office', $officeFilter))
            ->orderByDesc('process_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function (KernelProsses $record): array {
                return [
                    'id' => $record->id,
                    'process_date' => optional($record->process_date)->format('d/m/Y'),
                    'office' => (string) ($record->office ?? '-'),
                    'input_team' => (string) ($record->input_team ?? '-'),
                    'mesin_count' => $record->mesin_count,
                ];
            });

        $machineGroups = $this->getMachineGroupsForOffice($userOffice !== '' ? $userOffice : null);

        return view('process.index', [
            'teamMembers' => $teamMembers,
            'records' => $records,
            'machineGroups' => $machineGroups,
            'officeFilter' => $officeFilter,
            'officeOptions' => $officeOptions,
            'canManageTeamMeta' => $roleFlags['can_manage_team_meta'],
            'canManageMachineData' => $roleFlags['can_manage_machine_data'],
        ]);
    }

    public function spintestIndex(Request $request)
    {
        $roleFlags = $this->resolveProcessRoleFlags();
        $teamMembers = $this->getTeamMembersByOffice();

        $userOffice = trim((string) (auth()->user()->office ?? ''));
        $officeFilter = $userOffice !== ''
            ? $userOffice
            : trim((string) $request->input('office', 'all'));

        $officeOptions = ['YBS', 'SUN', 'SJN'];
        if ($officeFilter !== 'all' && !in_array($officeFilter, $officeOptions, true)) {
            $officeFilter = $userOffice !== '' ? $userOffice : 'all';
        }

        $records = SpintestProsses::with('mesin')
            ->when($officeFilter !== 'all', fn($query) => $query->where('office', $officeFilter))
            ->orderByDesc('process_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function (SpintestProsses $record): array {
                $team = (string) ($record->input_team ?? '-');
                $machinesForTeam = [];
                foreach ($record->mesin as $m) {
                    if ((string) ($m->team_name ?? '') !== $team) {
                        continue;
                    }
                    $start = substr((string) ($m->production_start_time ?? ''), 0, 5);
                    $end = substr((string) ($m->production_end_time ?? ''), 0, 5);
                    $machinesForTeam[] = trim(((string) $m->machine_name) . ($start !== '' ? ' ' . $start . '-' . $end : ''));
                }

                return [
                    'id' => $record->id,
                    'process_date' => optional($record->process_date)->format('d/m/Y'),
                    'office' => (string) ($record->office ?? '-'),
                    'input_team' => $team,
                    'machines_summary' => !empty($machinesForTeam) ? implode('; ', $machinesForTeam) : '-',
                ];
            });

        $officeForSpintestConfig = strtoupper(trim($userOffice !== '' ? $userOffice : ($officeFilter !== 'all' ? $officeFilter : 'YBS')));
        $machineGroups = $this->getSpintestMachineGroups($officeForSpintestConfig);

        return view('process.spintest-index', [
            'teamMembers' => $teamMembers,
            'records' => $records,
            'machineGroups' => $machineGroups,
            'spintestMachineGuide' => $this->getSpintestMachineGuide($officeForSpintestConfig),
            'officeFilter' => $officeFilter,
            'officeOptions' => $officeOptions,
            'canManageTeamMeta' => $roleFlags['can_manage_team_meta'],
            'canManageMachineData' => $roleFlags['can_manage_machine_data'],
        ]);
    }

    public function spintestStore(Request $request)
    {
        $roleFlags = $this->resolveProcessRoleFlags();
        $teamMemberRules = $this->buildTeamMemberValidationRules();
        $inputTeam = (string) $request->input('input_team');
        if (!in_array($inputTeam, self::TEAM_OPTIONS, true)) {
            return back()->withInput()->withErrors([
                'input_team' => 'Tim input tidak valid.',
            ]);
        }

        $office = (string) auth()->user()->office;
        $existingProcess = SpintestProsses::query()
            ->whereDate('process_date', $request->input('process_date'))
            ->where('office', $office)
            ->where('input_team', $inputTeam)
            ->first();

        $isTeamOneInput = $inputTeam === 'Tim 1';
        $validated = $request->validate([
            'process_date' => ['required', 'date'],
            'machines' => ['nullable', 'array'],
            'machines.*.selected' => ['nullable'],
            'machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'machines.*.machine_group' => ['nullable', 'string', 'max:120'],
            'machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'machines.*.end_time' => ['nullable', 'date_format:H:i'],
            'spare_machines.*.selected' => ['nullable'],
            'team_1_start_time' => [$isTeamOneInput ? 'required' : 'nullable', 'date_format:H:i'],
            'team_1_end_time' => [$isTeamOneInput ? 'required' : 'nullable', 'date_format:H:i'],
            'team_1_members' => ['nullable', 'array'],
            'team_1_members.*' => $teamMemberRules,
            'team_2_start_time' => [$isTeamOneInput ? 'nullable' : 'required', 'date_format:H:i'],
            'team_2_end_time' => [$isTeamOneInput ? 'nullable' : 'required', 'date_format:H:i'],
            'team_2_members' => ['nullable', 'array'],
            'team_2_members.*' => $teamMemberRules,
            'spare_machines' => ['nullable', 'array'],
            'spare_machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'spare_machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'spare_machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'spare_machines.*.end_time' => ['nullable', 'date_format:H:i'],
            'other_conditions' => ['nullable', 'array'],
            'other_conditions.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'other_conditions.*.reason' => ['nullable', 'string', 'max:255', 'required_with:other_conditions.*.start_time,other_conditions.*.end_time'],
            'other_conditions.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.end_time'],
            'other_conditions.*.end_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.start_time'],
        ]);

        if ($existingProcess !== null) {
            return back()
                ->withInput()
                ->withErrors([
                    'process_date' => 'Data proses spintest untuk tanggal ini dan tim ini sudah diinput.',
                ]);
        }

        $otherConditionsByTeam = $this->extractOtherConditionsPayload($request, $inputTeam);

        $process = SpintestProsses::create([
            'user_id' => auth()->id(),
            'office' => $office,
            'process_date' => $validated['process_date'],
            'input_team' => $inputTeam,
            'team_1_start_time' => $isTeamOneInput ? ($validated['team_1_start_time'] ?? '00:00') : '00:00',
            'team_1_end_time' => $isTeamOneInput ? ($validated['team_1_end_time'] ?? '00:00') : '00:00',
            'team_1_members' => $isTeamOneInput ? ($validated['team_1_members'] ?? []) : [],
            'team_1_other_conditions' => $isTeamOneInput ? ($otherConditionsByTeam['Tim 1'] ?? []) : [],
            'team_2_start_time' => $isTeamOneInput ? '00:00' : ($validated['team_2_start_time'] ?? '00:00'),
            'team_2_end_time' => $isTeamOneInput ? '00:00' : ($validated['team_2_end_time'] ?? '00:00'),
            'team_2_members' => $isTeamOneInput ? [] : ($validated['team_2_members'] ?? []),
            'team_2_other_conditions' => $isTeamOneInput ? [] : ($otherConditionsByTeam['Tim 2'] ?? []),
        ]);

        $machinePayload = $this->extractSpintestMachinePayload($request, $inputTeam, $office);
        if (!empty($machinePayload)) {
            foreach ($machinePayload as $machineRow) {
                SpintestMesin::create($machineRow + [
                    'spintest_prosses_id' => $process->id,
                ]);
            }
        }

        return redirect()
            ->route('process.spintest.index')
            ->with('success', 'Informasi proses spintest ' . $inputTeam . ' berhasil disimpan.');
    }

    public function spintestShow(SpintestProsses $spintestProsses)
    {
        $spintestProsses->load(['mesin', 'user']);
        $visibleTeam = $this->resolveSpintestVisibleTeam($spintestProsses);

        return view('process.spintest-show', [
            'record' => $spintestProsses,
            'team1' => $this->buildSpintestTeamRow($spintestProsses, 'Tim 1'),
            'team2' => $this->buildSpintestTeamRow($spintestProsses, 'Tim 2'),
            'visibleTeam' => $visibleTeam,
            'teamDetail' => $this->buildSpintestMachineDetail($spintestProsses, $visibleTeam ?? 'Tim 1'),
        ]);
    }

    public function spintestEdit(SpintestProsses $spintestProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_team_meta']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah checklist tim dan jam shift.');
        }

        return view('process.spintest-edit', [
            'record' => $spintestProsses,
            'team1' => $this->buildSpintestTeamRow($spintestProsses, 'Tim 1'),
            'team2' => $this->buildSpintestTeamRow($spintestProsses, 'Tim 2'),
            'visibleTeam' => $this->resolveSpintestVisibleTeam($spintestProsses),
            'teamMembers' => $this->getTeamMembersByOffice(),
        ]);
    }

    public function spintestUpdate(Request $request, SpintestProsses $spintestProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_team_meta']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah checklist tim dan jam shift.');
        }

        $teamMemberRules = $this->buildTeamMemberValidationRules();
        $visibleTeam = $this->resolveSpintestVisibleTeam($spintestProsses);

        if ($visibleTeam === null) {
            abort(404, 'Tim spintest tidak valid.');
        }

        $isTeamOne = $visibleTeam === 'Tim 1';

        $validated = $request->validate([
            'process_date' => ['required', 'date'],
            'team_1_start_time' => [$isTeamOne ? 'required' : 'nullable', 'date_format:H:i'],
            'team_1_end_time' => [$isTeamOne ? 'required' : 'nullable', 'date_format:H:i'],
            'team_1_members' => ['nullable', 'array'],
            'team_1_members.*' => $teamMemberRules,
            'team_2_start_time' => [$isTeamOne ? 'nullable' : 'required', 'date_format:H:i'],
            'team_2_end_time' => [$isTeamOne ? 'nullable' : 'required', 'date_format:H:i'],
            'team_2_members' => ['nullable', 'array'],
            'team_2_members.*' => $teamMemberRules,
        ]);

        $payload = ['process_date' => $validated['process_date']];

        if ($isTeamOne) {
            $payload['team_1_start_time'] = $validated['team_1_start_time'] ?? '00:00';
            $payload['team_1_end_time'] = $validated['team_1_end_time'] ?? '00:00';
            $payload['team_1_members'] = $validated['team_1_members'] ?? [];
        } else {
            $payload['team_2_start_time'] = $validated['team_2_start_time'] ?? '00:00';
            $payload['team_2_end_time'] = $validated['team_2_end_time'] ?? '00:00';
            $payload['team_2_members'] = $validated['team_2_members'] ?? [];
        }

        $spintestProsses->update($payload);

        return redirect()
            ->route('process.spintest.index')
            ->with('success', 'Informasi proses spintest berhasil diperbarui.');
    }

    public function spintestMachineDetail(SpintestProsses $spintestProsses)
    {
        $spintestProsses->load(['mesin', 'user']);
        $visibleTeam = $this->resolveSpintestVisibleTeam($spintestProsses);

        return view('process.spintest-machine-detail', [
            'record' => $spintestProsses,
            'visibleTeam' => $visibleTeam,
            'teamDetail' => $this->buildSpintestMachineDetail($spintestProsses, $visibleTeam ?? 'Tim 1'),
        ]);
    }

    public function spintestEditMachines(SpintestProsses $spintestProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_machine_data']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jam proses mesin.');
        }

        $spintestProsses->load('mesin');

        $visibleTeam = $this->resolveSpintestVisibleTeam($spintestProsses);
        $selectedMainMachines = [];
        $spareRowsByTeam = [
            'Tim 1' => [],
            'Tim 2' => [],
        ];
        $otherConditionsByTeam = [
            'Tim 1' => $this->getSpintestOtherConditionsForTeam($spintestProsses, 'Tim 1'),
            'Tim 2' => $this->getSpintestOtherConditionsForTeam($spintestProsses, 'Tim 2'),
        ];

        foreach ($spintestProsses->mesin as $machine) {
            $teamName = (string) $machine->team_name;
            if (!in_array($teamName, self::TEAM_OPTIONS, true)) {
                continue;
            }

            if ($machine->is_spare_input) {
                $spareRowsByTeam[$teamName][] = [
                    'team_name' => $teamName,
                    'machine_name' => (string) $machine->machine_name,
                    'start_time' => substr((string) $machine->production_start_time, 0, 5),
                    'end_time' => substr((string) $machine->production_end_time, 0, 5),
                    'selected' => true,
                ];
                continue;
            }

            $key = $teamName . '|' . strtoupper(trim((string) $machine->machine_name));
            if (!isset($selectedMainMachines[$key])) {
                $selectedMainMachines[$key] = [
                    'start_time' => substr((string) $machine->production_start_time, 0, 5),
                    'end_time' => substr((string) $machine->production_end_time, 0, 5),
                ];
            }
        }

        foreach (self::TEAM_OPTIONS as $teamName) {
            if (empty($spareRowsByTeam[$teamName])) {
                $spareRowsByTeam[$teamName] = [
                    [
                        'team_name' => $teamName,
                        'machine_name' => '',
                        'start_time' => '',
                        'end_time' => '',
                        'selected' => false,
                    ]
                ];
            }

            if (empty($otherConditionsByTeam[$teamName])) {
                $otherConditionsByTeam[$teamName] = [
                    [
                        'team_name' => $teamName,
                        'reason' => '',
                        'start_time' => '',
                        'end_time' => '',
                    ]
                ];
            }
        }

        $machineGroups = $this->getSpintestMachineGroups((string) ($spintestProsses->office ?? ''));
        $machineOptions = collect($machineGroups)->flatten()->unique()->values()->all();

        return view('process.spintest-edit-machines', [
            'record' => $spintestProsses,
            'machineGroups' => $machineGroups,
            'selectedMainMachines' => $selectedMainMachines,
            'spareRowsByTeam' => $spareRowsByTeam,
            'otherConditionsByTeam' => $otherConditionsByTeam,
            'machineOptions' => $machineOptions,
            'visibleTeam' => $visibleTeam,
        ]);
    }

    public function spintestUpdateMachines(Request $request, SpintestProsses $spintestProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_machine_data']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jam proses mesin.');
        }

        $visibleTeam = $this->resolveSpintestVisibleTeam($spintestProsses);

        if ($visibleTeam === null) {
            abort(404, 'Tim spintest tidak valid.');
        }

        $request->validate([
            'machines' => ['nullable', 'array'],
            'machines.*.selected' => ['nullable'],
            'machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'machines.*.machine_group' => ['nullable', 'string', 'max:120'],
            'machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'machines.*.end_time' => ['nullable', 'date_format:H:i'],
            'spare_machines' => ['nullable', 'array'],
            'spare_machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'spare_machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'spare_machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'spare_machines.*.end_time' => ['nullable', 'date_format:H:i'],
            'other_conditions' => ['nullable', 'array'],
            'other_conditions.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'other_conditions.*.reason' => ['nullable', 'string', 'max:255', 'required_with:other_conditions.*.start_time,other_conditions.*.end_time'],
            'other_conditions.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.end_time'],
            'other_conditions.*.end_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.start_time'],
        ]);

        $otherConditionsByTeam = $this->extractOtherConditionsPayload($request, $visibleTeam);
        $machinePayload = $this->extractSpintestMachinePayload($request, $visibleTeam, (string) ($spintestProsses->office ?? ''));

        $spintestProsses->mesin()
            ->where('team_name', $visibleTeam)
            ->delete();

        if (!empty($machinePayload)) {
            $spintestProsses->mesin()->createMany($machinePayload);
        }

        if ($visibleTeam === 'Tim 1') {
            $spintestProsses->update([
                'team_1_other_conditions' => $otherConditionsByTeam['Tim 1'] ?? [],
            ]);
        } else {
            $spintestProsses->update([
                'team_2_other_conditions' => $otherConditionsByTeam['Tim 2'] ?? [],
            ]);
        }

        return redirect()
            ->route('process.spintest.index')
            ->with('success', 'Data mesin spintest berhasil diperbarui.');
    }

    private function getSpintestMachineGroups(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? '')));
        if ($officeCode === 'SUN') {
            return [
                'Analisa Moisture & Spintess' => [
                    'Ffa&moisture',
                    'Spintest COT',
                    'Underflow CST 1',
                    'Underflow CST 2',
                    'Feed Decanter GEA 1',
                    'Feed Decanter GEA 2',
                    'Light Phase GEA 1',
                    'Light Phase GEA 2',
                ],
                'Lap Jangkos' => [
                    'Sterilizer 1',
                    'Sterilizer 2',
                    'Sterilizer 3',
                    'Sterilizer 4',
                ],
            ];
        }

        $groups = [
            'Analisa Moisture & Spintess' => [
                'FFA & Moisture',
                'Spintest COT',
                'Underflow CST 1',
                'Underflow CST 2',
                'Feed Decanter Alfa Laval 1',
                'Feed Decanter Alfa Laval 2',
                'Feed Decanter GEA',
                'Feed Decanter Flottweg',
                'Light Phase Alfa Laval 1',
                'Light Phase Alfa Laval 2',
                'Light Phase GEA',
                'Light Phase Flottweg',
            ],
            'Lap Jangkos' => [
                'Sterilizer 1',
                'Sterilizer 2',
                'Sterilizer 3',
                'Sterilizer 4',
                'Sterilizer 5',
                'Sterilizer 6',
                'Sterilizer 7',
                'Sterilizer 8',
            ],
            'Oil Loss Foss' => [
                'COT IN',
                'COT 2',
                'CST',
                'FD1',
                'FD2',
                'FD3',
                'FD4',
                'HP1',
                'HP2',
                'HP3',
                'HP4',
                'SD1',
                'SD2',
                'SD3',
                'SD4',
                'HPL1',
                'HPL2',
                'HPL3',
                'FE',
                'FBP1',
                'FBP2',
                'FBP3',
                'FBP4',
                'FBP5',
                'FP1',
                'FP2',
                'FP3',
                'FP4',
                'FP5',
                'FP6',
                'FP7',
                'FP8',
                'FP9',
            ],
        ];
        if ($officeCode === 'SUN') {
            unset($groups['Oil Loss Foss']);
        }

        return $groups;
    }

    public function store(Request $request)
    {
        $roleFlags = $this->resolveProcessRoleFlags();
        $teamMemberRules = $this->buildTeamMemberValidationRules();
        $inputTeam = (string) $request->input('input_team');
        if (!in_array($inputTeam, self::TEAM_OPTIONS, true)) {
            return back()->withInput()->withErrors([
                'input_team' => 'Tim input tidak valid.',
            ]);
        }

        $office = (string) auth()->user()->office;
        $existingProcess = KernelProsses::query()
            ->whereDate('process_date', $request->input('process_date'))
            ->where('office', $office)
            ->where('input_team', $inputTeam)
            ->first();

        if (!$roleFlags['can_manage_team_meta'] && $roleFlags['can_manage_machine_data']) {
            $validated = $request->validate([
                'process_date' => ['required', 'date'],
                'machines' => ['nullable', 'array'],
                'machines.*.selected' => ['nullable'],
                'machines.*.machine_name' => ['nullable', 'string', 'max:150'],
                'machines.*.machine_group' => ['nullable', 'string', 'max:120'],
                'machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
                'machines.*.start_time' => ['nullable', 'date_format:H:i'],
                'machines.*.end_time' => ['nullable', 'date_format:H:i'],
                'spare_machines' => ['nullable', 'array'],
                'spare_machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
                'spare_machines.*.machine_name' => ['nullable', 'string', 'max:150'],
                'spare_machines.*.start_time' => ['nullable', 'date_format:H:i'],
                'spare_machines.*.end_time' => ['nullable', 'date_format:H:i'],
                'other_conditions' => ['nullable', 'array'],
                'other_conditions.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
                'other_conditions.*.reason' => ['nullable', 'string', 'max:255', 'required_with:other_conditions.*.start_time,other_conditions.*.end_time'],
                'other_conditions.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.end_time'],
                'other_conditions.*.end_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.start_time'],
            ]);

            if (!$existingProcess) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'process_date' => 'Data checklist dan shift belum diinput Analis untuk tanggal dan tim ini.',
                    ]);
            }

            $machinePayload = $this->extractMachinePayload($request, $inputTeam, $office);
            $otherConditionsByTeam = $this->extractOtherConditionsPayload($request, $inputTeam);

            $existingProcess->mesin()
                ->where('team_name', $inputTeam)
                ->delete();

            if (!empty($machinePayload)) {
                $existingProcess->mesin()->createMany($machinePayload);
            }

            if ($inputTeam === 'Tim 1') {
                $existingProcess->update([
                    'team_1_other_conditions' => $otherConditionsByTeam['Tim 1'] ?? [],
                ]);
            } else {
                $existingProcess->update([
                    'team_2_other_conditions' => $otherConditionsByTeam['Tim 2'] ?? [],
                ]);
            }

            return redirect()
                ->route('process.index')
                ->with('success', 'Detail mesin ' . $inputTeam . ' berhasil disimpan.');
        }

        if (!$roleFlags['can_manage_team_meta']) {
            return back()
                ->withInput()
                ->withErrors([
                    'input_team' => 'Anda tidak memiliki akses untuk mengubah checklist tim dan jam shift.',
                ]);
        }

        $isTeamOneInput = $inputTeam === 'Tim 1';

        $validated = $request->validate([
            'process_date' => ['required', 'date'],

            'team_1_start_time' => [$isTeamOneInput ? 'required' : 'nullable', 'date_format:H:i'],
            'team_1_end_time' => [$isTeamOneInput ? 'required' : 'nullable', 'date_format:H:i'],
            'team_1_start_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_1_end_downtime'],
            'team_1_end_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_1_start_downtime'],
            'team_1_members' => ['nullable', 'array'],
            'team_1_members.*' => $teamMemberRules,

            'team_2_start_time' => [$isTeamOneInput ? 'nullable' : 'required', 'date_format:H:i'],
            'team_2_end_time' => [$isTeamOneInput ? 'nullable' : 'required', 'date_format:H:i'],
            'team_2_start_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_2_end_downtime'],
            'team_2_end_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_2_start_downtime'],
            'team_2_members' => ['nullable', 'array'],
            'team_2_members.*' => $teamMemberRules,

            'machines' => ['nullable', 'array'],
            'machines.*.selected' => ['nullable'],
            'machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'machines.*.machine_group' => ['nullable', 'string', 'max:120'],
            'machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'machines.*.end_time' => ['nullable', 'date_format:H:i'],

            'spare_machines' => ['nullable', 'array'],
            'spare_machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'spare_machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'spare_machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'spare_machines.*.end_time' => ['nullable', 'date_format:H:i'],

            'other_conditions' => ['nullable', 'array'],
            'other_conditions.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'other_conditions.*.reason' => ['nullable', 'string', 'max:255', 'required_with:other_conditions.*.start_time,other_conditions.*.end_time'],
            'other_conditions.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.end_time'],
            'other_conditions.*.end_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.start_time'],
        ]);

        $alreadyExists = $existingProcess !== null;

        if ($alreadyExists) {
            return back()
                ->withInput()
                ->withErrors([
                    'process_date' => 'Data proses untuk tanggal ini dan tim ini sudah diinput.',
                ]);
        }

        $conflicts = array_intersect(
            $isTeamOneInput ? ($validated['team_1_members'] ?? []) : [],
            !$isTeamOneInput ? ($validated['team_2_members'] ?? []) : []
        );
        if (!empty($conflicts)) {
            return back()
                ->withInput()
                ->withErrors([
                    'team_2_members' => 'Nama yang sama tidak boleh dipilih di Tim 1 dan Tim 2: ' . implode(', ', $conflicts),
                ]);
        }

        $process = KernelProsses::create([
            'user_id' => auth()->id(),
            'office' => $office,
            'process_date' => $validated['process_date'],
            'input_team' => $inputTeam,
            'team_1_start_time' => $isTeamOneInput ? ($validated['team_1_start_time'] ?? '00:00') : '00:00',
            'team_1_end_time' => $isTeamOneInput ? ($validated['team_1_end_time'] ?? '00:00') : '00:00',
            'team_1_start_downtime' => $isTeamOneInput ? ($validated['team_1_start_downtime'] ?? null) : null,
            'team_1_end_downtime' => $isTeamOneInput ? ($validated['team_1_end_downtime'] ?? null) : null,
            'team_1_downtime' => null,
            'team_1_members' => $isTeamOneInput ? ($validated['team_1_members'] ?? []) : [],
            'team_1_other_conditions' => [],
            'team_2_start_time' => $isTeamOneInput ? '00:00' : ($validated['team_2_start_time'] ?? '00:00'),
            'team_2_end_time' => $isTeamOneInput ? '00:00' : ($validated['team_2_end_time'] ?? '00:00'),
            'team_2_start_downtime' => $isTeamOneInput ? null : ($validated['team_2_start_downtime'] ?? null),
            'team_2_end_downtime' => $isTeamOneInput ? null : ($validated['team_2_end_downtime'] ?? null),
            'team_2_downtime' => null,
            'team_2_members' => $isTeamOneInput ? [] : ($validated['team_2_members'] ?? []),
            'team_2_other_conditions' => [],
        ]);

        if ($roleFlags['can_manage_machine_data']) {
            $machinePayload = $this->extractMachinePayload($request, $inputTeam, $office);
            if (!empty($machinePayload)) {
                $process->mesin()->createMany($machinePayload);
            }

            $otherConditionsByTeam = $this->extractOtherConditionsPayload($request, $inputTeam);
            if ($inputTeam === 'Tim 1') {
                $process->update([
                    'team_1_other_conditions' => $otherConditionsByTeam['Tim 1'] ?? [],
                ]);
            } else {
                $process->update([
                    'team_2_other_conditions' => $otherConditionsByTeam['Tim 2'] ?? [],
                ]);
            }
        }

        return redirect()
            ->route('process.index')
            ->with('success', 'Informasi proses ' . $inputTeam . ' berhasil disimpan.');
    }

    public function show(KernelProsses $kernelProsses)
    {
        $team1 = $this->buildTeamRow($kernelProsses, 'Tim 1');
        $team2 = $this->buildTeamRow($kernelProsses, 'Tim 2');
        $visibleTeam = in_array((string) $kernelProsses->input_team, self::TEAM_OPTIONS, true)
            ? (string) $kernelProsses->input_team
            : null;

        return view('process.show', [
            'record' => $kernelProsses,
            'team1' => $team1,
            'team2' => $team2,
            'visibleTeam' => $visibleTeam,
        ]);
    }

    public function edit(KernelProsses $kernelProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_team_meta']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah checklist tim dan jam shift.');
        }

        $team1 = $this->buildTeamRow($kernelProsses, 'Tim 1');
        $team2 = $this->buildTeamRow($kernelProsses, 'Tim 2');
        $visibleTeam = in_array((string) $kernelProsses->input_team, self::TEAM_OPTIONS, true)
            ? (string) $kernelProsses->input_team
            : null;
        $teamMembers = $this->getTeamMembersByOffice();

        return view('process.edit', [
            'record' => $kernelProsses,
            'team1' => $team1,
            'team2' => $team2,
            'teamMembers' => $teamMembers,
            'visibleTeam' => $visibleTeam,
        ]);
    }

    public function performanceSampelBoy(Request $request)
    {
        $dateStart = (string) $request->input('date_start', now()->format('Y-m-d'));
        $dateEnd = (string) $request->input('date_end', now()->format('Y-m-d'));
        $officeOptions = $this->getAvailableOfficesForPerformance();
        $defaultOffice = (string) (auth()->user()->office ?? '');
        $selectedOffice = (string) $request->input('office', $defaultOffice !== '' ? $defaultOffice : 'all');

        if ($selectedOffice !== 'all' && !in_array($selectedOffice, $officeOptions, true)) {
            $selectedOffice = $defaultOffice !== '' ? $defaultOffice : 'all';
        }

        // Normalize office to uppercase for consistent database comparison
        if ($selectedOffice !== 'all') {
            $selectedOffice = strtoupper(trim($selectedOffice));
        }

        // Ensure dates are valid and start <= end
        try {
            $startDate = Carbon::parse($dateStart)->startOfDay();
            $endDate = Carbon::parse($dateEnd)->endOfDay();
            if ($startDate > $endDate) {
                $startDate = $endDate->copy()->startOfDay();
                $endDate = $startDate->copy()->endOfDay();
            }
        } catch (\Throwable) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        }

        $records = KernelProsses::query()
            ->with('mesin')
            ->whereBetween('process_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->when($selectedOffice !== 'all', fn($query) => $query->where('office', $selectedOffice))
            ->orderBy('process_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        $team1PerfTotals = [];
        $team2PerfTotals = [];

        foreach ($records as $record) {
            /** @var KernelProsses $record */
            $team1Machines = $record->mesin->where('team_name', 'Tim 1')->values();
            $team2Machines = $record->mesin->where('team_name', 'Tim 2')->values();

            $inputTeam = in_array((string) $record->input_team, self::TEAM_OPTIONS, true)
                ? (string) $record->input_team
                : null;

            if ($inputTeam === 'Tim 1') {
                $row = $this->buildPerformanceRow(
                    $record,
                    'Tim 1',
                    (array) ($record->team_1_members ?? []),
                    $team1Machines,
                    $selectedOffice
                );
                $rows[] = $row;
                if (!empty($row['perf_total']) && $row['perf_total'] !== '-') {
                    $team1PerfTotals[] = (float) str_replace('%', '', $row['perf_total']);
                }
                continue;
            }

            if ($inputTeam === 'Tim 2') {
                $row = $this->buildPerformanceRow(
                    $record,
                    'Tim 2',
                    (array) ($record->team_2_members ?? []),
                    $team2Machines,
                    $selectedOffice
                );
                $rows[] = $row;
                if (!empty($row['perf_total']) && $row['perf_total'] !== '-') {
                    $team2PerfTotals[] = (float) str_replace('%', '', $row['perf_total']);
                }
                continue;
            }

            $row1 = $this->buildPerformanceRow(
                $record,
                'Tim 1',
                (array) ($record->team_1_members ?? []),
                $team1Machines,
                $selectedOffice
            );
            $rows[] = $row1;
            if (!empty($row1['perf_total']) && $row1['perf_total'] !== '-') {
                $team1PerfTotals[] = (float) str_replace('%', '', $row1['perf_total']);
            }

            $row2 = $this->buildPerformanceRow(
                $record,
                'Tim 2',
                (array) ($record->team_2_members ?? []),
                $team2Machines,
                $selectedOffice
            );
            $rows[] = $row2;
            if (!empty($row2['perf_total']) && $row2['perf_total'] !== '-') {
                $team2PerfTotals[] = (float) str_replace('%', '', $row2['perf_total']);
            }
        }

        $formatAverage = function (array $values): string {
            if (empty($values)) {
                return '-';
            }

            return number_format(array_sum($values) / count($values), 2) . '%';
        };

        $avgTeam1 = $formatAverage($team1PerfTotals);
        $avgTeam2 = $formatAverage($team2PerfTotals);

        return view('process.performance-sampel-boy', [
            'dateStart' => $startDate->format('Y-m-d'),
            'dateEnd' => $endDate->format('Y-m-d'),
            'selectedOffice' => $selectedOffice,
            'officeOptions' => $officeOptions,
            'rows' => $rows,
            'avgTeam1' => $avgTeam1,
            'avgTeam2' => $avgTeam2,
        ]);
    }

    public function lapJangkosRekapBreakdown(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();

        $records = KernelProsses::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('process_date', [$startDate, $endDate])
            ->orderBy('process_date')
            ->orderBy('id')
            ->get();

        $breakdownData = $this->buildBreakdownReportData($records);

        return view('process.lap-jangkos.rekap-breakdown', [
            'breakdownData' => $breakdownData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function lapJangkosRekapBreakdownExport(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();

        $records = KernelProsses::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('process_date', [$startDate, $endDate])
            ->orderBy('process_date')
            ->orderBy('id')
            ->get();

        $breakdownData = $this->buildBreakdownReportData($records);

        return $this->downloadAnalysisExport(
            'Rekap Breakdown',
            $startDate,
            $endDate,
            $breakdownData['rows'],
            ['Tanggal', 'Alasan', 'Jam Awal Breakdown', 'Jam Akhir Breakdown', 'Total Jam Breakdown'],
            'Rekap_Breakdown_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function exportPerformanceSampelBoy(Request $request)
    {
        $dateStart = (string) $request->input('date_start', now()->format('Y-m-d'));
        $dateEnd = (string) $request->input('date_end', now()->format('Y-m-d'));
        $officeOptions = $this->getAvailableOfficesForPerformance();
        $defaultOffice = (string) (auth()->user()->office ?? '');
        $selectedOffice = (string) $request->input('office', $defaultOffice !== '' ? $defaultOffice : 'all');

        if ($selectedOffice !== 'all' && !in_array($selectedOffice, $officeOptions, true)) {
            $selectedOffice = $defaultOffice !== '' ? $defaultOffice : 'all';
        }

        // Normalize office to uppercase for consistent database comparison
        if ($selectedOffice !== 'all') {
            $selectedOffice = strtoupper(trim($selectedOffice));
        }

        // Ensure dates are valid and start <= end
        try {
            $startDate = Carbon::parse($dateStart)->startOfDay();
            $endDate = Carbon::parse($dateEnd)->endOfDay();
            if ($startDate > $endDate) {
                $startDate = $endDate->copy()->startOfDay();
                $endDate = $startDate->copy()->endOfDay();
            }
        } catch (\Throwable) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        }

        $records = KernelProsses::query()
            ->with('mesin')
            ->whereBetween('process_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->when($selectedOffice !== 'all', fn($query) => $query->where('office', $selectedOffice))
            ->orderBy('process_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($records as $record) {
            /** @var KernelProsses $record */
            $team1Machines = $record->mesin->where('team_name', 'Tim 1')->values();
            $team2Machines = $record->mesin->where('team_name', 'Tim 2')->values();

            $inputTeam = in_array((string) $record->input_team, self::TEAM_OPTIONS, true)
                ? (string) $record->input_team
                : null;

            if ($inputTeam === 'Tim 1') {
                $rows[] = $this->buildPerformanceRow(
                    $record,
                    'Tim 1',
                    (array) ($record->team_1_members ?? []),
                    $team1Machines,
                    $selectedOffice
                );
                continue;
            }

            if ($inputTeam === 'Tim 2') {
                $rows[] = $this->buildPerformanceRow(
                    $record,
                    'Tim 2',
                    (array) ($record->team_2_members ?? []),
                    $team2Machines,
                    $selectedOffice
                );
                continue;
            }

            $rows[] = $this->buildPerformanceRow(
                $record,
                'Tim 1',
                (array) ($record->team_1_members ?? []),
                $team1Machines,
                $selectedOffice
            );

            $rows[] = $this->buildPerformanceRow(
                $record,
                'Tim 2',
                (array) ($record->team_2_members ?? []),
                $team2Machines,
                $selectedOffice
            );
        }

        $dateStartFormatted = Carbon::parse($startDate)->format('Ymd');
        $dateEndFormatted = Carbon::parse($endDate)->format('Ymd');
        $officeLabel = $selectedOffice === 'all' ? 'all-office' : strtolower($selectedOffice);

        $filename = 'Performance-Summary-' . $dateStartFormatted . '-' . $dateEndFormatted . '-' . $officeLabel . '.xlsx';

        return Excel::download(
            new PerformanceSampelBoyExport($rows, $startDate->format('Y-m-d'), $selectedOffice),
            $filename
        );
    }

    private function buildPerformanceSpintestRows(Carbon $startDate, Carbon $endDate, string $selectedOffice): array
    {
        $officeScope = $selectedOffice === 'all' ? null : $selectedOffice;

        // Get Spintest processes with their machines (only checked machines)
        $spintestProcesses = SpintestProsses::with(['mesin', 'user'])
            ->when($officeScope !== null, fn($q) => $q->where('office', $officeScope))
            ->whereBetween('process_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('process_date')
            ->orderBy('id')
            ->get();

        $performanceColumns = $this->getSpintestPerformanceColumns($selectedOffice);

        // Collect machine entries with their detail keys (from SpintestMesin records)
        $machineDetailKeysByProcess = [];

        foreach ($spintestProcesses as $proc) {
            $tanggal = optional($proc->process_date)->toDateString();
            $processId = (int) $proc->id;
            $office = (string) ($proc->office ?? '-');
            $creator = trim((string) ($proc->user?->name ?? '-'));
            $teamName = $proc->input_team ?? 'Tim 1';

            // Get team's time range
            $jamMulai = $teamName === 'Tim 1'
                ? substr((string) $proc->team_1_start_time, 0, 5)
                : substr((string) $proc->team_2_start_time, 0, 5);
            $jamAkhir = $teamName === 'Tim 1'
                ? substr((string) $proc->team_1_end_time, 0, 5)
                : substr((string) $proc->team_2_end_time, 0, 5);

            // Map machines to detail keys (only checked machines from SpintestMesin)
            $detailKeysByMachine = [];
            $detailRowsByKey = [];
            foreach ($proc->mesin as $m) {
                if ((string) ($m->team_name ?? '') !== (string) $teamName) {
                    continue;
                }

                $machineName = strtoupper(trim((string) ($m->machine_name ?? '')));
                $group = strtoupper(trim((string) ($m->machine_group ?? '')));

                $detailKey = '';
                if (str_contains($group, 'ANALISA') || str_contains($group, 'MOISTURE') || str_contains($group, 'SPINTES')) {
                    if (str_contains($machineName, 'FFA')) {
                        $detailKey = 'ffa_moisture';
                    } elseif (str_contains($machineName, 'SPINTEST') && str_contains($machineName, 'COT')) {
                        $detailKey = 'spintest_cot';
                    } elseif (str_contains($machineName, 'CST')) {
                        $detailKey = str_contains($machineName, '2') ? 'underflow_cst_2' : 'underflow_cst_1';
                    } elseif (str_contains($machineName, 'ALFA LAVAL')) {
                        $detailKey = str_contains($machineName, '2') ? 'feed_decanter_alfa_laval_2' : 'feed_decanter_alfa_laval_1';
                    } elseif (str_contains($machineName, 'GEA')) {
                        $detailKey = 'feed_decanter_gea';
                    } elseif (str_contains($machineName, 'FLOTT') || str_contains($machineName, 'FLOTTWEG')) {
                        $detailKey = 'feed_decanter_flottweg';
                    } elseif (str_contains($machineName, 'LIGHT PHASE') || str_contains($group, 'LIGHT')) {
                        if (str_contains($machineName, 'ALFA LAVAL') && str_contains($machineName, '2')) {
                            $detailKey = 'light_phase_alfa_laval_2';
                        } elseif (str_contains($machineName, 'ALFA LAVAL')) {
                            $detailKey = 'light_phase_alfa_laval_1';
                        } elseif (str_contains($machineName, 'GEA')) {
                            $detailKey = 'light_phase_gea';
                        } else {
                            $detailKey = 'light_phase_flottweg';
                        }
                    }
                }

                if ($detailKey === '' && str_contains($group, 'LAP JANGKOS')) {
                    if (preg_match('/(\d+)/', $machineName, $mnum)) {
                        $n = (int) $mnum[1];
                        if ($n >= 1 && $n <= 8) {
                            $detailKey = 'sterilizer_' . $n;
                        }
                    }
                }

                if ($detailKey === '' && str_contains($group, 'OIL LOSS')) {
                    $detailKey = $this->resolveOilFossPerformanceKey($machineName) ?: '';
                }

                // Backward-compatible fallback for legacy spare rows that still store group as SPARE INPUT.
                if ($detailKey === '' && (str_contains($group, 'SPARE') || $group === '')) {
                    if (str_contains($machineName, 'FFA')) {
                        $detailKey = 'ffa_moisture';
                    } elseif (str_contains($machineName, 'SPINTEST') && str_contains($machineName, 'COT')) {
                        $detailKey = 'spintest_cot';
                    } elseif (preg_match('/\bCST\s*1\b|\bCST1\b/', $machineName)) {
                        $detailKey = 'underflow_cst_1';
                    } elseif (preg_match('/\bCST\s*2\b|\bCST2\b/', $machineName)) {
                        $detailKey = 'underflow_cst_2';
                    } elseif (str_contains($machineName, 'FEED DECANTER') && str_contains($machineName, 'ALFA LAVAL')) {
                        $detailKey = str_contains($machineName, '2') ? 'feed_decanter_alfa_laval_2' : 'feed_decanter_alfa_laval_1';
                    } elseif (str_contains($machineName, 'FEED DECANTER') && str_contains($machineName, 'GEA')) {
                        $detailKey = 'feed_decanter_gea';
                    } elseif (str_contains($machineName, 'FEED DECANTER') && (str_contains($machineName, 'FLOTT') || str_contains($machineName, 'FLOTTWEG'))) {
                        $detailKey = 'feed_decanter_flottweg';
                    } elseif (str_contains($machineName, 'LIGHT PHASE') && str_contains($machineName, 'ALFA LAVAL')) {
                        $detailKey = str_contains($machineName, '2') ? 'light_phase_alfa_laval_2' : 'light_phase_alfa_laval_1';
                    } elseif (str_contains($machineName, 'LIGHT PHASE') && str_contains($machineName, 'GEA')) {
                        $detailKey = 'light_phase_gea';
                    } elseif (str_contains($machineName, 'LIGHT PHASE') && (str_contains($machineName, 'FLOTT') || str_contains($machineName, 'FLOTTWEG'))) {
                        $detailKey = 'light_phase_flottweg';
                    } elseif (preg_match('/\bSTERILIZER\s*(\d+)\b/', $machineName, $mnum)) {
                        $n = (int) ($mnum[1] ?? 0);
                        if ($n >= 1 && $n <= 8) {
                            $detailKey = 'sterilizer_' . $n;
                        }
                    } else {
                        $detailKey = $this->resolveOilFossPerformanceKey($machineName) ?: '';
                    }
                }

                if ($detailKey !== '') {
                    $detailKeysByMachine[$detailKey] = true;
                    $detailRowsByKey[$detailKey][] = $m;
                }
            }

            $expectedByColumn = [];
            foreach ($performanceColumns as $column) {
                $columnKey = (string) ($column['key'] ?? '');
                $columnDetailKeys = (array) ($column['detail_keys'] ?? []);

                if ($columnKey === '' || empty($columnDetailKeys)) {
                    continue;
                }

                $rowsForColumn = collect();
                foreach ($columnDetailKeys as $detailKey) {
                    foreach (($detailRowsByKey[$detailKey] ?? []) as $row) {
                        $rowsForColumn->push($row);
                    }
                }

                $expectedByColumn[$columnKey] = $this->calculateExpectedSamplesFromRowsWithConditions(
                    $proc,
                    $teamName,
                    $rowsForColumn,
                    self::SPINTEST_SAMPLING_INTERVAL_MINUTES
                );
            }

            // Store checked machines for this process
            $machineDetailKeysByProcess[$processId] = [
                'tanggal' => $tanggal,
                'office' => $office,
                'creator' => $creator,
                'team_name' => $teamName,
                'jam_mulai' => $jamMulai,
                'jam_akhir' => $jamAkhir,
                'detail_keys' => $detailKeysByMachine,
                'expected_by_column' => $expectedByColumn,
                'team_1_other_conditions' => (array) ($proc->team_1_other_conditions ?? []),
                'team_2_other_conditions' => (array) ($proc->team_2_other_conditions ?? []),
            ];
        }

        // Collect all analysis entries
        $analysisEntries = $this->collectAllSpintestAnalysisEntries($startDate, $endDate, $officeScope, $selectedOffice);

        if ($analysisEntries->isEmpty()) {
            return [];
        }

        $columnKeys = collect($performanceColumns)->pluck('key')->all();
        $detailKeyToColumnKey = [];
        foreach ($performanceColumns as $column) {
            foreach (($column['detail_keys'] ?? []) as $detailKey) {
                $detailKeyToColumnKey[$detailKey] = $column['key'];
            }
        }

        // Enrich analysis entries with team_name based on time overlap
        $enrichedEntries = $analysisEntries->map(function (array $entry) use ($spintestProcesses): array {
            $entry['team_name'] = '-';
            $entryTime = substr((string) ($entry['jam'] ?? ''), 0, 5);

            if ($entryTime !== '') {
                $entryTimeMinutes = $this->timeToMinutes($entryTime);

                foreach ($spintestProcesses as $proc) {
                    if (
                        optional($proc->process_date)->toDateString() !== $entry['tanggal']
                        || (string) ($proc->office ?? '-') !== $entry['office']
                    ) {
                        continue;
                    }

                    $inputTeam = (string) ($proc->input_team ?? 'Tim 1');

                    // Check if entry time falls within Tim 1 time range (only if Tim 1 was input or is default)
                    if ($inputTeam === 'Tim 1' || $inputTeam === '') {
                        $team1Start = substr((string) $proc->team_1_start_time, 0, 5);
                        $team1End = substr((string) $proc->team_1_end_time, 0, 5);

                        if ($team1Start !== '' && $team1Start !== '00:00' && $team1End !== '' && $team1End !== '00:00') {
                            $team1StartMin = $this->timeToMinutes($team1Start);
                            $team1EndMin = $this->timeToMinutes($team1End);

                            if ($team1StartMin <= $team1EndMin) {
                                if ($entryTimeMinutes >= $team1StartMin && $entryTimeMinutes <= $team1EndMin) {
                                    $entry['team_name'] = 'Tim 1';
                                    return $entry;
                                }
                            } else {
                                if ($entryTimeMinutes >= $team1StartMin || $entryTimeMinutes <= $team1EndMin) {
                                    $entry['team_name'] = 'Tim 1';
                                    return $entry;
                                }
                            }
                        }
                    }

                    // Check if entry time falls within Tim 2 time range (only if Tim 2 was input)
                    if ($inputTeam === 'Tim 2') {
                        $team2Start = substr((string) $proc->team_2_start_time, 0, 5);
                        $team2End = substr((string) $proc->team_2_end_time, 0, 5);

                        if ($team2Start !== '' && $team2Start !== '00:00' && $team2End !== '' && $team2End !== '00:00') {
                            $team2StartMin = $this->timeToMinutes($team2Start);
                            $team2EndMin = $this->timeToMinutes($team2End);

                            if ($team2StartMin <= $team2EndMin) {
                                if ($entryTimeMinutes >= $team2StartMin && $entryTimeMinutes <= $team2EndMin) {
                                    $entry['team_name'] = 'Tim 2';
                                    return $entry;
                                }
                            } else {
                                if ($entryTimeMinutes >= $team2StartMin || $entryTimeMinutes <= $team2EndMin) {
                                    $entry['team_name'] = 'Tim 2';
                                    return $entry;
                                }
                            }
                        }
                    }
                }
            }

            return $entry;
        })->values();

        return $enrichedEntries
            ->groupBy(fn(array $entry): string => $entry['tanggal'] . '|' . $entry['office'] . '|' . $entry['team_name'])
            ->map(function (Collection $entries) use ($machineDetailKeysByProcess, $columnKeys, $detailKeyToColumnKey, $performanceColumns): ?array {
                $firstEntry = $entries->first();
                $date = (string) $firstEntry['tanggal'];
                $office = (string) $firstEntry['office'];
                $teamName = (string) ($firstEntry['team_name'] ?? '-');

                // Ignore analysis rows that cannot be linked to a valid process team.
                if ($teamName === '-' || $teamName === '') {
                    return null;
                }
                $sampleBoyNames = $entries
                    ->pluck('created_by')
                    ->map(fn($name): string => trim((string) $name))
                    ->filter(fn(string $name): bool => $name !== '')
                    ->unique()
                    ->values();
                $sampleBoyText = $sampleBoyNames->isNotEmpty()
                    ? $sampleBoyNames->implode(', ')
                    : '-';

                // Find matching SpintestProsses
                // Match by tanggal, office, and team_name (not by creator, since process creator may differ from analysis entry creator)
                $matchingProc = null;
                foreach ($machineDetailKeysByProcess as $procId => $procInfo) {
                    if (
                        $procInfo['tanggal'] === $date
                        && $procInfo['office'] === $office
                        && $procInfo['team_name'] === $teamName
                    ) {
                        $matchingProc = $procInfo;
                        break;
                    }
                }

                if ($matchingProc === null) {
                    return null;
                }

                $jamMulai = $matchingProc['jam_mulai'] ?? '-';
                $jamAkhir = $matchingProc['jam_akhir'] ?? '-';
                $checkedDetailKeys = $matchingProc['detail_keys'] ?? [];
                $expectedByColumn = (array) ($matchingProc['expected_by_column'] ?? []);

                $totalHours = '-';
                if ($jamMulai !== '-' && $jamAkhir !== '-') {
                    $totalHours = $this->calculateTeamProductionHours($jamMulai, $jamAkhir, '', '') . ' jam';
                }

                // Filter entries that are within time range of process
                $filteredEntries = $entries;
                if ($jamMulai !== '-' && $jamAkhir !== '-') {
                    $filteredEntries = $entries->filter(function (array $entry) use ($jamMulai, $jamAkhir): bool {
                        $entryTime = substr((string) ($entry['jam'] ?? ''), 0, 5);
                        if ($entryTime === '') {
                            return false;
                        }

                        // Check if entry time is within range
                        $jamMulaiMinutes = $this->timeToMinutes($jamMulai);
                        $jamAkhirMinutes = $this->timeToMinutes($jamAkhir);
                        $entryTimeMinutes = $this->timeToMinutes($entryTime);

                        if ($jamMulaiMinutes <= $jamAkhirMinutes) {
                            // Normal case: no wrap around midnight
                            return $entryTimeMinutes >= $jamMulaiMinutes && $entryTimeMinutes <= $jamAkhirMinutes;
                        } else {
                            // Wrap around: e.g., 22:00 to 06:00
                            return $entryTimeMinutes >= $jamMulaiMinutes || $entryTimeMinutes <= $jamAkhirMinutes;
                        }
                    })->values();
                }

                // Count entries by detail key but deduplicate multiple machines recorded at the same time
                // so that entries sharing the same 'jam' for the same performance column count as 1.
                $detailCounts = array_fill_keys($columnKeys, 0);

                // Build bucket: jam -> set of columnKeys observed at that jam
                $jamBuckets = [];
                foreach ($filteredEntries as $entry) {
                    $detailKey = (string) ($entry['detail_key'] ?? '');
                    $jam = substr((string) ($entry['jam'] ?? ''), 0, 5);
                    $columnKey = $detailKeyToColumnKey[$detailKey] ?? null;

                    if ($detailKey === '' || $columnKey === null) {
                        continue;
                    }

                    // Only count detail keys that were checked in the process
                    if (!isset($checkedDetailKeys[$detailKey])) {
                        continue;
                    }

                    if ($jam === '') {
                        // fallback: count as its own unique bucket by using index
                        $jam = uniqid('t', true);
                    }

                    $jamBuckets[$jam][$columnKey] = true;
                }

                // For each jam bucket, increment each present column once
                foreach ($jamBuckets as $bucket) {
                    foreach (array_keys($bucket) as $col) {
                        if (array_key_exists($col, $detailCounts)) {
                            $detailCounts[$col]++;
                        }
                    }
                }

                // Calculate actual total and perf percentage.
                // Perf total is computed from all checked performance columns and capped at 100%.
                $actualTotal = array_sum($detailCounts);
                $totalExpectedAcross = 0;
                foreach ($performanceColumns as $column) {
                    $columnKey = (string) ($column['key'] ?? '');
                    if ($columnKey === 'perf_total') {
                        continue;
                    }

                    $columnDetailKeys = (array) ($column['detail_keys'] ?? []);
                    $isChecked = false;
                    foreach ($columnDetailKeys as $detailKey) {
                        if (isset($checkedDetailKeys[$detailKey])) {
                            $isChecked = true;
                            break;
                        }
                    }

                    if ($isChecked) {
                        $totalExpectedAcross += (int) ($expectedByColumn[$columnKey] ?? 0);
                    }
                }

                $perfPercentage = '-';
                if ($totalExpectedAcross > 0) {
                    $val = ($actualTotal / $totalExpectedAcross) * 100;
                    $val = min(100, $val);
                    $perfPercentage = number_format($val, 2) . '%';
                }

                // Convert counts to actual/expected strings
                // For checked machines: show "count/expected"
                // For unchecked machines: show "0/0"
                $detailStrings = [];
                foreach ($performanceColumns as $column) {
                    $columnKey = (string) $column['key'];
                    $columnDetailKeys = (array) ($column['detail_keys'] ?? []);
                    $isChecked = false;

                    foreach ($columnDetailKeys as $detailKey) {
                        if (isset($checkedDetailKeys[$detailKey])) {
                            $isChecked = true;
                            break;
                        }
                    }

                    if ($isChecked) {
                        $expectedForColumn = (int) ($expectedByColumn[$columnKey] ?? 0);
                        $detailStrings[$columnKey] = (string) ($detailCounts[$columnKey] . '/' . $expectedForColumn);
                    } else {
                        $detailStrings[$columnKey] = '0/0';
                    }
                }

                $detailStrings['perf_total'] = $perfPercentage;

                return [
                    'tanggal' => Carbon::parse($date)->format('d/m/Y'),
                    'tim' => $teamName,
                    'jam_mulai_proses' => $jamMulai,
                    'jam_akhir_proses' => $jamAkhir,
                    'total_hours' => $totalHours,
                    'nama_sample_boy' => $sampleBoyText,
                ] + $detailStrings;
            })
            ->filter()
            ->sortBy(fn(array $row): string => $row['tanggal'] . '|' . $row['tim'])
            ->values()
            ->all();
    }

    private function collectAllSpintestAnalysisEntries(Carbon $startDate, Carbon $endDate, ?string $officeScope, string $selectedOffice = 'all'): Collection
    {
        $entries = collect()
            ->concat($this->collectSpintestAnalysisEntries(
                FfaMoisture::query(),
                'Analisa Moisture & Spintes',
                'FFA dan Moisture',
                'jam',
                $startDate,
                $endDate,
                $officeScope,
                fn() => 'ffa_moisture'
            ))
            ->concat($this->collectSpintestAnalysisEntries(
                SpintestCot::query(),
                'Analisa Moisture & Spintes',
                'Spintest COT',
                'jam',
                $startDate,
                $endDate,
                $officeScope,
                fn() => 'spintest_cot'
            ))
            ->concat($this->collectSpintestAnalysisEntries(
                SpintestCst::query(),
                'Analisa Moisture & Spintes',
                'Underflow CST',
                'jam',
                $startDate,
                $endDate,
                $officeScope,
                function ($row): ?string {
                    $machineName = strtoupper(trim((string) ($row->machine_name ?? '')));

                    return match ($machineName) {
                        'CST1', 'CST 1' => 'underflow_cst_1',
                        'CST2', 'CST 2' => 'underflow_cst_2',
                        default => null,
                    };
                }
            ))
            ->concat($this->collectSpintestAnalysisEntries(
                SpintestFeedDecanter::query(),
                'Analisa Moisture & Spintes',
                'Feed Decanter',
                'jam',
                $startDate,
                $endDate,
                $officeScope,
                function ($row): ?string {
                    $machineName = strtoupper(trim((string) ($row->machine_name ?? '')));

                    return match ($machineName) {
                        'ALFA LAVAL 1' => 'feed_decanter_alfa_laval_1',
                        'ALFA LAVAL 2' => 'feed_decanter_alfa_laval_2',
                        'GEA', 'GEA1', 'GEA 1', 'GEA2', 'GEA 2' => 'feed_decanter_gea',
                        'FLOTTWEG' => 'feed_decanter_flottweg',
                        default => null,
                    };
                }
            ))
            ->concat($this->collectSpintestAnalysisEntries(
                SpintestLightPhase::query(),
                'Analisa Moisture & Spintes',
                'Light Phase',
                'jam',
                $startDate,
                $endDate,
                $officeScope,
                function ($row): ?string {
                    $machineName = strtoupper(trim((string) ($row->machine_name ?? '')));

                    return match ($machineName) {
                        'ALFA LAVAL 1' => 'light_phase_alfa_laval_1',
                        'ALFA LAVAL 2' => 'light_phase_alfa_laval_2',
                        'GEA', 'GEA1', 'GEA 1', 'GEA2', 'GEA 2' => 'light_phase_gea',
                        'FLOTTWEG' => 'light_phase_flottweg',
                        default => null,
                    };
                }
            ))
            ->concat($this->collectSpintestAnalysisEntries(
                AnalisaUsb::query(),
                'Lap Jangkos',
                'No Rebusan / Sterilizer 1-8',
                'jam',
                $startDate,
                $endDate,
                $officeScope,
                function ($row): ?string {
                    $number = (int) ($row->no_rebusan ?? 0);

                    return $number >= 1 && $number <= 8
                        ? 'sterilizer_' . $number
                        : null;
                }
            ));

        if (strtoupper(trim($selectedOffice)) !== 'SUN') {
            $entries = $entries->concat($this->collectSpintestAnalysisEntries(
                OilFoss::query(),
                'Oil Loss Foss',
                'Oil Loss Foss',
                'waktu',
                $startDate,
                $endDate,
                $officeScope,
                function ($row): ?string {
                    return $this->resolveOilFossPerformanceKey((string) ($row->machine_name ?? ''));
                }
            ));
        }

        return $entries;
    }

    private function timeToMinutes(string $time): int
    {
        if (strlen($time) < 5) {
            return 0;
        }
        $parts = explode(':', substr($time, 0, 5));
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        return $hours * 60 + $minutes;
    }

    private function roundTimeToHalfHourString(string $time): string
    {
        $value = trim($time);
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return now()->format('H:i');
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));

        return sprintf('%02d:%02d', $hour, $minute < 30 ? 0 : 30);
    }

    private function normalizeHalfHourTime(?string $time, ?string $fallback = null): string
    {
        $value = trim((string) $time);

        if ($value === '') {
            $value = $fallback ?? now()->format('H:i');
        }

        return $this->roundTimeToHalfHourString($value);
    }

    private function getProcessWindowsForDate(string $office, string $date, string $source = 'kernel'): array
    {
        $windows = [];
        $query = $source === 'spintest'
            ? SpintestProsses::query()->with('mesin')
            : KernelProsses::query()->with('mesin');

        $processRows = $query
            ->where('office', $office)
            ->whereDate('process_date', $date)
            ->get();

        foreach ($processRows as $processRow) {
            foreach (['1', '2'] as $teamSuffix) {
                $startField = 'team_' . $teamSuffix . '_start_time';
                $endField = 'team_' . $teamSuffix . '_end_time';
                $startLabel = substr((string) ($processRow->{$startField} ?? ''), 0, 5);
                $endLabel = substr((string) ($processRow->{$endField} ?? ''), 0, 5);

                $startMinutes = $this->timeToMinutes($startLabel);
                $endMinutes = $this->timeToMinutes($endLabel);

                if ($startLabel !== '' && $endLabel !== '' && $startLabel !== '00:00' && $endLabel !== '00:00' && $startMinutes < $endMinutes) {
                    $windows[] = [
                        'start' => $startMinutes,
                        'end' => $endMinutes,
                        'label' => $startLabel . '-' . $endLabel,
                    ];
                }
            }

            foreach ($processRow->mesin as $machine) {
                $startLabel = substr((string) ($machine->production_start_time ?? ''), 0, 5);
                $endLabel = substr((string) ($machine->production_end_time ?? ''), 0, 5);
                $startMinutes = $this->timeToMinutes($startLabel);
                $endMinutes = $this->timeToMinutes($endLabel);

                if ($startLabel === '' || $endLabel === '' || $startLabel === '00:00' || $endLabel === '00:00') {
                    continue;
                }

                $windows[] = [
                    'start' => $startMinutes,
                    'end' => $endMinutes,
                    'label' => $startLabel . '-' . $endLabel,
                ];
            }
        }

        return $windows;
    }

    private function assertYbsProcessTimeMatches(string $office, string $date, string $time, string $moduleName, string $source = 'kernel'): void
    {
        $windows = $this->getProcessWindowsForDate($office, $date, $source);

        if (empty($windows)) {
            throw ValidationException::withMessages([
                'tanggal' => "Warning: Informasi proses mesin untuk tanggal {$date} office {$office} belum tersedia. Input {$moduleName} belum bisa disimpan.",
            ]);
        }

        $normalizedTime = $this->normalizeHalfHourTime($time);
        $currentMinutes = $this->timeToMinutes($normalizedTime);

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
            'jam' => "Warning: Jam saat submit ({$normalizedTime}) di luar jam mesin hidup untuk {$moduleName}. Jam yang diizinkan: {$availableWindows}.",
        ]);
    }

    private function collectSpintestAnalysisEntries(Builder $query, string $moduleGroup, string $moduleName, string $timeField, Carbon $startDate, Carbon $endDate, ?string $officeScope, ?callable $detailResolver = null): Collection
    {
        return $query
            ->with('user')
            ->when($officeScope !== null, fn($builder) => $this->applyUserOfficeScope($builder, $officeScope))
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('tanggal')
            ->orderBy($timeField)
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($moduleGroup, $moduleName, $timeField, $detailResolver): ?array {
                $detailKey = $detailResolver !== null ? $detailResolver($row) : $moduleName;
                $detailKey = is_string($detailKey) ? trim($detailKey) : '';

                if ($detailKey === '') {
                    return null;
                }

                return [
                    'tanggal' => Carbon::parse($row->tanggal)->toDateString(),
                    'jam' => substr((string) ($row->{$timeField} ?? ''), 0, 5),
                    'office' => (string) ($row->office ?? '-'),
                    'created_by' => trim((string) ($row->created_by ?: ($row->user?->name ?? '-'))),
                    'module_group' => $moduleGroup,
                    'module_name' => $moduleName,
                    'detail_key' => $detailKey,
                ];
            })
            ->filter()
            ->values();
    }

    private function getSpintestMachineGuide(?string $office = null): array
    {
        $guide = [
            [
                'title' => 'Analisa Moisture & Spintes',
                'items' => [
                    ['label' => 'FFA dan Moisture', 'machines' => ['FFA dan Moisture']],
                    ['label' => 'Spintest COT', 'machines' => ['Spintest COT']],
                    ['label' => 'Underflow CST', 'machines' => ['CST 1', 'CST 2']],
                    ['label' => 'Feed Decanter', 'machines' => ['Alfa Laval 1', 'Alfa Laval 2', 'GEA', 'Flottweg']],
                    ['label' => 'Light Phase', 'machines' => ['Alfa Laval 1', 'Alfa Laval 2', 'GEA', 'Flottweg']],
                ],
            ],
            [
                'title' => 'Lap Jangkos',
                'items' => [
                    ['label' => 'No Rebusan / Sterillizer', 'machines' => ['Sterilizer 1', 'Sterilizer 2', 'Sterilizer 3', 'Sterilizer 4', 'Sterilizer 5', 'Sterilizer 6', 'Sterilizer 7', 'Sterilizer 8']],
                ],
            ],
            [
                'title' => 'Oil Loss Foss',
                'items' => [
                    ['label' => 'COT', 'machines' => ['COT IN', 'COT2']],
                    ['label' => 'CST', 'machines' => ['CST']],
                    ['label' => 'FD', 'machines' => ['FD1', 'FD2', 'FD3', 'FD4']],
                    ['label' => 'HP', 'machines' => ['HP1', 'HP2', 'HP3', 'HP4']],
                    ['label' => 'SD', 'machines' => ['SD1', 'SD2', 'SD3', 'SD4']],
                    ['label' => 'HPL', 'machines' => ['HPL1', 'HPL2', 'HPL3']],
                    ['label' => 'FE', 'machines' => ['FE']],
                    ['label' => 'FBP', 'machines' => ['FBP1', 'FBP2', 'FBP3', 'FBP4', 'FBP5']],
                    ['label' => 'FP', 'machines' => ['FP1', 'FP2', 'FP3', 'FP4', 'FP5', 'FP6', 'FP7', 'FP8', 'FP9']],
                ],
            ],
        ];

        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? '')));
        if ($officeCode === 'SUN') {
            $guide = array_values(array_filter($guide, fn(array $section): bool => (($section['title'] ?? '') !== 'Oil Loss Foss')));
        }

        return $guide;
    }

    private function getSpintestPerformanceColumns(string $office = 'all'): array
    {
        $officeCode = strtoupper(trim($office));
        if ($officeCode === 'SUN') {
            return self::SPINTEST_PERFORMANCE_COLUMNS_SUN;
        }

        if ($officeCode !== 'SUN') {
            return self::SPINTEST_PERFORMANCE_COLUMNS;
        }

        return self::SPINTEST_PERFORMANCE_COLUMNS;
    }

    private function resolveSpintestVisibleTeam(SpintestProsses $record): ?string
    {
        $team = (string) $record->input_team;

        return in_array($team, self::TEAM_OPTIONS, true) ? $team : null;
    }

    private function buildSpintestTeamRow(SpintestProsses $record, string $teamName): array
    {
        $isTeamOne = $teamName === 'Tim 1';

        return [
            'process_date' => optional($record->process_date)->format('d/m/Y'),
            'process_start' => substr((string) ($isTeamOne ? $record->team_1_start_time : $record->team_2_start_time), 0, 5),
            'process_end' => substr((string) ($isTeamOne ? $record->team_1_end_time : $record->team_2_end_time), 0, 5),
            'team_name' => $teamName,
            'members' => $isTeamOne ? ($record->team_1_members ?? []) : ($record->team_2_members ?? []),
            'other_conditions' => $this->getSpintestOtherConditionsForTeam($record, $teamName),
        ];
    }

    private function getSpintestOtherConditionsForTeam(SpintestProsses $record, string $teamName): array
    {
        $rows = $teamName === 'Tim 1'
            ? (array) ($record->team_1_other_conditions ?? [])
            : (array) ($record->team_2_other_conditions ?? []);

        return collect($rows)
            ->filter(fn($row): bool => is_array($row))
            ->map(function (array $row): array {
                $start = trim((string) ($row['start_time'] ?? ''));
                $end = trim((string) ($row['end_time'] ?? ''));
                $minutes = ($this->isValidTime($start) && $this->isValidTime($end))
                    ? $this->minutesBetween($start, $end)
                    : 0;

                return [
                    'reason' => trim((string) ($row['reason'] ?? '')),
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration_minutes' => $minutes,
                    'duration_hours' => round($minutes / 60, 2),
                ];
            })
            ->filter(fn(array $row): bool => $row['reason'] !== '' && $row['start_time'] !== '' && $row['end_time'] !== '')
            ->values()
            ->all();
    }

    private function buildSpintestMachineDetail(SpintestProsses $record, ?string $teamName): array
    {
        $teamName = $teamName ?? 'Tim 1';
        $machines = $record->mesin
            ->where('team_name', $teamName)
            ->values();

        $machineGroups = $machines
            ->groupBy(fn(SpintestMesin $machine): string => strtoupper(trim((string) $machine->machine_name)))
            ->map(function (Collection $rows, string $machineName) use ($record, $teamName): array {
                $rows = $rows->values();
                $rowDetails = $rows->map(function (SpintestMesin $machine) use ($record, $teamName): array {
                    $durationMinutes = max(
                        0,
                        $this->minutesBetween(
                            substr((string) $machine->production_start_time, 0, 5),
                            substr((string) $machine->production_end_time, 0, 5)
                        )
                    );

                    return [
                        'machine' => $machine,
                        'status' => $machine->is_spare_input ? 'Spare' : 'Operasi',
                        'duration_minutes' => $durationMinutes,
                        'expected_samples' => $this->calculateExpectedSamplesFromRowsWithConditions(
                            $record,
                            $teamName,
                            collect([$machine]),
                            self::SPINTEST_SAMPLING_INTERVAL_MINUTES
                        ),
                    ];
                })->values();

                return [
                    'machine_name' => $rows->first()?->machine_name ?? $machineName,
                    'rows' => $rowDetails,
                    'total_minutes' => (int) $rowDetails->sum('duration_minutes'),
                    'expected_samples' => $this->calculateExpectedSamplesFromRowsWithConditions(
                        $record,
                        $teamName,
                        $rows,
                        self::SPINTEST_SAMPLING_INTERVAL_MINUTES
                    ),
                ];
            })
            ->values();

        return [
            'team_name' => $teamName,
            'process_date' => optional($record->process_date)->format('d/m/Y'),
            'process_start' => $teamName === 'Tim 1'
                ? substr((string) $record->team_1_start_time, 0, 5)
                : substr((string) $record->team_2_start_time, 0, 5),
            'process_end' => $teamName === 'Tim 1'
                ? substr((string) $record->team_1_end_time, 0, 5)
                : substr((string) $record->team_2_end_time, 0, 5),
            'members' => $teamName === 'Tim 1' ? ($record->team_1_members ?? []) : ($record->team_2_members ?? []),
            'other_conditions' => $this->getSpintestOtherConditionsForTeam($record, $teamName),
            'machine_groups' => $machineGroups,
            'total_samples' => (int) $machineGroups->sum('expected_samples'),
        ];
    }

    private function resolveOilFossPerformanceKey(string $machineName): ?string
    {
        $normalized = strtoupper(str_replace([' ', '-', '_'], '', trim($machineName)));

        return match ($normalized) {
            'COTIN' => 'cot_in',
            'COT2' => 'cot_2',
            'CST' => 'cst',
            'FD1' => 'fd1',
            'FD2' => 'fd2',
            'FD3' => 'fd3',
            'FD4' => 'fd4',
            'HP1' => 'hp1',
            'HP2' => 'hp2',
            'HP3' => 'hp3',
            'HP4' => 'hp4',
            'SD1' => 'sd1',
            'SD2' => 'sd2',
            'SD3' => 'sd3',
            'SD4' => 'sd4',
            'HPL1' => 'hpl1',
            'HPL2' => 'hpl2',
            'HPL3' => 'hpl3',
            'FE' => 'fe',
            'FBP1' => 'fbp1',
            'FBP2' => 'fbp2',
            'FBP3' => 'fbp3',
            'FBP4' => 'fbp4',
            'FBP5' => 'fbp5',
            'FP1' => 'fp1',
            'FP2' => 'fp2',
            'FP3' => 'fp3',
            'FP4' => 'fp4',
            'FP5' => 'fp5',
            'FP6' => 'fp6',
            'FP7' => 'fp7',
            'FP8' => 'fp8',
            'FP9' => 'fp9',
            default => null,
        };
    }

    public function machineDetail(KernelProsses $kernelProsses)
    {
        $allMachines = $kernelProsses->mesin()
            ->orderBy('team_name')
            ->orderBy('machine_group')
            ->orderBy('machine_name')
            ->get();

        $teamsToShow = in_array((string) $kernelProsses->input_team, self::TEAM_OPTIONS, true)
            ? [(string) $kernelProsses->input_team]
            : self::TEAM_OPTIONS;

        $office = !empty($kernelProsses->office) ? (string) $kernelProsses->office : 'YBS';

        $groupedMachinesByTeam = collect($teamsToShow)
            ->mapWithKeys(function (string $teamName) use ($allMachines, $office, $kernelProsses): array {
                $teamMachines = $allMachines
                    ->where('team_name', $teamName)
                    ->values();

                $teamMainMachines = $teamMachines
                    ->where('is_spare_input', false)
                    ->values();

                $teamSparesByMachineName = $teamMachines
                    ->where('is_spare_input', true)
                    ->groupBy(fn(KernelMesin $machine): string => (string) $machine->machine_name);

                $teamSpareRows = $teamMachines
                    ->where('is_spare_input', true)
                    ->map(function (KernelMesin $spare): array {
                        return [
                            'machine' => $spare,
                            'total_minutes' => $this->resolveMachineTotalMinutes($spare),
                        ];
                    })
                    ->values();

                $groupedMachines = $teamMainMachines
                    ->groupBy(fn(KernelMesin $machine): string => (string) $machine->machine_group)
                    ->map(function ($machines) use ($teamSparesByMachineName, $office, $teamMachines, $kernelProsses, $teamName) {
                        return $machines->map(function (KernelMesin $machine) use ($teamSparesByMachineName, $office, $teamMachines, $kernelProsses, $teamName): array {
                            $totalMinutes = $this->resolveMachineTotalMinutes($machine);
                            $intervalMinutes = $this->resolveMachineIntervalMinutes((string) $machine->machine_name, $office);
                            $machineRows = $teamMachines
                                ->where('machine_name', $machine->machine_name)
                                ->values();
                            $expectedSamples = $this->calculateExpectedSamplesFromRowsWithConditions(
                                $kernelProsses,
                                $teamName,
                                $machineRows,
                                $intervalMinutes
                            );

                            return [
                                'main' => $machine,
                                'spares' => $teamSparesByMachineName->get($machine->machine_name, collect()),
                                'total_minutes' => $totalMinutes,
                                'interval_minutes' => $intervalMinutes,
                                'expected_samples' => $expectedSamples,
                            ];
                        });
                    });

                $orphanSpares = $teamMachines
                    ->where('is_spare_input', true)
                    ->filter(function (KernelMesin $spare) use ($teamMainMachines): bool {
                        return !$teamMainMachines->contains(fn(KernelMesin $machine): bool => $machine->machine_name === $spare->machine_name);
                    })
                    ->map(function (KernelMesin $spare): array {
                        return [
                            'machine' => $spare,
                            'total_minutes' => $this->resolveMachineTotalMinutes($spare),
                        ];
                    })
                    ->values();

                $expectedSamplesTotal = (int) $groupedMachines
                    ->flatten(1)
                    ->sum(fn(array $item): int => (int) ($item['expected_samples'] ?? 0));

                return [
                    $teamName => [
                        'groups' => $groupedMachines,
                        'spare_rows' => $teamSpareRows,
                        'orphans' => $orphanSpares,
                        'expected_samples_total' => $expectedSamplesTotal,
                        'other_conditions' => $this->getTeamOtherConditions($kernelProsses, $teamName),
                    ],
                ];
            });

        return view('process.machine-detail', [
            'record' => $kernelProsses,
            'groupedMachinesByTeam' => $groupedMachinesByTeam,
        ]);
    }

    public function editMachines(KernelProsses $kernelProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_machine_data']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jam proses mesin.');
        }

        $kernelProsses->load('mesin');

        $visibleTeam = in_array((string) $kernelProsses->input_team, self::TEAM_OPTIONS, true)
            ? (string) $kernelProsses->input_team
            : null;

        $selectedMainMachines = [];
        $spareRowsByTeam = [
            'Tim 1' => [],
            'Tim 2' => [],
        ];
        $otherConditionsByTeam = [
            'Tim 1' => collect((array) ($kernelProsses->team_1_other_conditions ?? []))
                ->filter(fn($row): bool => is_array($row))
                ->map(fn($row): array => [
                    'team_name' => 'Tim 1',
                    'reason' => trim((string) ($row['reason'] ?? '')),
                    'start_time' => trim((string) ($row['start_time'] ?? '')),
                    'end_time' => trim((string) ($row['end_time'] ?? '')),
                ])
                ->values()
                ->all(),
            'Tim 2' => collect((array) ($kernelProsses->team_2_other_conditions ?? []))
                ->filter(fn($row): bool => is_array($row))
                ->map(fn($row): array => [
                    'team_name' => 'Tim 2',
                    'reason' => trim((string) ($row['reason'] ?? '')),
                    'start_time' => trim((string) ($row['start_time'] ?? '')),
                    'end_time' => trim((string) ($row['end_time'] ?? '')),
                ])
                ->values()
                ->all(),
        ];

        foreach ($kernelProsses->mesin as $machine) {
            $teamName = (string) $machine->team_name;
            if (!in_array($teamName, self::TEAM_OPTIONS, true)) {
                continue;
            }

            if ($machine->is_spare_input) {
                $spareRowsByTeam[$teamName][] = [
                    'team_name' => $teamName,
                    'machine_name' => (string) $machine->machine_name,
                    'start_time' => substr((string) $machine->production_start_time, 0, 5),
                    'end_time' => substr((string) $machine->production_end_time, 0, 5),
                ];
                continue;
            }

            $key = $teamName . '|' . strtoupper(trim((string) $machine->machine_name));
            if (!isset($selectedMainMachines[$key])) {
                $selectedMainMachines[$key] = [
                    'start_time' => substr((string) $machine->production_start_time, 0, 5),
                    'end_time' => substr((string) $machine->production_end_time, 0, 5),
                ];
            }
        }

        foreach (self::TEAM_OPTIONS as $teamName) {
            if (empty($spareRowsByTeam[$teamName])) {
                $spareRowsByTeam[$teamName] = [
                    [
                        'team_name' => $teamName,
                        'machine_name' => '',
                        'start_time' => '',
                        'end_time' => '',
                    ]
                ];
            }

            if (empty($otherConditionsByTeam[$teamName])) {
                $otherConditionsByTeam[$teamName] = [
                    [
                        'team_name' => $teamName,
                        'reason' => '',
                        'start_time' => '',
                        'end_time' => '',
                    ]
                ];
            }
        }

        $machineGroups = $this->getMachineGroupsForOffice($kernelProsses->office ?: null);
        $machineOptions = collect($machineGroups)->flatten()->values()->all();

        return view('process.edit-machines', [
            'record' => $kernelProsses,
            'machineGroups' => $machineGroups,
            'selectedMainMachines' => $selectedMainMachines,
            'spareRowsByTeam' => $spareRowsByTeam,
            'otherConditionsByTeam' => $otherConditionsByTeam,
            'machineOptions' => $machineOptions,
            'visibleTeam' => $visibleTeam,
        ]);
    }

    public function updateMachines(Request $request, KernelProsses $kernelProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_machine_data']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah jam proses mesin.');
        }

        $validated = $request->validate([
            'machines' => ['nullable', 'array'],
            'machines.*.selected' => ['nullable'],
            'machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'machines.*.machine_group' => ['nullable', 'string', 'max:120'],
            'machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'machines.*.end_time' => ['nullable', 'date_format:H:i'],
            'spare_machines' => ['nullable', 'array'],
            'spare_machines.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'spare_machines.*.machine_name' => ['nullable', 'string', 'max:150'],
            'spare_machines.*.start_time' => ['nullable', 'date_format:H:i'],
            'spare_machines.*.end_time' => ['nullable', 'date_format:H:i'],

            'other_conditions' => ['nullable', 'array'],
            'other_conditions.*.team_name' => ['nullable', 'string', 'in:Tim 1,Tim 2'],
            'other_conditions.*.reason' => ['nullable', 'string', 'max:255', 'required_with:other_conditions.*.start_time,other_conditions.*.end_time'],
            'other_conditions.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.end_time'],
            'other_conditions.*.end_time' => ['nullable', 'date_format:H:i', 'required_with:other_conditions.*.reason,other_conditions.*.start_time'],
        ]);

        $otherConditionsByTeam = $this->extractOtherConditionsPayload($request);

        $machinePayload = $this->extractMachinePayload($request, null, $kernelProsses->office ?: null);

        $kernelProsses->mesin()->delete();
        if (!empty($machinePayload)) {
            $kernelProsses->mesin()->createMany($machinePayload);
        }

        $team1OtherConditions = $this->requestContainsTeamConditions($request, 'Tim 1')
            ? ($otherConditionsByTeam['Tim 1'] ?? [])
            : (array) ($kernelProsses->team_1_other_conditions ?? []);

        $team2OtherConditions = $this->requestContainsTeamConditions($request, 'Tim 2')
            ? ($otherConditionsByTeam['Tim 2'] ?? [])
            : (array) ($kernelProsses->team_2_other_conditions ?? []);

        $kernelProsses->update([
            'team_1_other_conditions' => $team1OtherConditions,
            'team_2_other_conditions' => $team2OtherConditions,
        ]);

        return redirect()
            ->route('process.index')
            ->with('success', 'Data mesin berhasil diperbarui.');
    }

    public function destroyMachines(KernelProsses $kernelProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_machine_data']) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus jam proses mesin.');
        }

        $deletedCount = $kernelProsses->mesin()->count();
        $kernelProsses->mesin()->delete();

        return redirect()
            ->route('process.index')
            ->with('success', $deletedCount > 0
                ? "Data mesin berhasil dihapus ({$deletedCount} item)."
                : 'Tidak ada data mesin untuk dihapus.');
    }

    public function updateBoth(Request $request, KernelProsses $kernelProsses)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_team_meta']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah checklist tim dan jam shift.');
        }

        $teamMemberRules = $this->buildTeamMemberValidationRules();
        $validated = $request->validate([
            'process_date' => ['required', 'date'],

            'team_1_start_time' => ['required', 'date_format:H:i'],
            'team_1_end_time' => ['required', 'date_format:H:i'],
            'team_1_start_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_1_end_downtime'],
            'team_1_end_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_1_start_downtime'],
            'team_1_members' => ['nullable', 'array'],
            'team_1_members.*' => $teamMemberRules,

            'team_2_start_time' => ['required', 'date_format:H:i'],
            'team_2_end_time' => ['required', 'date_format:H:i'],
            'team_2_start_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_2_end_downtime'],
            'team_2_end_downtime' => ['nullable', 'date_format:H:i', 'required_with:team_2_start_downtime'],
            'team_2_members' => ['nullable', 'array'],
            'team_2_members.*' => $teamMemberRules,
        ]);

        $conflicts = array_intersect($validated['team_1_members'] ?? [], $validated['team_2_members'] ?? []);
        if (!empty($conflicts)) {
            return back()
                ->withInput()
                ->withErrors([
                    'team_2_members' => 'Nama yang sama tidak boleh dipilih di Tim 1 dan Tim 2: ' . implode(', ', $conflicts),
                ]);
        }

        $kernelProsses->update([
            'process_date' => $validated['process_date'],
            'team_1_start_time' => $validated['team_1_start_time'],
            'team_1_end_time' => $validated['team_1_end_time'],
            'team_1_start_downtime' => $validated['team_1_start_downtime'] ?? null,
            'team_1_end_downtime' => $validated['team_1_end_downtime'] ?? null,
            'team_1_members' => $validated['team_1_members'] ?? [],
            'team_2_start_time' => $validated['team_2_start_time'],
            'team_2_end_time' => $validated['team_2_end_time'],
            'team_2_start_downtime' => $validated['team_2_start_downtime'] ?? null,
            'team_2_end_downtime' => $validated['team_2_end_downtime'] ?? null,
            'team_2_members' => $validated['team_2_members'] ?? [],
        ]);

        return redirect()
            ->route('process.index')
            ->with('success', 'Informasi proses berhasil diperbarui.');
    }

    public function editOld(KernelProsses $kernelProsses, int $team)
    {
        $this->ensureValidTeam($team);
        $teamMembers = $this->getTeamMembersByOffice();

        $otherMembers = $team === 1
            ? ($kernelProsses->team_2_members ?? [])
            : ($kernelProsses->team_1_members ?? []);

        $teamData = $team === 1
            ? [
                'start_time' => substr((string) $kernelProsses->team_1_start_time, 0, 5),
                'end_time' => substr((string) $kernelProsses->team_1_end_time, 0, 5),
                'start_downtime' => substr((string) $kernelProsses->team_1_start_downtime, 0, 5),
                'end_downtime' => substr((string) $kernelProsses->team_1_end_downtime, 0, 5),
                'members' => $kernelProsses->team_1_members ?? [],
            ]
            : [
                'start_time' => substr((string) $kernelProsses->team_2_start_time, 0, 5),
                'end_time' => substr((string) $kernelProsses->team_2_end_time, 0, 5),
                'start_downtime' => substr((string) $kernelProsses->team_2_start_downtime, 0, 5),
                'end_downtime' => substr((string) $kernelProsses->team_2_end_downtime, 0, 5),
                'members' => $kernelProsses->team_2_members ?? [],
            ];

        return view('process.edit', [
            'record' => $kernelProsses,
            'team' => $team,
            'teamLabel' => $team === 1 ? 'Tim 1' : 'Tim 2',
            'teamMembers' => $teamMembers,
            'teamData' => $teamData,
            'otherMembers' => $otherMembers,
        ]);
    }

    public function update(Request $request, KernelProsses $kernelProsses, int $team)
    {
        if (!$this->resolveProcessRoleFlags()['can_manage_team_meta']) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah checklist tim dan jam shift.');
        }

        $this->ensureValidTeam($team);
        $teamMemberRules = $this->buildTeamMemberValidationRules();

        $validated = $request->validate([
            'process_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'start_downtime' => ['nullable', 'date_format:H:i', 'required_with:end_downtime'],
            'end_downtime' => ['nullable', 'date_format:H:i', 'required_with:start_downtime'],
            'members' => ['nullable', 'array'],
            'members.*' => $teamMemberRules,
        ]);

        $selectedMembers = $validated['members'] ?? [];
        $otherMembers = $team === 1
            ? ($kernelProsses->team_2_members ?? [])
            : ($kernelProsses->team_1_members ?? []);

        $conflicts = array_intersect($selectedMembers, $otherMembers);
        if (!empty($conflicts)) {
            return back()
                ->withInput()
                ->withErrors([
                    'members' => 'Nama yang sudah dipakai tim lain tidak bisa dipilih: ' . implode(', ', $conflicts),
                ]);
        }

        $payload = ['process_date' => $validated['process_date']];

        if ($team === 1) {
            $payload = array_merge($payload, [
                'team_1_start_time' => $validated['start_time'],
                'team_1_end_time' => $validated['end_time'],
                'team_1_start_downtime' => $validated['start_downtime'] ?? null,
                'team_1_end_downtime' => $validated['end_downtime'] ?? null,
                'team_1_members' => $selectedMembers,
            ]);
        } else {
            $payload = array_merge($payload, [
                'team_2_start_time' => $validated['start_time'],
                'team_2_end_time' => $validated['end_time'],
                'team_2_start_downtime' => $validated['start_downtime'] ?? null,
                'team_2_end_downtime' => $validated['end_downtime'] ?? null,
                'team_2_members' => $selectedMembers,
            ]);
        }

        $kernelProsses->update($payload);

        return redirect()
            ->route('process.index')
            ->with('success', 'Data proses berhasil diperbarui.');
    }

    private function buildTeamRow(KernelProsses $record, string $teamName): array
    {
        $isTeamOne = $teamName === 'Tim 1';

        $processStart = (string) ($isTeamOne ? $record->team_1_start_time : $record->team_2_start_time);
        $processEnd = (string) ($isTeamOne ? $record->team_1_end_time : $record->team_2_end_time);
        $downtimeStart = (string) ($isTeamOne ? $record->team_1_start_downtime : $record->team_2_start_downtime);
        $downtimeEnd = (string) ($isTeamOne ? $record->team_1_end_downtime : $record->team_2_end_downtime);

        $totalProductionHours = $this->calculateTeamProductionHours(
            $processStart,
            $processEnd,
            $downtimeStart,
            $downtimeEnd
        );

        return [
            'process_date' => optional($record->process_date)->format('d/m/Y'),
            'process_start' => substr($processStart, 0, 5),
            'process_end' => substr($processEnd, 0, 5),
            'downtime_start' => substr($downtimeStart, 0, 5),
            'downtime_end' => substr($downtimeEnd, 0, 5),
            'production_hours' => $totalProductionHours . ' jam',
            'team_name' => $teamName,
            'members' => $isTeamOne ? ($record->team_1_members ?? []) : ($record->team_2_members ?? []),
        ];
    }

    private function buildPerformanceRow(KernelProsses $record, string $teamName, array $members, Collection $teamMachines, string $selectedOffice): array
    {
        $isTeamOne = $teamName === 'Tim 1';
        $mainMachines = $teamMachines
            ->where('is_spare_input', false)
            ->values();
        if ($mainMachines->isEmpty()) {
            $mainMachines = $teamMachines->values();
        }

        $machinesForPerformance = $teamMachines->values();
        if ($machinesForPerformance->isEmpty()) {
            $machinesForPerformance = $mainMachines;
        }

        $fallbackProcessStart = substr((string) ($isTeamOne ? $record->team_1_start_time : $record->team_2_start_time), 0, 5);
        $fallbackProcessEnd = substr((string) ($isTeamOne ? $record->team_1_end_time : $record->team_2_end_time), 0, 5);
        $machineWindow = $this->resolvePerformanceWindowFromMachines($machinesForPerformance, $fallbackProcessStart, $fallbackProcessEnd);
        $processStart = $machineWindow['start'];
        $processEnd = $machineWindow['end'];
        $downtimeStart = substr((string) ($isTeamOne ? $record->team_1_start_downtime : $record->team_2_start_downtime), 0, 5);
        $downtimeEnd = substr((string) ($isTeamOne ? $record->team_1_end_downtime : $record->team_2_end_downtime), 0, 5);

        $effectiveHours = $this->calculateTeamProductionHours($processStart, $processEnd, '', '');

        // Use actual office from record if available, fallback to selectedOffice filter
        $office = $selectedOffice === 'all' ? 'YBS' : $selectedOffice;
        if (!empty($record->office)) {
            $office = (string) $record->office;
        }

        $expectedByGroup = $this->buildExpectedSamplesByGroup($record, $teamName, $machinesForPerformance, $office);
        $machineWindowsByCode = $this->buildMachineWindowsByCode($machinesForPerformance);

        $actualBundle = $this->buildActualSamplesBundle(
            (string) optional($record->process_date)->format('Y-m-d'),
            $office === 'all' ? '' : $office,
            $machineWindowsByCode,
            []
        );
        $actualByGroup = $actualBundle['group'];

        $spintestColumns = array_values(array_filter(
            $this->getSpintestPerformanceColumns($office),
            fn(array $column): bool => (string) ($column['key'] ?? '') !== 'perf_total'
        ));

        $spintestExpectedByGroup = $this->buildExpectedSamplesBySpintestColumns(
            $record,
            $teamName,
            $machinesForPerformance,
            $spintestColumns
        );
        $spintestActualByGroup = $this->buildActualSamplesBySpintestColumns(
            (string) optional($record->process_date)->format('Y-m-d'),
            $office,
            $machineWindowsByCode,
            [],
            $spintestColumns
        );

        foreach ($spintestExpectedByGroup as $groupKey => $expectedValue) {
            $expectedByGroup[$groupKey] = (int) $expectedValue;
            $actualByGroup[$groupKey] = (int) ($spintestActualByGroup[$groupKey] ?? 0);
        }

        $perfActualTotal = 0;
        $perfExpectedTotal = 0;
        $performanceKeys = array_values(array_unique(array_merge(
            array_keys($this->getIntervalMinutesForOffice($office)),
            array_map(fn(array $column): string => (string) ($column['key'] ?? ''), $spintestColumns)
        )));

        foreach ($performanceKeys as $groupKey) {
            if ($groupKey === '') {
                continue;
            }

            $perfActualTotal += (int) ($actualByGroup[$groupKey] ?? 0);
            $perfExpectedTotal += (int) ($expectedByGroup[$groupKey] ?? 0);
        }

        $perfTotal = '-';
        if ($perfExpectedTotal > 0) {
            $perfPercentage = ($perfActualTotal / $perfExpectedTotal) * 100;
            $perfPercentage = min(100, $perfPercentage);
            $perfTotal = number_format($perfPercentage, 2) . '%';
        }

        return [
            'tanggal' => optional($record->process_date)->format('d/m/Y'),
            'team_name' => $teamName,
            'jam_mulai' => $processStart,
            'jam_akhir' => $processEnd,
            'downtime_awal' => $downtimeStart !== '' ? $downtimeStart : '-',
            'downtime_akhir' => $downtimeEnd !== '' ? $downtimeEnd : '-',
            'total_hours' => $effectiveHours . ' jam',
            'nama_sampel_boy' => !empty($members) ? implode(', ', $members) : '-',
            'fibre_cyclone' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'fibre_cyclone'),
            'ltds' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'ltds'),
            'claybath_wet_shell' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'claybath_wet_shell'),
            'inlet_kernel_silo' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'inlet_kernel_silo'),
            'outlet_kernel_silo' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'outlet_kernel_silo'),
            'press' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'press'),
            'eficiency' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'eficiency'),
            'destoner' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'destoner'),
            'ffa_moisture' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'ffa_moisture'),
            'spintest_cot' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'spintest_cot'),
            'underflow_cst' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'underflow_cst'),
            'feed_decanter' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'feed_decanter'),
            'light_phase' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'light_phase'),
            'sterilizer' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'sterilizer'),
            'cot' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'cot'),
            'cst' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'cst'),
            'fd' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'fd'),
            'hp' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'hp'),
            'sd' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'sd'),
            'hpl' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'hpl'),
            'fe' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'fe'),
            'fbp' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'fbp'),
            'fp' => $this->formatActualExpected($actualByGroup, $expectedByGroup, 'fp'),
            'perf_total' => $perfTotal,
        ];
    }

    private function resolvePerformanceWindowFromMachines(Collection $machines, string $fallbackStart, string $fallbackEnd): array
    {
        $ranges = [];

        foreach ($machines as $machine) {
            $start = $this->timeToMinutes(substr((string) ($machine->production_start_time ?? ''), 0, 5));
            $end = $this->timeToMinutes(substr((string) ($machine->production_end_time ?? ''), 0, 5));

            if ($start === null || $end === null) {
                continue;
            }

            if ($end <= $start) {
                $end += 24 * 60;
            }

            $ranges[] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        if (empty($ranges)) {
            return [
                'start' => $fallbackStart,
                'end' => $fallbackEnd,
            ];
        }

        $start = (int) min(array_column($ranges, 'start'));
        $end = (int) max(array_column($ranges, 'end'));

        return [
            'start' => $this->minutesToTimeLabel($start),
            'end' => $this->minutesToTimeLabel($end),
        ];
    }

    private function buildExpectedSamplesBySpintestColumns(
        KernelProsses $record,
        string $teamName,
        Collection $machines,
        array $spintestColumns
    ): array {
        $expected = [];

        foreach ($spintestColumns as $column) {
            $columnKey = (string) ($column['key'] ?? '');
            $detailKeys = (array) ($column['detail_keys'] ?? []);

            if ($columnKey === '' || empty($detailKeys)) {
                continue;
            }

            $rowsForColumn = $machines
                ->filter(function ($machine) use ($detailKeys): bool {
                    $machineName = strtoupper(trim((string) $machine->machine_name));
                    $resolvedKey = $this->resolvePerformanceGroupByMachineName($machineName);

                    return $resolvedKey !== null && in_array($resolvedKey, $detailKeys, true);
                })
                ->values();

            if ($rowsForColumn->isEmpty()) {
                $expected[$columnKey] = 0;
                continue;
            }

            $expected[$columnKey] = $this->calculateExpectedSamplesFromRowsWithConditions(
                $record,
                $teamName,
                $rowsForColumn,
                self::SPINTEST_SAMPLING_INTERVAL_MINUTES
            );
        }

        return $expected;
    }

    private function buildActualSamplesBySpintestColumns(
        string $date,
        string $office,
        array $machineWindowsByCode,
        array $teamMembers,
        array $spintestColumns
    ): array {
        $actual = [];
        $detailKeyToColumnKey = [];

        foreach ($spintestColumns as $column) {
            $columnKey = (string) ($column['key'] ?? '');
            if ($columnKey === '') {
                continue;
            }

            $actual[$columnKey] = 0;

            foreach ((array) ($column['detail_keys'] ?? []) as $detailKey) {
                $detailKeyToColumnKey[(string) $detailKey] = $columnKey;
            }
        }

        if (empty($detailKeyToColumnKey)) {
            return $actual;
        }

        $rows = $this->getSpintestAnalysisEntriesCached($date, $office);
        if ($rows->isEmpty()) {
            return $actual;
        }

        $normalizedTeamMembers = array_map(
            fn($member): string => strtoupper(trim((string) $member)),
            $teamMembers
        );

        $slots = [];
        foreach ($rows as $row) {
            $detailKey = trim((string) ($row['detail_key'] ?? ''));
            $columnKey = $detailKeyToColumnKey[$detailKey] ?? null;
            if ($columnKey === null) {
                continue;
            }

            if (!empty($normalizedTeamMembers)) {
                $sampleBoy = strtoupper(trim((string) ($row['created_by'] ?? '')));
                if (!in_array($sampleBoy, $normalizedTeamMembers, true)) {
                    continue;
                }
            }

            $time = substr((string) ($row['jam'] ?? ''), 0, 5);
            $minutes = $this->timeToMinutes($time);
            if ($minutes === null) {
                continue;
            }

            $windows = $machineWindowsByCode[$this->normalizeCode($detailKey)] ?? [];
            if (empty($windows)) {
                continue;
            }

            $inWindow = false;
            foreach ($windows as $window) {
                if ($this->isMinutesWithinRange($minutes, (int) $window['start'], (int) $window['end'])) {
                    $inWindow = true;
                    break;
                }
            }

            if (!$inWindow) {
                continue;
            }

            $slots[$columnKey][$time] = true;
        }

        foreach ($slots as $columnKey => $columnSlots) {
            $actual[$columnKey] = count($columnSlots);
        }

        return $actual;
    }

    private function getSpintestAnalysisEntriesCached(string $date, string $office): Collection
    {
        $normalizedOffice = strtoupper(trim($office));
        $key = $date . '|' . $normalizedOffice;

        if (!isset($this->spintestAnalysisRowsCache[$key])) {
            $startDate = Carbon::parse($date)->startOfDay();
            $endDate = Carbon::parse($date)->endOfDay();

            $this->spintestAnalysisRowsCache[$key] = $this->collectAllSpintestAnalysisEntries(
                $startDate,
                $endDate,
                $normalizedOffice !== '' ? $normalizedOffice : null,
                $normalizedOffice !== '' ? $normalizedOffice : 'all'
            )->values();
        }

        return $this->spintestAnalysisRowsCache[$key];
    }

    private function formatActualExpected(array $actualByGroup, array $expectedByGroup, string $groupKey): string
    {
        $actual = (int) ($actualByGroup[$groupKey] ?? 0);
        $expected = (int) ($expectedByGroup[$groupKey] ?? 0);

        return $actual . '/' . $expected;
    }

    private function buildExpectedSamplesByGroup(KernelProsses $record, string $teamName, Collection $machines, string $office = 'YBS'): array
    {
        $expected = [];
        $intervals = $this->getIntervalMinutesForOffice($office);
        foreach (array_keys($intervals) as $groupKey) {
            $expected[$groupKey] = 0;
        }

        foreach ($intervals as $groupKey => $intervalMinutes) {
            if ((int) $intervalMinutes <= 0) {
                continue;
            }

            $rowsForGroup = $machines
                ->filter(function ($machine) use ($groupKey): bool {
                    $machineName = strtoupper(trim((string) $machine->machine_name));
                    return $this->resolvePerformanceGroupByMachineName($machineName) === $groupKey;
                })
                ->values();

            if ($rowsForGroup->isEmpty()) {
                continue;
            }

            $expected[$groupKey] = $this->calculateExpectedSamplesFromRowsWithConditions(
                $record,
                $teamName,
                $rowsForGroup,
                (int) $intervalMinutes
            );
        }

        return $expected;
    }

    private function calculateTeamProductionHours(string $processStart, string $processEnd, string $downtimeStart, string $downtimeEnd): int
    {
        $processMinutes = $this->minutesBetween($processStart, $processEnd);
        $downtimeMinutes = $this->minutesBetween($downtimeStart, $downtimeEnd);

        return max(intdiv($processMinutes - $downtimeMinutes, 60), 0);
    }

    private function buildMachineWindowsByCode(Collection $machines): array
    {
        $windowsByCode = [];

        foreach ($machines as $machine) {
            $machineName = strtoupper(trim((string) $machine->machine_name));
            $codes = $this->resolveCodesByMachineName($machineName);

            if (empty($codes)) {
                continue;
            }

            $start = $this->timeToMinutes(substr((string) $machine->production_start_time, 0, 5));
            $end = $this->timeToMinutes(substr((string) $machine->production_end_time, 0, 5));

            if ($start === null || $end === null) {
                continue;
            }

            foreach ($codes as $code) {
                $normalizedCode = $this->normalizeCode($code);
                if (!isset($windowsByCode[$normalizedCode])) {
                    $windowsByCode[$normalizedCode] = [];
                }

                $windowsByCode[$normalizedCode][] = [
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        return $windowsByCode;
    }

    private function resolvePerformanceGroupByMachineName(string $machineName): ?string
    {
        if (str_contains($machineName, 'FFA')) {
            return 'ffa_moisture';
        }

        if (str_contains($machineName, 'SPINTEST') && str_contains($machineName, 'COT')) {
            return 'spintest_cot';
        }

        if (preg_match('/\bCST\s*1\b|\bCST1\b/', $machineName)) {
            return 'underflow_cst_1';
        }

        if (preg_match('/\bCST\s*2\b|\bCST2\b/', $machineName)) {
            return 'underflow_cst_2';
        }

        if (str_contains($machineName, 'ALFA LAVAL')) {
            if (str_contains($machineName, '2')) {
                return 'feed_decanter_alfa_laval_2';
            }

            return 'feed_decanter_alfa_laval_1';
        }

        if (str_contains($machineName, 'GEA')) {
            return 'feed_decanter_gea';
        }

        if (str_contains($machineName, 'FLOTT')) {
            return 'feed_decanter_flottweg';
        }

        if (str_contains($machineName, 'LIGHT PHASE')) {
            if (str_contains($machineName, 'ALFA LAVAL') && str_contains($machineName, '2')) {
                return 'light_phase_alfa_laval_2';
            }

            if (str_contains($machineName, 'ALFA LAVAL')) {
                return 'light_phase_alfa_laval_1';
            }

            if (str_contains($machineName, 'GEA')) {
                return 'light_phase_gea';
            }

            return 'light_phase_flottweg';
        }

        if (preg_match('/\bSTERILIZER\s*(\d+)\b/', $machineName, $matches)) {
            $number = (int) ($matches[1] ?? 0);
            if ($number >= 1 && $number <= 8) {
                return 'sterilizer_' . $number;
            }
        }

        if (str_contains($machineName, 'COT IN')) {
            return 'cot_in';
        }

        if (preg_match('/\bCOT\s*2\b|\bCOT2\b/', $machineName)) {
            return 'cot_2';
        }

        if (preg_match('/\bFD\s*([1-4])\b|\bFD([1-4])\b/', $machineName, $matches)) {
            $number = (int) ($matches[1] ?: ($matches[2] ?: 0));
            return $number >= 1 && $number <= 4 ? 'fd' . $number : null;
        }

        if (preg_match('/\bHP\s*([1-4])\b|\bHP([1-4])\b/', $machineName, $matches)) {
            $number = (int) ($matches[1] ?: ($matches[2] ?: 0));
            return $number >= 1 && $number <= 4 ? 'hp' . $number : null;
        }

        if (preg_match('/\bSD\s*([1-4])\b|\bSD([1-4])\b/', $machineName, $matches)) {
            $number = (int) ($matches[1] ?: ($matches[2] ?: 0));
            return $number >= 1 && $number <= 4 ? 'sd' . $number : null;
        }

        if (preg_match('/\bHPL\s*([1-3])\b|\bHPL([1-3])\b/', $machineName, $matches)) {
            $number = (int) ($matches[1] ?: ($matches[2] ?: 0));
            return $number >= 1 && $number <= 3 ? 'hpl' . $number : null;
        }

        if ($machineName === 'FE') {
            return 'fe';
        }

        if (preg_match('/\bFBP\s*([1-5])\b|\bFBP([1-5])\b/', $machineName, $matches)) {
            $number = (int) ($matches[1] ?: ($matches[2] ?: 0));
            return $number >= 1 && $number <= 5 ? 'fbp' . $number : null;
        }

        if (preg_match('/\bFP\s*([1-9])\b|\bFP([1-9])\b/', $machineName, $matches)) {
            $number = (int) ($matches[1] ?: ($matches[2] ?: 0));
            return $number >= 1 && $number <= 9 ? 'fp' . $number : null;
        }

        if (str_starts_with($machineName, 'FIBRE CYCLONE')) {
            return 'fibre_cyclone';
        }

        if (str_starts_with($machineName, 'LTDS')) {
            return 'ltds';
        }

        if (str_starts_with($machineName, 'CLAYBATH WET SHELL')) {
            return 'claybath_wet_shell';
        }

        if (str_starts_with($machineName, 'INLET KERNEL SILO')) {
            return 'inlet_kernel_silo';
        }

        if (str_starts_with($machineName, 'OUTLET KERNEL SILO')) {
            return 'outlet_kernel_silo';
        }

        if (str_starts_with($machineName, 'PRESS')) {
            return 'press';
        }

        if (str_starts_with($machineName, 'RIPPLE MILL')) {
            return 'eficiency';
        }

        if (str_starts_with($machineName, 'DESTONER')) {
            return 'destoner';
        }

        return null;
    }

    private function buildActualSamplesByGroup(string $date, string $office, array $machineWindowsByCode, array $teamMembers = []): array
    {
        return $this->buildActualSamplesBundle($date, $office, $machineWindowsByCode, $teamMembers)['group'];
    }

    private function buildExpectedSamplesByCode(KernelProsses $record, string $teamName, Collection $machines, string $office = 'YBS'): array
    {
        $expected = [];
        foreach (self::PERFORMANCE_DETAIL_CODES as $detailCode) {
            $expected[$detailCode['code']] = 0;
        }

        $machinesByName = $machines
            ->groupBy(fn($machine) => strtoupper(trim((string) $machine->machine_name)));

        foreach ($machinesByName as $machineName => $rows) {
            $intervalMinutes = $this->resolveMachineIntervalMinutes((string) $machineName, $office);
            if ($intervalMinutes <= 0) {
                continue;
            }

            $expectedSamples = $this->calculateExpectedSamplesFromRowsWithConditions(
                $record,
                $teamName,
                $rows->values(),
                $intervalMinutes
            );

            if ($expectedSamples <= 0) {
                continue;
            }

            $codes = $this->resolveCodesByMachineName((string) $machineName);
            if (empty($codes)) {
                continue;
            }

            $addedCodes = [];
            foreach ($codes as $rawCode) {
                $detailCode = $this->normalizeDetailCode((string) $rawCode);
                if ($detailCode === null || isset($addedCodes[$detailCode])) {
                    continue;
                }

                $addedCodes[$detailCode] = true;
                $expected[$detailCode] = (int) (($expected[$detailCode] ?? 0) + $expectedSamples);
            }
        }

        return $expected;
    }

    private function buildActualSamplesByCode(string $date, string $office, array $machineWindowsByCode, array $teamMembers = []): array
    {
        return $this->buildActualSamplesBundle($date, $office, $machineWindowsByCode, $teamMembers)['code'];
    }

    private function buildActualSamplesBundle(string $date, string $office, array $machineWindowsByCode, array $teamMembers = []): array
    {
        $groupActual = [];
        foreach (array_keys(self::PERFORMANCE_GROUP_INTERVAL_MINUTES) as $groupKey) {
            $groupActual[$groupKey] = 0;
        }

        $codeActual = [];
        foreach (self::PERFORMANCE_DETAIL_CODES as $detailCode) {
            $codeActual[$detailCode['code']] = 0;
        }

        $rows = $this->getTimedCodeRowsCached($date, $office);

        // Normalize team members to uppercase for comparison
        $normalizedTeamMembers = array_map(fn($member) => strtoupper(trim((string) $member)), $teamMembers);
        $groupSlots = [];

        foreach ($rows as $row) {
            // Filter by team member if team_members is provided
            if (!empty($normalizedTeamMembers)) {
                $sampelBoy = strtoupper(trim((string) ($row['sampel_boy'] ?? '')));
                if (!in_array($sampelBoy, $normalizedTeamMembers, true)) {
                    continue;
                }
            }

            $isPengulangan = (bool) ($row['pengulangan'] ?? false);
            if ($isPengulangan) {
                continue;
            }

            $rawCode = $this->normalizeCode((string) ($row['code'] ?? ''));
            $minutes = $this->timeToMinutes((string) ($row['time'] ?? ''));

            if ($rawCode === '' || $minutes === null) {
                continue;
            }

            $windows = $machineWindowsByCode[$rawCode] ?? [];
            if (empty($windows)) {
                continue;
            }

            $matched = false;
            foreach ($windows as $window) {
                if ($this->isMinutesWithinRange($minutes, (int) $window['start'], (int) $window['end'])) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                continue;
            }

            $groupKey = $this->resolvePerformanceGroupByCode($rawCode);
            if ($groupKey !== null) {
                $intervalMinutes = $this->resolveCodeIntervalMinutes($rawCode, $office === '' ? 'YBS' : $office);
                if ($intervalMinutes > 0) {
                    $slotKey = (int) floor($minutes / $intervalMinutes);
                    if (!isset($groupSlots[$groupKey])) {
                        $groupSlots[$groupKey] = [];
                    }

                    $groupSlots[$groupKey][$slotKey] = true;
                }
            }

            $normalizedCode = $this->normalizeDetailCode($rawCode);
            if ($normalizedCode !== null) {
                $codeActual[$normalizedCode]++;
            }
        }

        foreach ($groupSlots as $groupKey => $slots) {
            $intervalMinutes = (int) ($this->getIntervalMinutesForOffice($office === '' ? 'YBS' : $office)[$groupKey] ?? 0);
            $groupActual[$groupKey] = $this->adjustSampleCountForInterval(count($slots), $intervalMinutes);
        }

        return [
            'group' => $groupActual,
            'code' => $codeActual,
        ];
    }

    private function getTimedCodeRowsCached(string $date, string $office): Collection
    {
        $key = $date . '|' . strtoupper(trim($office));

        if (!isset($this->timedCodeRowsCache[$key])) {
            $this->timedCodeRowsCache[$key] = collect()
                ->concat($this->fetchTimedCodeRows(KernelCalculation::query(), $date, $office))
                ->concat($this->fetchTimedCodeRows(KernelDirtMoistCalculation::query(), $date, $office))
                ->concat($this->fetchTimedCodeRows(KernelQwt::query(), $date, $office))
                ->concat($this->fetchTimedCodeRows(KernelRippleMill::query(), $date, $office))
                ->concat($this->fetchTimedCodeRows(KernelDestoner::query(), $date, $office))
                ->values();
        }

        return $this->timedCodeRowsCache[$key];
    }

    private function fetchTimedCodeRows($query, string $date, string $office): Collection
    {
        $baseDate = Carbon::parse($date);
        $startDateTime = $baseDate->copy()->setTime(7, 0, 0)->format('Y-m-d H:i:s');
        $endDateTime = $baseDate->copy()->addDay()->setTime(6, 59, 59)->format('Y-m-d H:i:s');
        $table = $query->getModel()->getTable();
        $hasPengulanganColumn = Schema::hasColumn($table, 'pengulangan');
        $selectColumns = ['kode', 'rounded_time', 'created_at', 'sampel_boy'];
        if ($hasPengulanganColumn) {
            $selectColumns[] = 'pengulangan';
        }

        $rows = $query
            ->where(function ($builder) use ($startDateTime, $endDateTime) {
                $builder->whereBetween('rounded_time', [$startDateTime, $endDateTime])
                    ->orWhere(function ($fallback) use ($startDateTime, $endDateTime) {
                        $fallback->whereNull('rounded_time')
                            ->whereBetween('created_at', [$startDateTime, $endDateTime]);
                    });
            })
            ->when($office !== '', fn($builder) => $builder->where('office', $office))
            ->when($hasPengulanganColumn, fn($builder) => $builder->where('pengulangan', false))
            ->get($selectColumns);

        return $rows->map(function ($row): array {
            $time = $this->extractTimeFromDateTimeValue($row->rounded_time);

            if ($time === '') {
                $time = $this->extractTimeFromDateTimeValue($row->created_at);
            }

            return [
                'code' => strtoupper(trim((string) $row->kode)),
                'time' => $time,
                'sampel_boy' => (string) ($row->sampel_boy ?? ''),
                'pengulangan' => (bool) ($row->pengulangan ?? false),
            ];
        })->values();
    }

    private function extractTimeFromDateTimeValue(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)
                ->setTimezone(config('app.timezone'))
                ->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    private function resolveCodesByMachineName(string $machineName): array
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

        $performanceKey = $this->resolvePerformanceGroupByMachineName($machineName);
        if ($performanceKey !== null) {
            return [$performanceKey];
        }

        return [];
    }

    private function resolvePerformanceGroupByCode(string $code): ?string
    {
        $normalizedCode = $this->normalizeCode($code);

        foreach (self::PERFORMANCE_CODE_GROUPS as $groupKey => $codes) {
            foreach ($codes as $itemCode) {
                if ($this->normalizeCode($itemCode) === $normalizedCode) {
                    return $groupKey;
                }
            }
        }

        return null;
    }

    private function normalizeDetailCode(string $code): ?string
    {
        $normalized = $this->normalizeCode($code);

        foreach (self::PERFORMANCE_DETAIL_CODES as $detailCode) {
            foreach ($detailCode['aliases'] as $alias) {
                if ($normalized === $this->normalizeCode((string) $alias)) {
                    return (string) $detailCode['code'];
                }
            }
        }

        return null;
    }

    private function resolveCodeIntervalMinutes(string $code, string $office = 'YBS'): ?int
    {
        $groupKey = $this->resolvePerformanceGroupByCode($code);
        if ($groupKey === null) {
            return null;
        }

        $intervals = $this->getIntervalMinutesForOffice($office);

        return (int) ($intervals[$groupKey] ?? 0);
    }

    private function resolveMachineIntervalMinutes(string $machineName, string $office = 'YBS'): int
    {
        $groupKey = $this->resolvePerformanceGroupByMachineName(strtoupper(trim($machineName)));
        if ($groupKey === null) {
            return 0;
        }

        $intervals = $this->getIntervalMinutesForOffice($office);

        if (isset($intervals[$groupKey])) {
            return (int) $intervals[$groupKey];
        }

        if (
            in_array($groupKey, [
                'ffa_moisture',
                'spintest_cot',
                'underflow_cst_1',
                'underflow_cst_2',
                'feed_decanter_alfa_laval_1',
                'feed_decanter_alfa_laval_2',
                'feed_decanter_gea',
                'feed_decanter_flottweg',
                'light_phase_alfa_laval_1',
                'light_phase_alfa_laval_2',
                'light_phase_gea',
                'light_phase_flottweg',
                'sterilizer_1',
                'sterilizer_2',
                'sterilizer_3',
                'sterilizer_4',
                'sterilizer_5',
                'sterilizer_6',
                'sterilizer_7',
                'sterilizer_8',
                'cot_in',
                'cot_2',
                'cst',
                'fd1',
                'fd2',
                'fd3',
                'fd4',
                'hp1',
                'hp2',
                'hp3',
                'hp4',
                'sd1',
                'sd2',
                'sd3',
                'sd4',
                'hpl1',
                'hpl2',
                'hpl3',
                'fe',
                'fbp1',
                'fbp2',
                'fbp3',
                'fbp4',
                'fbp5',
                'fp1',
                'fp2',
                'fp3',
                'fp4',
                'fp5',
                'fp6',
                'fp7',
                'fp8',
                'fp9',
            ], true)
        ) {
            return self::SPINTEST_SAMPLING_INTERVAL_MINUTES;
        }

        return 0;
    }

    private function calculateExpectedSamplesFromRowsWithConditions(
        object $record,
        string $teamName,
        Collection $rows,
        int $intervalMinutes
    ): int {
        if ($intervalMinutes <= 0 || $rows->isEmpty()) {
            return 0;
        }

        $segments = $this->buildEffectiveMachineSegmentsForExpected($record, $teamName, $rows);
        $totalDurationMinutes = 0;

        foreach ($segments as $segment) {
            $durationMinutes = max(0, (int) $segment['end'] - (int) $segment['start']);
            if ($durationMinutes <= 0) {
                continue;
            }

            $totalDurationMinutes += $durationMinutes;
        }

        return $this->adjustSampleCountForInterval(
            intdiv($totalDurationMinutes, $intervalMinutes),
            $intervalMinutes
        );
    }

    private function adjustSampleCountForInterval(int $sampleCount, int $intervalMinutes): int
    {
        if ($intervalMinutes === 60 && $sampleCount > 0) {
            return max(0, $sampleCount - 1);
        }

        return max(0, $sampleCount);
    }

    private function buildEffectiveMachineSegmentsForExpected(object $record, string $teamName, Collection $rows): array
    {
        $baseSegments = [];
        foreach ($rows as $row) {
            $start = substr((string) ($row->production_start_time ?? ''), 0, 5);
            $end = substr((string) ($row->production_end_time ?? ''), 0, 5);

            $baseSegments = array_merge($baseSegments, $this->expandTimeRangeSegments($start, $end));
        }

        $baseSegments = $this->normalizeSegments($baseSegments);
        if (empty($baseSegments)) {
            return [];
        }

        $conditionSegments = collect($this->getTeamOtherConditions($record, $teamName))
            ->map(function (array $condition): array {
                $start = trim((string) ($condition['start_time'] ?? ''));
                $end = trim((string) ($condition['end_time'] ?? ''));

                return $this->expandTimeRangeSegments($start, $end);
            })
            ->flatten(1)
            ->values()
            ->all();

        $conditionSegments = $this->normalizeSegments($conditionSegments);

        return $this->subtractSegments($baseSegments, $conditionSegments);
    }

    private function normalizeSegments(array $segments): array
    {
        $normalized = collect($segments)
            ->filter(fn($segment): bool => is_array($segment)
                && isset($segment['start'], $segment['end'])
                && (int) $segment['end'] > (int) $segment['start'])
            ->map(fn($segment): array => [
                'start' => (int) $segment['start'],
                'end' => (int) $segment['end'],
            ])
            ->sortBy('start')
            ->values()
            ->all();

        if (empty($normalized)) {
            return [];
        }

        $merged = [];
        foreach ($normalized as $segment) {
            if (empty($merged)) {
                $merged[] = $segment;
                continue;
            }

            $lastIndex = count($merged) - 1;
            if ($segment['start'] <= $merged[$lastIndex]['end']) {
                $merged[$lastIndex]['end'] = max($merged[$lastIndex]['end'], $segment['end']);
                continue;
            }

            $merged[] = $segment;
        }

        return $merged;
    }

    private function subtractSegments(array $baseSegments, array $cutSegments): array
    {
        $base = $this->normalizeSegments($baseSegments);
        $cuts = $this->normalizeSegments($cutSegments);

        if (empty($base) || empty($cuts)) {
            return $base;
        }

        $result = [];

        foreach ($base as $segment) {
            $parts = [$segment];

            foreach ($cuts as $cut) {
                if (empty($parts)) {
                    break;
                }

                $nextParts = [];
                foreach ($parts as $part) {
                    if ($cut['end'] <= $part['start'] || $cut['start'] >= $part['end']) {
                        $nextParts[] = $part;
                        continue;
                    }

                    if ($cut['start'] > $part['start']) {
                        $nextParts[] = [
                            'start' => $part['start'],
                            'end' => $cut['start'],
                        ];
                    }

                    if ($cut['end'] < $part['end']) {
                        $nextParts[] = [
                            'start' => $cut['end'],
                            'end' => $part['end'],
                        ];
                    }
                }

                $parts = $nextParts;
            }

            $result = array_merge($result, $parts);
        }

        return $this->normalizeSegments($result);
    }

    private function minutesFromWindow(int $start, int $end): int
    {
        if ($end >= $start) {
            return $end - $start;
        }

        return (24 * 60 - $start) + $end;
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(str_replace([' ', '-', '_'], '', trim($code)));
    }

    private function isMinutesWithinRange(int $point, int $start, int $end): bool
    {
        if ($end >= $start) {
            return $point >= $start && $point <= $end;
        }

        return $point >= $start || $point <= $end;
    }

    private function ensureValidTeam(int $team): void
    {
        abort_unless(in_array($team, [1, 2], true), 404);
    }

    private function minutesBetween(string $start, string $end): int
    {
        if ($start === '' || $end === '') {
            return 0;
        }

        $startTotal = $this->timeToMinutes($start);
        $endTotal = $this->timeToMinutes($end);

        if ($startTotal === null || $endTotal === null) {
            return 0;
        }

        if ($endTotal < $startTotal) {
            $endTotal += 24 * 60;
        }

        return $endTotal - $startTotal;
    }

    private function hoursToMinutes(float $hours): int
    {
        if ($hours <= 0) {
            return 0;
        }

        return (int) round($hours * 60);
    }

    private function minutesToTimeLabel(int $minutes): string
    {
        $normalized = $minutes % (24 * 60);
        if ($normalized < 0) {
            $normalized += 24 * 60;
        }

        return sprintf('%02d:%02d', intdiv($normalized, 60), $normalized % 60);
    }

    private function resolveMachineTotalMinutes(KernelMesin $machine): int
    {
        $minutesFromHours = $this->hoursToMinutes((float) ($machine->total_produsction_hours ?? 0));
        if ($minutesFromHours > 0) {
            return $minutesFromHours;
        }

        $minutesFromWindow = $this->minutesBetween(
            substr((string) $machine->production_start_time, 0, 5),
            substr((string) $machine->production_end_time, 0, 5)
        );

        return max($minutesFromWindow, 0);
    }

    private function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    private function calculateOtherConditionDeductionMinutes(string $machineStart, string $machineEnd, array $conditions): int
    {
        if (!$this->isValidTime($machineStart) || !$this->isValidTime($machineEnd)) {
            return 0;
        }

        $overlapSegments = [];

        foreach ($conditions as $condition) {
            $startTime = trim((string) ($condition['start_time'] ?? ''));
            $endTime = trim((string) ($condition['end_time'] ?? ''));

            if (!$this->isValidTime($startTime) || !$this->isValidTime($endTime)) {
                continue;
            }

            $overlapSegments = array_merge(
                $overlapSegments,
                $this->intersectTimeRanges($machineStart, $machineEnd, $startTime, $endTime)
            );
        }

        return $this->sumMergedMinutes($overlapSegments);
    }

    private function intersectTimeRanges(string $startA, string $endA, string $startB, string $endB): array
    {
        $segmentsA = $this->expandTimeRangeSegments($startA, $endA);
        $segmentsB = $this->expandTimeRangeSegments($startB, $endB);
        $intersections = [];

        foreach ($segmentsA as $segmentA) {
            foreach ($segmentsB as $segmentB) {
                $start = max($segmentA['start'], $segmentB['start']);
                $end = min($segmentA['end'], $segmentB['end']);

                if ($end > $start) {
                    $intersections[] = [
                        'start' => $start,
                        'end' => $end,
                    ];
                }
            }
        }

        return $intersections;
    }

    private function expandTimeRangeSegments(string $start, string $end): array
    {
        $startMinute = $this->timeToMinutes($start);
        $endMinute = $this->timeToMinutes($end);

        if ($startMinute === null || $endMinute === null || $startMinute === $endMinute) {
            return [];
        }

        if ($endMinute > $startMinute) {
            return [
                [
                    'start' => $startMinute,
                    'end' => $endMinute,
                ]
            ];
        }

        return [
            [
                'start' => $startMinute,
                'end' => 24 * 60,
            ],
            [
                'start' => 0,
                'end' => $endMinute,
            ],
        ];
    }

    private function sumMergedMinutes(array $segments): int
    {
        $normalized = collect($segments)
            ->filter(fn($segment): bool => is_array($segment)
                && isset($segment['start'], $segment['end'])
                && (int) $segment['end'] > (int) $segment['start'])
            ->map(fn($segment): array => [
                'start' => (int) $segment['start'],
                'end' => (int) $segment['end'],
            ])
            ->sortBy('start')
            ->values()
            ->all();

        if (empty($normalized)) {
            return 0;
        }

        $merged = [];
        foreach ($normalized as $segment) {
            if (empty($merged)) {
                $merged[] = $segment;
                continue;
            }

            $lastIndex = count($merged) - 1;
            if ($segment['start'] <= $merged[$lastIndex]['end']) {
                $merged[$lastIndex]['end'] = max($merged[$lastIndex]['end'], $segment['end']);
                continue;
            }

            $merged[] = $segment;
        }

        return (int) collect($merged)->sum(fn($segment): int => $segment['end'] - $segment['start']);
    }

    private function extractMachinePayload(Request $request, ?string $inputTeam = null, ?string $office = null): array
    {
        $machineGroups = $this->getMachineGroupsForOffice($office);

        $allowedByGroup = collect($machineGroups)
            ->map(fn(array $machines): array => array_values($machines));

        $machineToGroup = collect($machineGroups)
            ->flatMap(function (array $machines, string $group): array {
                $map = [];
                foreach ($machines as $machine) {
                    $map[$machine] = $group;
                }

                return $map;
            });

        $entries = [];
        $errors = [];
        $otherConditionsByTeam = collect();

        foreach ((array) $request->input('machines', []) as $idx => $machine) {
            if (!is_array($machine) || !$this->toBool($machine['selected'] ?? false)) {
                continue;
            }

            $machineName = trim((string) ($machine['machine_name'] ?? ''));
            $machineGroup = trim((string) ($machine['machine_group'] ?? ''));
            $teamName = trim((string) ($machine['team_name'] ?? ''));
            $startTime = trim((string) ($machine['start_time'] ?? ''));
            $endTime = trim((string) ($machine['end_time'] ?? ''));

            if ($inputTeam !== null && $teamName !== $inputTeam) {
                continue;
            }

            if (!$allowedByGroup->has($machineGroup)) {
                $errors["machines.$idx.machine_group"] = 'Sub mesin tidak valid.';
            }

            if ($machineGroup !== '' && !in_array($machineName, $allowedByGroup->get($machineGroup, []), true)) {
                $errors["machines.$idx.machine_name"] = 'Nama sub mesin tidak valid.';
            }

            if (!in_array($teamName, self::TEAM_OPTIONS, true)) {
                $errors["machines.$idx.team_name"] = 'Tim mesin wajib dipilih.';
            }

            if (!$this->isValidTime($startTime)) {
                $errors["machines.$idx.start_time"] = 'Jam awal produksi wajib diisi dengan format HH:MM.';
            }

            if (!$this->isValidTime($endTime)) {
                $errors["machines.$idx.end_time"] = 'Jam akhir produksi wajib diisi dengan format HH:MM.';
            }

            $entries[] = [
                'team_name' => $teamName,
                'machine_group' => $machineGroup,
                'machine_name' => $machineName,
                'production_start_time' => $startTime,
                'production_end_time' => $endTime,
                'row_hours' => ($this->isValidTime($startTime) && $this->isValidTime($endTime))
                    ? round($this->minutesBetween($startTime, $endTime) / 60, 2)
                    : 0,
                'is_spare_input' => false,
            ];
        }

        foreach ((array) $request->input('spare_machines', []) as $idx => $machine) {
            if (!is_array($machine)) {
                continue;
            }

            $teamName = trim((string) ($machine['team_name'] ?? ''));
            $selected = $this->toBool($machine['selected'] ?? false);
            $machineName = trim((string) ($machine['machine_name'] ?? ''));
            $startTime = trim((string) ($machine['start_time'] ?? ''));
            $endTime = trim((string) ($machine['end_time'] ?? ''));

            if ($inputTeam !== null && $teamName !== $inputTeam) {
                continue;
            }

            if ($machineName === '' && $startTime === '' && $endTime === '') {
                continue;
            }

            // Legacy support: prior forms included an explicit selected flag.
            // Current informasi proses form sends value-only rows.
            $isLegacyUnchecked = !$selected && $machineName === '';
            if ($isLegacyUnchecked) {
                continue;
            }

            if ($machineName === '') {
                $errors["spare_machines.$idx.machine_name"] = 'Nama mesin spare wajib diisi.';
            }

            if ($machineName !== '' && !$machineToGroup->has($machineName)) {
                $errors["spare_machines.$idx.machine_name"] = 'Nama mesin spare tidak valid.';
            }

            if (!in_array($teamName, self::TEAM_OPTIONS, true)) {
                $errors["spare_machines.$idx.team_name"] = 'Tim mesin spare wajib dipilih.';
            }

            if (!$this->isValidTime($startTime)) {
                $errors["spare_machines.$idx.start_time"] = 'Jam awal mesin spare wajib diisi dengan format HH:MM.';
            }

            if (!$this->isValidTime($endTime)) {
                $errors["spare_machines.$idx.end_time"] = 'Jam akhir mesin spare wajib diisi dengan format HH:MM.';
            }

            $entries[] = [
                'team_name' => $teamName,
                'machine_group' => (string) $machineToGroup->get($machineName, 'SPARE INPUT'),
                'machine_name' => $machineName,
                'production_start_time' => $startTime,
                'production_end_time' => $endTime,
                'row_hours' => ($this->isValidTime($startTime) && $this->isValidTime($endTime))
                    ? round($this->minutesBetween($startTime, $endTime) / 60, 2)
                    : 0,
                'is_spare_input' => true,
            ];
        }

        foreach ((array) $request->input('other_conditions', []) as $idx => $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $teamName = trim((string) ($condition['team_name'] ?? ''));
            $reason = trim((string) ($condition['reason'] ?? ''));
            $startTime = trim((string) ($condition['start_time'] ?? ''));
            $endTime = trim((string) ($condition['end_time'] ?? ''));

            if ($inputTeam !== null && $teamName !== $inputTeam) {
                continue;
            }

            if ($reason === '' && $startTime === '' && $endTime === '') {
                continue;
            }

            if (!in_array($teamName, self::TEAM_OPTIONS, true)) {
                $errors["other_conditions.$idx.team_name"] = 'Tim kondisi lainnya wajib dipilih.';
            }

            if ($reason === '') {
                $errors["other_conditions.$idx.reason"] = 'Alasan kondisi lainnya wajib diisi.';
            }

            if (!$this->isValidTime($startTime)) {
                $errors["other_conditions.$idx.start_time"] = 'Jam mulai kondisi lainnya wajib diisi dengan format HH:MM.';
            }

            if (!$this->isValidTime($endTime)) {
                $errors["other_conditions.$idx.end_time"] = 'Jam selesai kondisi lainnya wajib diisi dengan format HH:MM.';
            }

            if (!isset($errors["other_conditions.$idx.start_time"]) && !isset($errors["other_conditions.$idx.end_time"])) {
                $otherConditionsByTeam->push([
                    'team_name' => $teamName,
                    'reason' => $reason,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $spareHoursByTeamMachine = collect($entries)
            ->where('is_spare_input', true)
            ->groupBy(fn(array $entry): string => $entry['team_name'] . '|' . $entry['machine_name'])
            ->map(fn($rows): float => round((float) $rows->sum('row_hours'), 2));

        $otherConditionsByTeam = $otherConditionsByTeam
            ->groupBy('team_name')
            ->map(fn(Collection $rows): array => $rows->values()->all());

        return collect($entries)
            ->map(function (array $entry) use ($spareHoursByTeamMachine, $otherConditionsByTeam): array {
                $rowHours = (float) $entry['row_hours'];
                $spareHours = 0.0;
                $totalHours = $rowHours;

                if (!$entry['is_spare_input']) {
                    $teamMachineKey = $entry['team_name'] . '|' . $entry['machine_name'];
                    $teamConditions = (array) $otherConditionsByTeam->get($entry['team_name'], []);
                    $deductionMinutes = $this->calculateOtherConditionDeductionMinutes(
                        (string) $entry['production_start_time'],
                        (string) $entry['production_end_time'],
                        $teamConditions
                    );

                    $deductionHours = round($deductionMinutes / 60, 2);
                    $effectiveRowHours = max(round($rowHours - $deductionHours, 2), 0);

                    $spareHours = (float) $spareHoursByTeamMachine->get($teamMachineKey, 0);
                    $totalHours = $effectiveRowHours + $spareHours;
                }

                return [
                    'team_name' => $entry['team_name'],
                    'machine_group' => $entry['machine_group'],
                    'machine_name' => $entry['machine_name'],
                    'production_start_time' => $entry['production_start_time'],
                    'production_end_time' => $entry['production_end_time'],
                    'total_produsction_hours' => round($totalHours, 2),
                    'is_spare' => round($entry['is_spare_input'] ? $rowHours : $spareHours, 2),
                    'is_spare_input' => $entry['is_spare_input'],
                ];
            })
            ->values()
            ->all();
    }

    private function getMachineGroupsForOffice(?string $office = null): array
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        $machineGroupsFromMaster = KernelMasterData::query()
            ->where('is_active', true)
            ->where('office', $officeCode)
            ->orderBy('kode')
            ->get(['nama_sample'])
            ->groupBy(function ($row): string {
                return $this->resolveMachineGroupFromSampleName((string) $row->nama_sample);
            })
            ->map(function ($rows): array {
                return $rows
                    ->pluck('nama_sample')
                    ->map(fn($name) => strtoupper(trim((string) $name)))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            })
            ->filter(fn(array $rows): bool => !empty($rows))
            ->toArray();

        if (!empty($machineGroupsFromMaster)) {
            $base = $machineGroupsFromMaster;
        } else {
            $base = match ($officeCode) {
                'SUN' => self::MACHINE_GROUPS_SUN,
                'SJN' => self::MACHINE_GROUPS_SJN,
                default => self::MACHINE_GROUPS_YBS,
            };
        }

        // Merge spintest-specific groups so they are visible on the main
        // informasi proses mesin pages as well.
        $spintest = $this->getSpintestMachineGroups($officeCode);

        return array_merge($base, $spintest);
    }

    private function resolveMachineGroupFromSampleName(string $sampleName): string
    {
        $name = strtoupper(trim($sampleName));

        if (str_starts_with($name, 'FIBRE CYCLONE')) {
            return 'FIBRE CYCLONE';
        }

        if (str_starts_with($name, 'LTDS')) {
            return 'LTDS';
        }

        if (str_starts_with($name, 'CLAYBATH WET SHELL')) {
            return 'CLAYBATH WET SHELL';
        }

        if (str_starts_with($name, 'INLET KERNEL SILO')) {
            return 'INLET KERNEL SILO';
        }

        if (str_starts_with($name, 'OUTLET KERNEL SILO')) {
            return 'OUTLET KERNEL SILO TO BUNKER';
        }

        if (str_starts_with($name, 'PRESS')) {
            return 'PRESS';
        }

        if (str_starts_with($name, 'RIPPLE MILL')) {
            return 'RIPPLE MILL';
        }

        if (str_starts_with($name, 'DESTONER')) {
            return 'DESTONER';
        }

        return 'LAINNYA';
    }

    private function extractOtherConditionsPayload(Request $request, ?string $inputTeam = null): array
    {
        $grouped = [
            'Tim 1' => [],
            'Tim 2' => [],
        ];

        foreach ((array) $request->input('other_conditions', []) as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $teamName = trim((string) ($condition['team_name'] ?? ''));
            $reason = trim((string) ($condition['reason'] ?? ''));
            $startTime = trim((string) ($condition['start_time'] ?? ''));
            $endTime = trim((string) ($condition['end_time'] ?? ''));

            if ($inputTeam !== null && $teamName !== $inputTeam) {
                continue;
            }

            if ($reason === '' && $startTime === '' && $endTime === '') {
                continue;
            }

            if (!in_array($teamName, self::TEAM_OPTIONS, true)) {
                continue;
            }

            if ($reason === '' || !$this->isValidTime($startTime) || !$this->isValidTime($endTime)) {
                continue;
            }

            $grouped[$teamName][] = [
                'reason' => $reason,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        return $grouped;
    }

    private function extractSpintestMachinePayload(Request $request, ?string $inputTeam = null, ?string $office = null): array
    {
        $entries = [];
        $otherConditionsByTeam = $this->extractOtherConditionsPayload($request, $inputTeam);
        $machineGroups = $this->getSpintestMachineGroups($office);
        $machineToGroup = collect($machineGroups)
            ->flatMap(function (array $machines, string $group): array {
                $map = [];
                foreach ($machines as $machine) {
                    $map[$machine] = $group;
                }

                return $map;
            });

        foreach ((array) $request->input('machines', []) as $machine) {
            if (!is_array($machine) || !$this->toBool($machine['selected'] ?? false)) {
                continue;
            }

            $teamName = trim((string) ($machine['team_name'] ?? ''));
            $machineName = trim((string) ($machine['machine_name'] ?? ''));
            $machineGroup = trim((string) ($machine['machine_group'] ?? ''));
            $startTime = trim((string) ($machine['start_time'] ?? ''));
            $endTime = trim((string) ($machine['end_time'] ?? ''));

            if ($inputTeam !== null && $teamName !== $inputTeam) {
                continue;
            }

            if ($teamName === '' || $machineName === '' || !$this->isValidTime($startTime) || !$this->isValidTime($endTime)) {
                continue;
            }

            $baseMinutes = $this->minutesBetween($startTime, $endTime);
            $deductionMinutes = $this->calculateOtherConditionDeductionMinutes($startTime, $endTime, $otherConditionsByTeam[$teamName] ?? []);
            $effectiveMinutes = max(0, $baseMinutes - $deductionMinutes);

            $entries[] = [
                'team_name' => $teamName,
                'machine_group' => $machineGroup !== '' ? $machineGroup : 'SPINTEST',
                'machine_name' => $machineName,
                'production_start_time' => $startTime,
                'production_end_time' => $endTime,
                'total_production_hours' => round($effectiveMinutes / 60, 2),
                'is_spare_input' => false,
                'is_spare' => 0,
            ];
        }

        foreach ((array) $request->input('spare_machines', []) as $machine) {
            if (!is_array($machine)) {
                continue;
            }

            $teamName = trim((string) ($machine['team_name'] ?? ''));
            $selected = $this->toBool($machine['selected'] ?? false);
            $machineName = trim((string) ($machine['machine_name'] ?? ''));
            $startTime = trim((string) ($machine['start_time'] ?? ''));
            $endTime = trim((string) ($machine['end_time'] ?? ''));

            if ($inputTeam !== null && $teamName !== $inputTeam) {
                continue;
            }

            if (!$selected || $teamName === '' || $machineName === '' || !$this->isValidTime($startTime) || !$this->isValidTime($endTime)) {
                continue;
            }

            $spareMinutes = $this->minutesBetween($startTime, $endTime);
            $entries[] = [
                'team_name' => $teamName,
                'machine_group' => (string) $machineToGroup->get($machineName, 'SPARE INPUT'),
                'machine_name' => $machineName,
                'production_start_time' => $startTime,
                'production_end_time' => $endTime,
                'total_production_hours' => round($spareMinutes / 60, 2),
                'is_spare_input' => true,
                'is_spare' => round($spareMinutes / 60, 2),
            ];
        }

        return $entries;
    }

    private function requestContainsTeamConditions(Request $request, string $teamName): bool
    {
        foreach ((array) $request->input('other_conditions', []) as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            if (trim((string) ($condition['team_name'] ?? '')) === $teamName) {
                return true;
            }
        }

        return false;
    }

    private function getTeamOtherConditions(object $record, string $teamName): array
    {
        $rows = $teamName === 'Tim 1'
            ? (array) ($record->team_1_other_conditions ?? [])
            : (array) ($record->team_2_other_conditions ?? []);

        return collect($rows)
            ->filter(fn($row): bool => is_array($row))
            ->map(function (array $row): array {
                $start = trim((string) ($row['start_time'] ?? ''));
                $end = trim((string) ($row['end_time'] ?? ''));
                $minutes = ($this->isValidTime($start) && $this->isValidTime($end))
                    ? $this->minutesBetween($start, $end)
                    : 0;

                return [
                    'reason' => trim((string) ($row['reason'] ?? '')),
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration_minutes' => $minutes,
                    'duration_hours' => round($minutes / 60, 2),
                ];
            })
            ->filter(fn(array $row): bool => $row['reason'] !== '' && $row['start_time'] !== '' && $row['end_time'] !== '')
            ->values()
            ->all();
    }

    private function isValidTime(string $time): bool
    {
        if ($time === '') {
            return false;
        }

        $date = \DateTime::createFromFormat('H:i', $time);

        return $date !== false && $date->format('H:i') === $time;
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function getUserOfficeScope(?string $office = null): ?string
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? '')));

        return $officeCode !== '' ? $officeCode : null;
    }

    private function applyUserOfficeScope(Builder $query, ?string $office = null): Builder
    {
        $officeScope = $this->getUserOfficeScope($office);

        if ($officeScope === null) {
            return $query;
        }

        return $query->whereHas('user', function (Builder $userQuery) use ($officeScope): void {
            $userQuery->where('office', $officeScope);
        });
    }

    private function getIntervalMinutesForOffice(string $office): array
    {
        if (strtoupper(trim($office)) === 'SUN') {
            return self::PERFORMANCE_GROUP_INTERVAL_MINUTES_SUN;
        }

        // Default to YBS intervals for all other offices
        return self::PERFORMANCE_GROUP_INTERVAL_MINUTES;
    }

    private function getAvailableOfficesForPerformance(): array
    {
        $offices = KernelProsses::query()
            ->whereNotNull('office')
            ->select('office')
            ->distinct()
            ->orderBy('office')
            ->pluck('office')
            ->map(fn($office) => strtoupper(trim((string) $office)))
            ->filter(fn($office) => $office !== '')
            ->values()
            ->unique()
            ->all();

        $userOffice = strtoupper(trim((string) (auth()->user()->office ?? '')));
        if ($userOffice !== '' && !in_array($userOffice, $offices, true)) {
            $offices[] = $userOffice;
            sort($offices);
        }

        return $offices;
    }

    private function getTeamMembersByOffice(): array
    {
        $office = trim((string) (auth()->user()->office ?? ''));

        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'like', 'Sampel Boy%');
            })
            ->when($office !== '', fn($query) => $query->where('office', $office))
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn($name) => $name !== null && $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveProcessRoleFlags(): array
    {
        $user = auth()->user();
        $canManageTeamMeta = $user
            ? ($user->can('create informasi proses mesin') || $user->can('edit informasi proses mesin'))
            : false;

        $canManageMachineData = $user
            ? ($user->can('create jam proses mesin') || $user->can('edit jam proses mesin'))
            : false;

        return [
            'can_manage_team_meta' => $canManageTeamMeta,
            'can_manage_machine_data' => $canManageMachineData,
        ];
    }

    private function buildTeamMemberValidationRules(): array
    {
        $members = $this->getTeamMembersByOffice();
        $rules = ['string'];

        if (!empty($members)) {
            $rules[] = Rule::in($members);
        }

        return $rules;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ── ANALISA MOISTURE & SPINTES ────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════

    public function analisisaMoistureInput()
    {
        return view('process.analisa-moisture.input', [
            'isSunOffice' => $this->isSunOffice(),
            'cstMachines' => $this->getCstMachinesForOffice(),
            'decanterMachines' => $this->getDecanterMachinesForOffice(),
            'lightPhaseMachines' => $this->getLightPhaseMachinesForOffice(),
        ]);
    }

    public function analisisaMoistureStore(Request $request)
    {
        if ($request->boolean('bulk_submit')) {
            $userId = auth()->id();
            $createdBy = (string) (auth()->user()->name ?? 'System');
            $office = $this->resolveCurrentOffice();
            $isYbsOffice = $office === 'YBS';

            return DB::transaction(function () use ($request, $userId, $createdBy, $office, $isYbsOffice) {
                $validated = $request->validate([
                    'ffa.tanggal' => ['nullable', 'date'],
                    'ffa.jam' => ['nullable', 'date_format:H:i'],
                    'ffa.moisture' => ['nullable', 'string', 'max:100'],
                    'ffa.bst1_ffa' => ['nullable', 'numeric'],
                    'ffa.bst2_ffa' => ['nullable', 'numeric'],
                    'ffa.bst3_ffa' => ['nullable', 'numeric'],
                    'ffa.impurities' => ['nullable', 'numeric'],

                    'cot.tanggal' => ['nullable', 'date'],
                    'cot.jam' => ['nullable', 'date_format:H:i'],
                    'cot.oil' => ['nullable', 'numeric'],
                    'cot.emulsi' => ['nullable', 'numeric'],
                    'cot.air' => ['nullable', 'numeric'],
                    'cot.nos' => ['nullable', 'numeric'],

                    'machines.spintest_cst' => ['nullable', 'array'],
                    'machines.spintest_cst.*.tanggal' => ['nullable', 'date'],
                    'machines.spintest_cst.*.jam' => ['nullable', 'date_format:H:i'],
                    'machines.spintest_cst.*.machine_name' => ['nullable', Rule::in($this->getCstMachinesForOffice())],
                    'machines.spintest_cst.*.oil' => ['nullable', 'numeric'],
                    'machines.spintest_cst.*.emulsi' => ['nullable', 'numeric'],
                    'machines.spintest_cst.*.air' => ['nullable', 'numeric'],
                    'machines.spintest_cst.*.nos' => ['nullable', 'numeric'],

                    'machines.spintest_feed_decanter' => ['nullable', 'array'],
                    'machines.spintest_feed_decanter.*.tanggal' => ['nullable', 'date'],
                    'machines.spintest_feed_decanter.*.jam' => ['nullable', 'date_format:H:i'],
                    'machines.spintest_feed_decanter.*.machine_name' => ['nullable', Rule::in($this->getDecanterMachinesForOffice())],
                    'machines.spintest_feed_decanter.*.oil' => ['nullable', 'numeric'],
                    'machines.spintest_feed_decanter.*.emulsi' => ['nullable', 'numeric'],
                    'machines.spintest_feed_decanter.*.air' => ['nullable', 'numeric'],
                    'machines.spintest_feed_decanter.*.nos' => ['nullable', 'numeric'],

                    'machines.spintest_light_phase' => ['nullable', 'array'],
                    'machines.spintest_light_phase.*.tanggal' => ['nullable', 'date'],
                    'machines.spintest_light_phase.*.jam' => ['nullable', 'date_format:H:i'],
                    'machines.spintest_light_phase.*.machine_name' => ['nullable', Rule::in($this->getLightPhaseMachinesForOffice())],
                    'machines.spintest_light_phase.*.oil' => ['nullable', 'numeric'],
                    'machines.spintest_light_phase.*.emulsi' => ['nullable', 'numeric'],
                    'machines.spintest_light_phase.*.air' => ['nullable', 'numeric'],
                    'machines.spintest_light_phase.*.nos' => ['nullable', 'numeric'],
                ]);

                $savedCount = 0;

                if ($this->hasAnyMeaningfulValue($validated['ffa'] ?? [])) {
                    $ffaDate = $validated['ffa']['tanggal'] ?? now()->toDateString();
                    $ffaJam = $this->normalizeHalfHourTime($validated['ffa']['jam'] ?? null, now()->format('H:i'));

                    if ($isYbsOffice) {
                        $this->assertYbsProcessTimeMatches($office, $ffaDate, $ffaJam, 'Analisa FFA dan Moisture', 'spintest');
                    }

                    FfaMoisture::create([
                        'user_id' => $userId,
                        'created_by' => $createdBy,
                        'office' => $office,
                        'tanggal' => $ffaDate,
                        'jam' => $ffaJam,
                        'moisture' => $this->stringOrDefault($validated['ffa']['moisture'] ?? null, '0'),
                        'bst1_ffa' => $this->numericOrZero($validated['ffa']['bst1_ffa'] ?? null),
                        'bst2_ffa' => $this->numericOrZero($validated['ffa']['bst2_ffa'] ?? null),
                        'bst3_ffa' => $this->isSunOffice() ? 0 : $this->numericOrZero($validated['ffa']['bst3_ffa'] ?? null),
                        'impurities' => $this->numericOrZero($validated['ffa']['impurities'] ?? null),
                    ]);
                    $savedCount++;
                }

                if ($this->hasAnyMeaningfulValue($validated['cot'] ?? [])) {
                    $cotDate = $validated['cot']['tanggal'] ?? now()->toDateString();
                    $cotJam = $this->normalizeHalfHourTime($validated['cot']['jam'] ?? null, now()->format('H:i'));

                    if ($isYbsOffice) {
                        $this->assertYbsProcessTimeMatches($office, $cotDate, $cotJam, 'Analisa Spintest COT', 'spintest');
                    }

                    SpintestCot::create([
                        'user_id' => $userId,
                        'created_by' => $createdBy,
                        'office' => $office,
                        'tanggal' => $cotDate,
                        'jam' => $cotJam,
                        'oil' => $this->numericOrZero($validated['cot']['oil'] ?? null),
                        'emulsi' => $this->numericOrZero($validated['cot']['emulsi'] ?? null),
                        'air' => $this->numericOrZero($validated['cot']['air'] ?? null),
                        'nos' => $this->numericOrZero($validated['cot']['nos'] ?? null),
                    ]);
                    $savedCount++;
                }

                foreach (($validated['machines']['spintest_cst'] ?? []) as $row) {
                    if (!$this->hasAnyMeaningfulValue($row, ['machine_name'])) {
                        continue;
                    }

                    $rowDate = $row['tanggal'] ?? now()->toDateString();
                    $rowJam = $this->normalizeHalfHourTime($row['jam'] ?? null, now()->format('H:i'));

                    if ($isYbsOffice) {
                        $this->assertYbsProcessTimeMatches($office, $rowDate, $rowJam, 'Analisa Spintest Underflow CST', 'spintest');
                    }

                    SpintestCst::create([
                        'user_id' => $userId,
                        'created_by' => $createdBy,
                        'office' => $office,
                        'tanggal' => $rowDate,
                        'jam' => $rowJam,
                        'machine_name' => $row['machine_name'] ?? $this->getCstMachinesForOffice()[0],
                        'oil' => $this->numericOrZero($row['oil'] ?? null),
                        'emulsi' => $this->numericOrZero($row['emulsi'] ?? null),
                        'air' => $this->numericOrZero($row['air'] ?? null),
                        'nos' => $this->numericOrZero($row['nos'] ?? null),
                    ]);
                    $savedCount++;
                }

                foreach (($validated['machines']['spintest_feed_decanter'] ?? []) as $row) {
                    if (!$this->hasAnyMeaningfulValue($row, ['machine_name'])) {
                        continue;
                    }

                    $rowDate = $row['tanggal'] ?? now()->toDateString();
                    $rowJam = $this->normalizeHalfHourTime($row['jam'] ?? null, now()->format('H:i'));

                    if ($isYbsOffice) {
                        $this->assertYbsProcessTimeMatches($office, $rowDate, $rowJam, 'Analisa Spintest Feed Decanter', 'spintest');
                    }

                    SpintestFeedDecanter::create([
                        'user_id' => $userId,
                        'created_by' => $createdBy,
                        'office' => $office,
                        'tanggal' => $rowDate,
                        'jam' => $rowJam,
                        'machine_name' => $row['machine_name'] ?? $this->getDecanterMachinesForOffice()[0],
                        'oil' => $this->numericOrZero($row['oil'] ?? null),
                        'emulsi' => $this->numericOrZero($row['emulsi'] ?? null),
                        'air' => $this->numericOrZero($row['air'] ?? null),
                        'nos' => $this->numericOrZero($row['nos'] ?? null),
                    ]);
                    $savedCount++;
                }

                foreach (($validated['machines']['spintest_light_phase'] ?? []) as $row) {
                    if (!$this->hasAnyMeaningfulValue($row, ['machine_name'])) {
                        continue;
                    }

                    $rowDate = $row['tanggal'] ?? now()->toDateString();
                    $rowJam = $this->normalizeHalfHourTime($row['jam'] ?? null, now()->format('H:i'));

                    if ($isYbsOffice) {
                        $this->assertYbsProcessTimeMatches($office, $rowDate, $rowJam, 'Analisa Spintest Light Phase', 'spintest');
                    }

                    SpintestLightPhase::create([
                        'user_id' => $userId,
                        'created_by' => $createdBy,
                        'office' => $office,
                        'tanggal' => $rowDate,
                        'jam' => $rowJam,
                        'machine_name' => $row['machine_name'] ?? $this->getLightPhaseMachinesForOffice()[0],
                        'oil' => $this->numericOrZero($row['oil'] ?? null),
                        'emulsi' => $this->numericOrZero($row['emulsi'] ?? null),
                        'air' => $this->numericOrZero($row['air'] ?? null),
                        'nos' => $this->numericOrZero($row['nos'] ?? null),
                    ]);
                    $savedCount++;
                }

                if ($savedCount === 0) {
                    throw ValidationException::withMessages([
                        'bulk_submit' => 'Minimal satu form harus diisi agar data bisa disimpan.',
                    ]);
                }

                return redirect()->route('analisa-moisture.input')
                    ->with('success', 'Semua data analisa berhasil disimpan.');
            });
        }

        $module = (string) $request->input('module', '');
        $userId = auth()->id();
        $createdBy = (string) (auth()->user()->name ?? 'System');

        return DB::transaction(function () use ($request, $module, $userId, $createdBy) {
            $redirectRoute = 'analisa-moisture.ffa-moisture';
            $successMessage = 'Data berhasil disimpan.';
            $isSunOffice = $this->isSunOffice();

            switch ($module) {
                case 'ffa_moisture':
                    $validated = $request->validate([
                        'tanggal' => ['required', 'date'],
                        'jam' => ['required', 'date_format:H:i'],
                        'moisture' => ['required', 'string', 'max:100'],
                        'bst1_ffa' => ['required', 'numeric'],
                        'bst2_ffa' => ['required', 'numeric'],
                        'bst3_ffa' => $isSunOffice ? ['nullable', 'numeric'] : ['required', 'numeric'],
                        'impurities' => ['required', 'numeric'],
                    ]);

                    FfaMoisture::create(array_merge($validated, [
                        'user_id' => $userId,
                        'created_by' => $createdBy,
                        'office' => $this->resolveCurrentOffice(),
                        'bst3_ffa' => $isSunOffice ? 0 : ($validated['bst3_ffa'] ?? null),
                    ]));
                    $redirectRoute = 'analisa-moisture.ffa-moisture';
                    $successMessage = 'Data analisa FFA dan Moisture berhasil disimpan.';
                    break;

                case 'spintest_cot':
                    $validated = $request->validate([
                        'tanggal' => ['required', 'date'],
                        'jam' => ['required', 'date_format:H:i'],
                        'oil' => ['required', 'numeric'],
                        'emulsi' => ['required', 'numeric'],
                        'air' => ['required', 'numeric'],
                        'nos' => ['required', 'numeric'],
                    ]);

                    SpintestCot::create($validated + ['user_id' => $userId, 'created_by' => $createdBy, 'office' => $this->resolveCurrentOffice()]);
                    $redirectRoute = 'analisa-moisture.spintest-cot';
                    $successMessage = 'Data analisa Spintest COT berhasil disimpan.';
                    break;

                case 'spintest_cst':
                    $validated = $request->validate([
                        'tanggal' => ['required', 'date'],
                        'jam' => ['required', 'date_format:H:i'],
                        'machine_name' => ['required', Rule::in($this->getCstMachinesForOffice())],
                        'oil' => ['required', 'numeric'],
                        'emulsi' => ['required', 'numeric'],
                        'air' => ['required', 'numeric'],
                        'nos' => ['required', 'numeric'],
                    ]);

                    SpintestCst::create($validated + ['user_id' => $userId, 'created_by' => $createdBy, 'office' => $this->resolveCurrentOffice()]);
                    $redirectRoute = 'analisa-moisture.spintest-underflow-cst';
                    $successMessage = 'Data analisa Spintest Underflow CST berhasil disimpan.';
                    break;

                case 'spintest_feed_decanter':
                    $validated = $request->validate([
                        'tanggal' => ['required', 'date'],
                        'jam' => ['required', 'date_format:H:i'],
                        'machine_name' => ['required', Rule::in($this->getDecanterMachinesForOffice())],
                        'oil' => ['required', 'numeric'],
                        'emulsi' => ['required', 'numeric'],
                        'air' => ['required', 'numeric'],
                        'nos' => ['required', 'numeric'],
                    ]);

                    SpintestFeedDecanter::create($validated + ['user_id' => $userId, 'created_by' => $createdBy, 'office' => $this->resolveCurrentOffice()]);
                    $redirectRoute = 'analisa-moisture.spintest-feed-decanter';
                    $successMessage = 'Data analisa Spintest Feed Decanter berhasil disimpan.';
                    break;

                case 'spintest_light_phase':
                    $validated = $request->validate([
                        'tanggal' => ['required', 'date'],
                        'jam' => ['required', 'date_format:H:i'],
                        'machine_name' => ['required', Rule::in($this->getLightPhaseMachinesForOffice())],
                        'oil' => ['required', 'numeric'],
                        'emulsi' => ['required', 'numeric'],
                        'air' => ['required', 'numeric'],
                        'nos' => ['required', 'numeric'],
                    ]);

                    SpintestLightPhase::create($validated + ['user_id' => $userId, 'created_by' => $createdBy, 'office' => $this->resolveCurrentOffice()]);
                    $redirectRoute = 'analisa-moisture.spintest-light-phase';
                    $successMessage = 'Data analisa Spintest Light Phase berhasil disimpan.';
                    break;

                default:
                    abort(422, 'Module analisa tidak valid.');
            }

            return redirect()->route($redirectRoute)->with('success', $successMessage);
        });
    }

    public function analisaFfaMoisture(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $isSunOffice = $this->isSunOffice($officeScope);

        $rows = FfaMoisture::query()
            ->with('user')
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->paginate(25)
            ->appends($request->except('page'));

        return view('process.analisa-moisture.ffa-moisture', compact('rows', 'startDate', 'endDate', 'isSunOffice'));
    }

    public function editAnalisaFfaMoisture(FfaMoisture $ffaMoisture)
    {
        return view('process.analisa-moisture.edit-ffa-moisture', [
            'row' => $ffaMoisture,
            'isSunOffice' => $this->isSunOffice(),
        ]);
    }

    public function updateAnalisaFfaMoisture(Request $request, FfaMoisture $ffaMoisture)
    {
        $isSunOffice = $this->isSunOffice();

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'moisture' => ['nullable', 'string', 'max:100'],
            'bst1_ffa' => ['nullable', 'numeric'],
            'bst2_ffa' => ['nullable', 'numeric'],
            'bst3_ffa' => ['nullable', 'numeric'],
            'impurities' => ['nullable', 'numeric'],
        ]);

        $ffaMoisture->update([
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'],
            'moisture' => $this->stringOrDefault($validated['moisture'] ?? null, '0'),
            'bst1_ffa' => $this->numericOrZero($validated['bst1_ffa'] ?? null),
            'bst2_ffa' => $this->numericOrZero($validated['bst2_ffa'] ?? null),
            'bst3_ffa' => $isSunOffice ? 0 : $this->numericOrZero($validated['bst3_ffa'] ?? null),
            'impurities' => $this->numericOrZero($validated['impurities'] ?? null),
        ]);

        return redirect()
            ->route('analisa-moisture.ffa-moisture', $this->buildAnalysisListQuery($ffaMoisture->tanggal?->toDateString()))
            ->with('success', 'Data analisa FFA dan Moisture berhasil diperbarui.');
    }

    public function destroyAnalisaFfaMoisture(FfaMoisture $ffaMoisture)
    {
        $tanggal = $ffaMoisture->tanggal?->toDateString();
        $ffaMoisture->delete();

        return redirect()
            ->route('analisa-moisture.ffa-moisture', $this->buildAnalysisListQuery($tanggal))
            ->with('success', 'Data analisa FFA dan Moisture berhasil dihapus.');
    }

    public function exportAnalisaFfaMoisture(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $isSunOffice = $this->isSunOffice($officeScope);

        $rows = FfaMoisture::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        $rows = $rows->map(function (FfaMoisture $row) use ($isSunOffice): array {
            $baseRow = [
                Carbon::parse($row->tanggal)->format('d-m-Y'),
                $row->jam,
                $row->moisture,
                $row->bst1_ffa,
                $row->bst2_ffa,
            ];

            if (!$isSunOffice) {
                $baseRow[] = $row->bst3_ffa;
            }

            $baseRow[] = $row->impurities;

            return $baseRow;
        })->values();

        $headings = $isSunOffice
            ? ['Tanggal', 'Jam', 'Moisture', 'BST1 (FFA)', 'BST2 (FFA)', 'Impurities']
            : ['Tanggal', 'Jam', 'Moisture', 'BST1 (FFA)', 'BST2 (FFA)', 'BST3 (FFA)', 'Impurities'];

        return $this->downloadAnalysisExport(
            'Data Analisa FFA dan Moisture',
            $startDate,
            $endDate,
            $rows,
            $headings,
            'FFA_Moisture_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function analisaSpintestCot(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();

        $rows = SpintestCot::query()
            ->with('user')
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->paginate(25)
            ->appends($request->except('page'));

        return view('process.analisa-moisture.spintest-cot', compact('rows', 'startDate', 'endDate'));
    }

    public function editAnalisaSpintestCot(SpintestCot $spintestCot)
    {
        return view('process.analisa-moisture.edit-spintest-cot', [
            'row' => $spintestCot,
        ]);
    }

    public function updateAnalisaSpintestCot(Request $request, SpintestCot $spintestCot)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'oil' => ['nullable', 'numeric'],
            'emulsi' => ['nullable', 'numeric'],
            'air' => ['nullable', 'numeric'],
            'nos' => ['nullable', 'numeric'],
        ]);

        $spintestCot->update([
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'],
            'oil' => $this->numericOrZero($validated['oil'] ?? null),
            'emulsi' => $this->numericOrZero($validated['emulsi'] ?? null),
            'air' => $this->numericOrZero($validated['air'] ?? null),
            'nos' => $this->numericOrZero($validated['nos'] ?? null),
        ]);

        return redirect()
            ->route('analisa-moisture.spintest-cot', $this->buildAnalysisListQuery($spintestCot->tanggal?->toDateString()))
            ->with('success', 'Data analisa Spintest COT berhasil diperbarui.');
    }

    public function destroyAnalisaSpintestCot(SpintestCot $spintestCot)
    {
        $tanggal = $spintestCot->tanggal?->toDateString();
        $spintestCot->delete();

        return redirect()
            ->route('analisa-moisture.spintest-cot', $this->buildAnalysisListQuery($tanggal))
            ->with('success', 'Data analisa Spintest COT berhasil dihapus.');
    }

    public function exportAnalisaSpintestCot(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();

        $rows = SpintestCot::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get()
            ->map(function (SpintestCot $row): array {
                return [
                    Carbon::parse($row->tanggal)->format('d-m-Y'),
                    $row->jam,
                    $row->oil,
                    $row->emulsi,
                    $row->air,
                    $row->nos,
                ];
            })
            ->values();

        return $this->downloadAnalysisExport(
            'Data Analisa Spintest COT',
            $startDate,
            $endDate,
            $rows,
            ['Tanggal', 'Jam', 'OIL', 'EMULSI', 'AIR', 'NOS'],
            'Spintest_COT_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function analisaSpintestUnderflowCst(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $selectedMachine = trim((string) $request->input('machine_name', 'all'));

        if ($selectedMachine !== 'all' && !in_array($selectedMachine, $this->getCstMachinesForOffice(), true)) {
            $selectedMachine = 'all';
        }

        $rows = SpintestCst::query()
            ->with('user')
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($selectedMachine !== 'all', fn($query) => $query->where('machine_name', $selectedMachine))
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('machine_name')
            ->paginate(25)
            ->appends($request->except('page'));

        return view('process.analisa-moisture.spintest-underflow-cst', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'machineOptions' => $this->getCstMachinesForOffice(),
            'selectedMachine' => $selectedMachine,
        ]);
    }

    public function editAnalisaSpintestUnderflowCst(SpintestCst $spintestCst)
    {
        return view('process.analisa-moisture.edit-spintest-underflow-cst', [
            'row' => $spintestCst,
            'machineOptions' => $this->getCstMachinesForOffice(),
        ]);
    }

    public function updateAnalisaSpintestUnderflowCst(Request $request, SpintestCst $spintestCst)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'machine_name' => ['required', Rule::in($this->getCstMachinesForOffice())],
            'oil' => ['nullable', 'numeric'],
            'emulsi' => ['nullable', 'numeric'],
            'air' => ['nullable', 'numeric'],
            'nos' => ['nullable', 'numeric'],
        ]);

        $spintestCst->update([
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'],
            'machine_name' => $validated['machine_name'],
            'oil' => $this->numericOrZero($validated['oil'] ?? null),
            'emulsi' => $this->numericOrZero($validated['emulsi'] ?? null),
            'air' => $this->numericOrZero($validated['air'] ?? null),
            'nos' => $this->numericOrZero($validated['nos'] ?? null),
        ]);

        return redirect()
            ->route('analisa-moisture.spintest-underflow-cst', $this->buildAnalysisListQuery($spintestCst->tanggal?->toDateString(), $spintestCst->machine_name))
            ->with('success', 'Data analisa Spintest Underflow CST berhasil diperbarui.');
    }

    public function destroyAnalisaSpintestUnderflowCst(SpintestCst $spintestCst)
    {
        $tanggal = $spintestCst->tanggal?->toDateString();
        $machineName = $spintestCst->machine_name;
        $spintestCst->delete();

        return redirect()
            ->route('analisa-moisture.spintest-underflow-cst', $this->buildAnalysisListQuery($tanggal, $machineName))
            ->with('success', 'Data analisa Spintest Underflow CST berhasil dihapus.');
    }

    public function exportAnalisaSpintestUnderflowCst(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $selectedMachine = trim((string) $request->input('machine_name', 'all'));

        if ($selectedMachine !== 'all' && !in_array($selectedMachine, $this->getCstMachinesForOffice(), true)) {
            $selectedMachine = 'all';
        }

        $rows = SpintestCst::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($selectedMachine !== 'all', fn($query) => $query->where('machine_name', $selectedMachine))
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('machine_name')
            ->get()
            ->map(function (SpintestCst $row): array {
                return [
                    Carbon::parse($row->tanggal)->format('d-m-Y'),
                    $row->jam,
                    $row->machine_name,
                    $row->oil,
                    $row->emulsi,
                    $row->air,
                    $row->nos,
                ];
            })
            ->values();

        return $this->downloadAnalysisExport(
            'Data Analisa Spintest Underflow CST',
            $startDate,
            $endDate,
            $rows,
            ['Tanggal', 'Jam', 'Mesin', 'OIL', 'EMULSI', 'AIR', 'NOS'],
            'Spintest_CST_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function analisaSpintestFeedDecanter(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $selectedMachine = trim((string) $request->input('machine_name', 'all'));

        if ($selectedMachine !== 'all' && !in_array($selectedMachine, $this->getDecanterMachinesForOffice(), true)) {
            $selectedMachine = 'all';
        }

        $rows = SpintestFeedDecanter::query()
            ->with('user')
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($selectedMachine !== 'all', fn($query) => $query->where('machine_name', $selectedMachine))
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('machine_name')
            ->paginate(25)
            ->appends($request->except('page'));

        return view('process.analisa-moisture.spintest-feed-decanter', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'machineOptions' => $this->getDecanterMachinesForOffice(),
            'selectedMachine' => $selectedMachine,
        ]);
    }

    public function editAnalisaSpintestFeedDecanter(SpintestFeedDecanter $spintestFeedDecanter)
    {
        return view('process.analisa-moisture.edit-spintest-feed-decanter', [
            'row' => $spintestFeedDecanter,
            'machineOptions' => $this->getDecanterMachinesForOffice(),
        ]);
    }

    public function updateAnalisaSpintestFeedDecanter(Request $request, SpintestFeedDecanter $spintestFeedDecanter)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'machine_name' => ['required', Rule::in($this->getDecanterMachinesForOffice())],
            'oil' => ['nullable', 'numeric'],
            'emulsi' => ['nullable', 'numeric'],
            'air' => ['nullable', 'numeric'],
            'nos' => ['nullable', 'numeric'],
        ]);

        $spintestFeedDecanter->update([
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'],
            'machine_name' => $validated['machine_name'],
            'oil' => $this->numericOrZero($validated['oil'] ?? null),
            'emulsi' => $this->numericOrZero($validated['emulsi'] ?? null),
            'air' => $this->numericOrZero($validated['air'] ?? null),
            'nos' => $this->numericOrZero($validated['nos'] ?? null),
        ]);

        return redirect()
            ->route('analisa-moisture.spintest-feed-decanter', $this->buildAnalysisListQuery($spintestFeedDecanter->tanggal?->toDateString(), $spintestFeedDecanter->machine_name))
            ->with('success', 'Data analisa Spintest Feed Decanter berhasil diperbarui.');
    }

    public function destroyAnalisaSpintestFeedDecanter(SpintestFeedDecanter $spintestFeedDecanter)
    {
        $tanggal = $spintestFeedDecanter->tanggal?->toDateString();
        $machineName = $spintestFeedDecanter->machine_name;
        $spintestFeedDecanter->delete();

        return redirect()
            ->route('analisa-moisture.spintest-feed-decanter', $this->buildAnalysisListQuery($tanggal, $machineName))
            ->with('success', 'Data analisa Spintest Feed Decanter berhasil dihapus.');
    }

    public function exportAnalisaSpintestFeedDecanter(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $selectedMachine = trim((string) $request->input('machine_name', 'all'));

        if ($selectedMachine !== 'all' && !in_array($selectedMachine, $this->getDecanterMachinesForOffice(), true)) {
            $selectedMachine = 'all';
        }

        $rows = SpintestFeedDecanter::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($selectedMachine !== 'all', fn($query) => $query->where('machine_name', $selectedMachine))
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('machine_name')
            ->get()
            ->map(function (SpintestFeedDecanter $row): array {
                return [
                    Carbon::parse($row->tanggal)->format('d-m-Y'),
                    $row->jam,
                    $row->machine_name,
                    $row->oil,
                    $row->emulsi,
                    $row->air,
                    $row->nos,
                ];
            })
            ->values();

        return $this->downloadAnalysisExport(
            'Data Analisa Spintest Feed Decanter',
            $startDate,
            $endDate,
            $rows,
            ['Tanggal', 'Jam', 'Mesin', 'OIL', 'EMULSI', 'AIR', 'NOS'],
            'Spintest_Feed_Decanter_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function analisaSpintestLightPhase(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $selectedMachine = trim((string) $request->input('machine_name', 'all'));

        if ($selectedMachine !== 'all' && !in_array($selectedMachine, $this->getLightPhaseMachinesForOffice(), true)) {
            $selectedMachine = 'all';
        }

        $rows = SpintestLightPhase::query()
            ->with('user')
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($selectedMachine !== 'all', fn($query) => $query->where('machine_name', $selectedMachine))
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('machine_name')
            ->paginate(25)
            ->appends($request->except('page'));

        return view('process.analisa-moisture.spintest-light-phase', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'machineOptions' => $this->getLightPhaseMachinesForOffice(),
            'selectedMachine' => $selectedMachine,
        ]);
    }

    public function editAnalisaSpintestLightPhase(SpintestLightPhase $spintestLightPhase)
    {
        return view('process.analisa-moisture.edit-spintest-light-phase', [
            'row' => $spintestLightPhase,
            'machineOptions' => $this->getLightPhaseMachinesForOffice(),
        ]);
    }

    public function updateAnalisaSpintestLightPhase(Request $request, SpintestLightPhase $spintestLightPhase)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'machine_name' => ['required', Rule::in($this->getLightPhaseMachinesForOffice())],
            'oil' => ['nullable', 'numeric'],
            'emulsi' => ['nullable', 'numeric'],
            'air' => ['nullable', 'numeric'],
            'nos' => ['nullable', 'numeric'],
        ]);

        $spintestLightPhase->update([
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'],
            'machine_name' => $validated['machine_name'],
            'oil' => $this->numericOrZero($validated['oil'] ?? null),
            'emulsi' => $this->numericOrZero($validated['emulsi'] ?? null),
            'air' => $this->numericOrZero($validated['air'] ?? null),
            'nos' => $this->numericOrZero($validated['nos'] ?? null),
        ]);

        return redirect()
            ->route('analisa-moisture.spintest-light-phase', $this->buildAnalysisListQuery($spintestLightPhase->tanggal?->toDateString(), $spintestLightPhase->machine_name))
            ->with('success', 'Data analisa Spintest Light Phase berhasil diperbarui.');
    }

    public function destroyAnalisaSpintestLightPhase(SpintestLightPhase $spintestLightPhase)
    {
        $tanggal = $spintestLightPhase->tanggal?->toDateString();
        $machineName = $spintestLightPhase->machine_name;
        $spintestLightPhase->delete();

        return redirect()
            ->route('analisa-moisture.spintest-light-phase', $this->buildAnalysisListQuery($tanggal, $machineName))
            ->with('success', 'Data analisa Spintest Light Phase berhasil dihapus.');
    }

    public function exportAnalisaSpintestLightPhase(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $selectedMachine = trim((string) $request->input('machine_name', 'all'));

        if ($selectedMachine !== 'all' && !in_array($selectedMachine, $this->getLightPhaseMachinesForOffice(), true)) {
            $selectedMachine = 'all';
        }

        $rows = SpintestLightPhase::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($selectedMachine !== 'all', fn($query) => $query->where('machine_name', $selectedMachine))
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('machine_name')
            ->get()
            ->map(function (SpintestLightPhase $row): array {
                return [
                    Carbon::parse($row->tanggal)->format('d-m-Y'),
                    $row->jam,
                    $row->machine_name,
                    $row->oil,
                    $row->emulsi,
                    $row->air,
                    $row->nos,
                ];
            })
            ->values();

        return $this->downloadAnalysisExport(
            'Data Analisa Spintest Light Phase',
            $startDate,
            $endDate,
            $rows,
            ['Tanggal', 'Jam', 'Mesin', 'OIL', 'EMULSI', 'AIR', 'NOS'],
            'Spintest_Light_Phase_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function rekapAnalisaMoistureSpintest(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $isSunOffice = $this->isSunOffice($officeScope);

        $rows = $this->buildRekapAnalisaMoistureSpintestRows($startDate, $endDate, $officeScope);

        return view('process.analisa-moisture.rekap', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isSunOffice' => $isSunOffice,
        ]);
    }

    public function exportRekapAnalisaMoistureSpintest(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $officeScope = $this->getUserOfficeScope();
        $isSunOffice = $this->isSunOffice($officeScope);

        $rows = $this->buildRekapAnalisaMoistureSpintestRows($startDate, $endDate, $officeScope)
            ->map(function (array $row) use ($isSunOffice): array {
                $cells = [
                    Carbon::parse($row['tanggal'])->format('d-m-Y'),
                    $row['ffa_moisture'],
                    $row['bst1_ffa'],
                    $row['bst2_ffa'],
                    $row['impurities'],
                    $row['cot_oil'],
                    $row['cot_emulsi'],
                    $row['cot_air'],
                    $row['cot_nos'],
                    $row['cst_oil'],
                    $row['cst_emulsi'],
                    $row['cst_air'],
                    $row['cst_nos'],
                    $row['feed_oil'],
                    $row['feed_emulsi'],
                    $row['feed_air'],
                    $row['feed_nos'],
                    $row['light_oil'],
                    $row['light_emulsi'],
                    $row['light_air'],
                    $row['light_nos'],
                ];

                if (!$isSunOffice) {
                    array_splice($cells, 4, 0, [$row['bst3_ffa']]);
                }

                return $cells;
            })
            ->values();

        $headings = [
            'Tanggal',
            'Moisture',
            'BST1',
            'BST2',
        ];

        if (!$isSunOffice) {
            $headings[] = 'BST3';
        }

        $headings = array_merge($headings, [
            'Impurities',
            'COT Oil',
            'COT Emulsi',
            'COT Air',
            'COT Nos',
            'CST Oil',
            'CST Emulsi',
            'CST Air',
            'CST Nos',
            'Feed Oil',
            'Feed Emulsi',
            'Feed Air',
            'Feed Nos',
            'Light Oil',
            'Light Emulsi',
            'Light Air',
            'Light Nos',
        ]);

        return $this->downloadAnalysisExport(
            'Rekap Analisa Moisture & Spintest',
            $startDate,
            $endDate,
            $rows,
            $headings,
            'Rekap_Analisa_Moisture_Spintest_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    private function buildRekapAnalisaMoistureSpintestRows(string $startDate, string $endDate, ?string $officeScope): Collection
    {

        $ffaRows = FfaMoisture::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(tanggal) as tanggal'),
                DB::raw('AVG(CASE WHEN moisture REGEXP "^-?[0-9]+(\\\\.[0-9]+)?$" THEN CAST(moisture AS DECIMAL(10,2)) END) as moisture'),
                DB::raw('AVG(bst1_ffa) as bst1_ffa'),
                DB::raw('AVG(bst2_ffa) as bst2_ffa'),
                DB::raw('AVG(bst3_ffa) as bst3_ffa'),
                DB::raw('AVG(impurities) as impurities')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn($row) => (string) $row->tanggal);

        $cotRows = SpintestCot::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(tanggal) as tanggal'),
                DB::raw('AVG(oil) as oil'),
                DB::raw('AVG(emulsi) as emulsi'),
                DB::raw('AVG(air) as air'),
                DB::raw('AVG(nos) as nos')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn($row) => (string) $row->tanggal);

        $cstRows = SpintestCst::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(tanggal) as tanggal'),
                DB::raw('AVG(oil) as oil'),
                DB::raw('AVG(emulsi) as emulsi'),
                DB::raw('AVG(air) as air'),
                DB::raw('AVG(nos) as nos')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn($row) => (string) $row->tanggal);

        $feedRows = SpintestFeedDecanter::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(tanggal) as tanggal'),
                DB::raw('AVG(oil) as oil'),
                DB::raw('AVG(emulsi) as emulsi'),
                DB::raw('AVG(air) as air'),
                DB::raw('AVG(nos) as nos')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn($row) => (string) $row->tanggal);

        $lightRows = SpintestLightPhase::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(tanggal) as tanggal'),
                DB::raw('AVG(oil) as oil'),
                DB::raw('AVG(emulsi) as emulsi'),
                DB::raw('AVG(air) as air'),
                DB::raw('AVG(nos) as nos')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn($row) => (string) $row->tanggal);

        $dates = collect([$ffaRows->keys(), $cotRows->keys(), $cstRows->keys(), $feedRows->keys(), $lightRows->keys()])
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        $toAvg = fn($value) => $value === null ? null : round((float) $value, 2);

        $rows = $dates->map(function (string $date) use ($ffaRows, $cotRows, $cstRows, $feedRows, $lightRows, $toAvg): array {
            $ffa = $ffaRows->get($date);
            $cot = $cotRows->get($date);
            $cst = $cstRows->get($date);
            $feed = $feedRows->get($date);
            $light = $lightRows->get($date);

            return [
                'tanggal' => $date,
                'ffa_moisture' => $toAvg($ffa?->moisture),
                'bst1_ffa' => $toAvg($ffa?->bst1_ffa),
                'bst2_ffa' => $toAvg($ffa?->bst2_ffa),
                'bst3_ffa' => $toAvg($ffa?->bst3_ffa),
                'impurities' => $toAvg($ffa?->impurities),
                'cot_oil' => $toAvg($cot?->oil),
                'cot_emulsi' => $toAvg($cot?->emulsi),
                'cot_air' => $toAvg($cot?->air),
                'cot_nos' => $toAvg($cot?->nos),
                'cst_oil' => $toAvg($cst?->oil),
                'cst_emulsi' => $toAvg($cst?->emulsi),
                'cst_air' => $toAvg($cst?->air),
                'cst_nos' => $toAvg($cst?->nos),
                'feed_oil' => $toAvg($feed?->oil),
                'feed_emulsi' => $toAvg($feed?->emulsi),
                'feed_air' => $toAvg($feed?->air),
                'feed_nos' => $toAvg($feed?->nos),
                'light_oil' => $toAvg($light?->oil),
                'light_emulsi' => $toAvg($light?->emulsi),
                'light_air' => $toAvg($light?->air),
                'light_nos' => $toAvg($light?->nos),
            ];
        });

        return $rows;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ── LAP JANGKOS ───────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════

    public function lapJangkosInputUsb()
    {
        return view('process.lap-jangkos.input-usb', [
            'rowNumbers' => $this->getUsbRowNumbersForOffice(),
            'shiftOptions' => [1, 2],
        ]);
    }

    public function lapJangkosStoreUsb(Request $request)
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'size:' . count($this->getUsbRowNumbersForOffice())],
            'rows.*.tanggal' => ['nullable', 'date'],
            'rows.*.jam' => ['nullable', 'date_format:H:i'],
            'rows.*.shift' => ['nullable', Rule::in([1, 2, '1', '2'])],
            'rows.*.diamati_jlh_janjang' => ['nullable', 'numeric', 'min:0'],
            'rows.*.lolos_jlh_janjang' => ['nullable', 'numeric', 'min:0'],
        ]);

        $savedCount = 0;
        $userId = auth()->id();
        $createdBy = (string) (auth()->user()->name ?? 'System');
        $office = $this->resolveCurrentOffice();
        $isYbsOffice = $office === 'YBS';

        DB::transaction(function () use ($validated, $userId, $createdBy, &$savedCount, $office, $isYbsOffice) {
            foreach ($this->getUsbRowNumbersForOffice() as $index) {
                $row = $validated['rows'][$index] ?? [];

                if (!$this->hasAnyMeaningfulValue($row)) {
                    continue;
                }

                $diamati = $this->numericOrZero($row['diamati_jlh_janjang'] ?? null);
                $lolos = $this->numericOrZero($row['lolos_jlh_janjang'] ?? null);
                $persenUsb = $diamati > 0 ? round(($lolos / $diamati) * 100, 2) : 0;
                $tanggal = $row['tanggal'] ?? now()->toDateString();
                $jam = $this->normalizeHalfHourTime($row['jam'] ?? null, now()->format('H:i'));

                if ($isYbsOffice) {
                    $this->assertYbsProcessTimeMatches($office, $tanggal, $jam, 'Lap Jangkos USB', 'kernel');
                }

                AnalisaUsb::create([
                    'user_id' => $userId,
                    'created_by' => $createdBy,
                    'office' => $office,
                    'tanggal' => $tanggal,
                    'jam' => $jam,
                    'shift' => (int) ($row['shift'] ?? 1),
                    'no_rebusan' => $index,
                    'diamati_jlh_janjang' => $diamati,
                    'lolos_jlh_janjang' => $lolos,
                    'persen_usb' => $persenUsb,
                ]);

                $savedCount++;
            }
        });

        if ($savedCount === 0) {
            throw ValidationException::withMessages([
                'rows' => 'Minimal satu form rebusan harus diisi agar data bisa disimpan.',
            ]);
        }

        return redirect()->route('lap-jangkos.data-usb')
            ->with('success', 'Data USB berhasil disimpan.');
    }

    public function lapJangkosDataUsb(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $rowNumbers = $this->getUsbRowNumbersForOffice();
        $officeScope = $this->getUserOfficeScope();

        $records = AnalisaUsb::query()
            ->with('user')
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('no_rebusan')
            ->orderBy('id')
            ->get();

        [$detailRows] = $this->buildUsbReportData($records, $startDate, $endDate, $rowNumbers);

        return view('process.lap-jangkos.data-usb', [
            'detailRows' => $detailRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rowNumbers' => $rowNumbers,
        ]);
    }

    public function lapJangkosEditUsb(AnalisaUsb $analisaUsb)
    {
        return view('process.lap-jangkos.edit-usb', [
            'record' => $analisaUsb,
            'shiftOptions' => [1, 2],
            'rowNumbers' => $this->getUsbRowNumbersForOffice(),
        ]);
    }

    public function lapJangkosUpdateUsb(Request $request, AnalisaUsb $analisaUsb)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'shift' => ['required', Rule::in([1, 2, '1', '2'])],
            'diamati_jlh_janjang' => ['required', 'numeric', 'min:0'],
            'lolos_jlh_janjang' => ['required', 'numeric', 'min:0'],
        ]);

        $diamati = (float) $validated['diamati_jlh_janjang'];
        $lolos = (float) $validated['lolos_jlh_janjang'];
        $persenUsb = $diamati > 0 ? round(($lolos / $diamati) * 100, 2) : 0;

        $analisaUsb->update([
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'],
            'shift' => (int) $validated['shift'],
            'diamati_jlh_janjang' => $diamati,
            'lolos_jlh_janjang' => $lolos,
            'persen_usb' => $persenUsb,
        ]);

        return redirect()->route('lap-jangkos.data-usb')
            ->with('success', 'Data USB berhasil diperbarui.');
    }

    public function lapJangkosDestroyUsb(AnalisaUsb $analisaUsb)
    {
        $analisaUsb->delete();

        return redirect()->route('lap-jangkos.data-usb')
            ->with('success', 'Data USB berhasil dihapus.');
    }

    public function lapJangkosRekapUsb(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $rowNumbers = $this->getUsbRowNumbersForOffice();
        $officeScope = $this->getUserOfficeScope();

        $records = AnalisaUsb::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('no_rebusan')
            ->orderBy('id')
            ->get();

        [, $recapData] = $this->buildUsbReportData($records, $startDate, $endDate, $rowNumbers);

        return view('process.lap-jangkos.rekap-usb', [
            'recapData' => $recapData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rowNumbers' => $rowNumbers,
        ]);
    }

    public function lapJangkosRekapUsbExport(Request $request)
    {
        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);
        $rowNumbers = $this->getUsbRowNumbersForOffice();
        $officeScope = $this->getUserOfficeScope();

        $records = AnalisaUsb::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->orderBy('no_rebusan')
            ->orderBy('id')
            ->get();

        [, $recapData] = $this->buildUsbReportData($records, $startDate, $endDate, $rowNumbers);

        $headings = array_merge(
            ['No Rebusan'],
            collect($recapData['dates'])->map(fn(string $date): string => Carbon::parse($date)->format('d-m-Y'))->all()
        );
        $rows = collect();

        foreach ($recapData['row_numbers'] as $rowNumber) {
            $row = ['no_rebusan' => $rowNumber];
            foreach ($recapData['dates'] as $date) {
                $value = $recapData['matrix'][$rowNumber][$date] ?? null;
                $row[$date] = $value === null ? null : round((float) $value, 2);
            }
            $rows->push($row);
        }

        $avgRow = ['no_rebusan' => 'Avg'];
        foreach ($recapData['dates'] as $date) {
            $value = $recapData['date_averages'][$date] ?? null;
            $avgRow[$date] = $value === null ? null : round((float) $value, 2);
        }
        $rows->push($avgRow);

        return $this->downloadAnalysisExport(
            'Rekap USB',
            $startDate,
            $endDate,
            $rows,
            $headings,
            'Rekap_USB_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    private function buildUsbReportData(Collection $records, ?string $startDate = null, ?string $endDate = null, ?array $rowNumbers = null): array
    {
        $rowNumbers = $rowNumbers ?? $this->getUsbRowNumbersForOffice();
        $dateKeys = [];

        if ($startDate !== null && $endDate !== null) {
            foreach (\Carbon\CarbonPeriod::create($startDate, $endDate) as $date) {
                $dateKeys[] = $date->format('Y-m-d');
            }
        } else {
            $dateKeys = $records
                ->pluck('tanggal')
                ->filter()
                ->map(fn($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        $dailyGrouped = [];
        $dailyAllValues = [];

        foreach ($records as $record) {
            $dateKey = Carbon::parse($record->tanggal)->toDateString();
            $rowNumber = (int) $record->no_rebusan;

            if ($rowNumber < 1 || $rowNumber > 8) {
                continue;
            }

            $dailyGrouped[$dateKey][$rowNumber][] = (float) $record->persen_usb;
            $dailyAllValues[$dateKey][] = (float) $record->persen_usb;
        }

        $dailyRowAverages = [];
        foreach ($dailyGrouped as $dateKey => $rows) {
            foreach ($rows as $rowNumber => $values) {
                $dailyRowAverages[$dateKey][$rowNumber] = round(array_sum($values) / count($values), 2);
            }
        }

        $dailyAllAverages = [];
        foreach ($dailyAllValues as $dateKey => $values) {
            $dailyAllAverages[$dateKey] = round(array_sum($values) / count($values), 2);
        }

        $detailRows = $records->map(function (AnalisaUsb $record) use ($dailyAllAverages): array {
            $dateKey = Carbon::parse($record->tanggal)->toDateString();
            $rowNumber = (int) $record->no_rebusan;

            return [
                'id' => (int) $record->id,
                'tanggal' => Carbon::parse($record->tanggal)->format('d-m-Y'),
                'jam' => substr((string) $record->jam, 0, 5),
                'created_by' => (string) ($record->created_by ?: ($record->user?->name ?? '-')),
                'office' => (string) ($record->office ?: '-'),
                'shift' => (int) $record->shift,
                'no_rebusan' => $rowNumber,
                'diamati_jlh_janjang' => (float) $record->diamati_jlh_janjang,
                'lolos_jlh_janjang' => (float) $record->lolos_jlh_janjang,
                'persen_usb' => (float) $record->persen_usb,
                'average' => $dailyAllAverages[$dateKey] ?? (float) $record->persen_usb,
            ];
        })->values()->all();

        $matrix = [];
        foreach ($rowNumbers as $rowNumber) {
            foreach ($dateKeys as $dateKey) {
                $matrix[$rowNumber][$dateKey] = $dailyRowAverages[$dateKey][$rowNumber] ?? null;
            }
        }

        $dateAverages = [];
        foreach ($dateKeys as $dateKey) {
            $values = collect($rowNumbers)
                ->map(fn(int $rowNumber) => $matrix[$rowNumber][$dateKey] ?? null)
                ->filter(fn($value) => $value !== null)
                ->values();

            $dateAverages[$dateKey] = $values->isEmpty()
                ? null
                : round($values->avg(), 2);
        }

        $recapData = [
            'dates' => $dateKeys,
            'row_numbers' => $rowNumbers,
            'matrix' => $matrix,
            'date_averages' => $dateAverages,
        ];

        return [$detailRows, $recapData];
    }

    private function buildBreakdownReportData(Collection $records): array
    {
        $rows = collect();
        $totalMinutes = 0;

        foreach ($records as $record) {
            $processDate = optional($record->process_date)->toDateString();
            if ($processDate === null) {
                continue;
            }

            foreach ([
                'Tim 1' => (array) ($record->team_1_other_conditions ?? []),
                'Tim 2' => (array) ($record->team_2_other_conditions ?? []),
            ] as $teamName => $conditions) {
                foreach ($conditions as $condition) {
                    if (!is_array($condition)) {
                        continue;
                    }

                    $reason = trim((string) ($condition['reason'] ?? ''));
                    $startTime = trim((string) ($condition['start_time'] ?? ''));
                    $endTime = trim((string) ($condition['end_time'] ?? ''));

                    if ($reason === '' || !$this->isValidTime($startTime) || !$this->isValidTime($endTime)) {
                        continue;
                    }

                    $durationMinutes = $this->minutesBetween($startTime, $endTime);
                    $totalMinutes += $durationMinutes;

                    $rows->push([
                        '_sort_date' => $processDate,
                        '_sort_time' => $startTime,
                        'tanggal' => Carbon::parse($processDate)->format('d-m-Y'),
                        'alasan' => $reason,
                        'jam_awal_breakdown' => $startTime,
                        'jam_akhir_breakdown' => $endTime,
                        'total_jam_breakdown' => intdiv($durationMinutes, 60) . ' jam ' . ($durationMinutes % 60) . ' menit',
                        'team_name' => $teamName,
                    ]);
                }
            }
        }

        $sortedRows = $rows
            ->sortBy(fn(array $row): string => $row['_sort_date'] . '|' . $row['_sort_time'] . '|' . $row['alasan'] . '|' . $row['team_name'])
            ->map(function (array $row): array {
                unset($row['_sort_date'], $row['_sort_time'], $row['team_name']);

                return $row;
            })
            ->values()
            ->all();

        $totalDuration = intdiv($totalMinutes, 60) . ' jam ' . ($totalMinutes % 60) . ' menit';

        return [
            'rows' => $sortedRows,
            'total_duration' => $totalDuration,
        ];
    }

    public function oilLossFossInput()
    {
        $this->ensureOilFossAvailableForCurrentOffice();

        $operatorOptions = $this->getOperatorOptionsByOffice(auth()->user()->office ?? null, 'oil');

        return view('process.oil-loss-foss.input', [
            'machineGroups' => $this->oilFossMachineGroups(),
            'operatorOptions' => $operatorOptions,
            'defaultOperator' => $this->resolveOilFossOperator($operatorOptions),
            'shiftOptions' => [1, 2],
        ]);
    }

    public function oilLossFossStore(Request $request)
    {
        $this->ensureOilFossAvailableForCurrentOffice();

        $operatorOptions = $this->getOperatorOptionsByOffice(auth()->user()->office ?? null, 'oil');
        $operatorRule = !empty($operatorOptions) ? Rule::in($operatorOptions) : null;

        $validated = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.tanggal' => ['nullable', 'date'],
            'rows.*.waktu' => ['nullable', 'date_format:H:i'],
            'rows.*.operator' => array_values(array_filter(['nullable', $operatorRule])),
            'rows.*.shift' => ['nullable', Rule::in([1, 2, '1', '2'])],
            'rows.*.moist' => ['nullable', 'numeric', 'min:0'],
            'rows.*.olwb' => ['nullable', 'numeric', 'min:0'],
        ]);

        $definitions = collect($this->oilFossMachineDefinitions())->keyBy('key');
        $savedCount = 0;
        $userId = auth()->id();
        $createdBy = (string) (auth()->user()->name ?? 'System');
        $office = $this->resolveCurrentOffice();
        $isYbsOffice = $office === 'YBS';

        $defaultOperator = $this->resolveOilFossOperator($operatorOptions);

        DB::transaction(function () use ($validated, $definitions, $userId, $createdBy, $defaultOperator, &$savedCount, $office, $isYbsOffice) {
            foreach ($validated['rows'] as $rowKey => $row) {
                if (!$definitions->has($rowKey)) {
                    continue;
                }

                $moistRaw = $row['moist'] ?? null;
                $olwbRaw = $row['olwb'] ?? null;

                $moistFilled = $moistRaw !== null && $moistRaw !== '';
                $olwbFilled = $olwbRaw !== null && $olwbRaw !== '';

                if (!$moistFilled && !$olwbFilled) {
                    continue;
                }

                $moist = $this->nullableNumeric($moistRaw);
                $olwb = $this->nullableNumeric($olwbRaw);

                $hasNonZeroValue = ($moistFilled && (float) $moist !== 0.0)
                    || ($olwbFilled && (float) $olwb !== 0.0);

                if (!$hasNonZeroValue && !($moistFilled && $olwbFilled)) {
                    continue;
                }

                $tanggal = $row['tanggal'] ?? now()->toDateString();
                $waktu = $this->normalizeHalfHourTime($row['waktu'] ?? null, now()->format('H:i'));

                if ($isYbsOffice) {
                    $this->assertYbsProcessTimeMatches($office, $tanggal, $waktu, 'Oil Loss Foss', 'kernel');
                }

                $definition = $definitions->get($rowKey);
                $operator = trim((string) ($row['operator'] ?? $defaultOperator));
                if ($operator === '') {
                    $operator = $defaultOperator;
                }

                OilFoss::create([
                    'user_id' => $userId,
                    'created_by' => $createdBy,
                    'office' => $office,
                    'tanggal' => $tanggal,
                    'waktu' => $waktu,
                    'operator' => $operator,
                    'shift' => (int) ($row['shift'] ?? 1),
                    'machine_group' => $definition['group'],
                    'machine_name' => $definition['label'],
                    'moist' => $moist,
                    'olwb' => $olwb,
                ]);

                $savedCount++;
            }
        });

        if ($savedCount === 0) {
            throw ValidationException::withMessages([
                'rows' => 'Minimal satu form Oil Loss Foss harus terisi agar data dapat disimpan.',
            ]);
        }

        return redirect()->route('oil-loss-foss.data')
            ->with('success', 'Data Oil Loss Foss berhasil disimpan.');
    }

    public function oilLossFossData(Request $request)
    {
        $this->ensureOilFossAvailableForCurrentOffice();
        $officeScope = $this->getUserOfficeScope();

        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);

        $operator = trim((string) $request->input('operator', 'ALL'));
        if ($operator === '' || strtoupper($operator) === 'ALL') {
            $operator = 'ALL';
        }

        $shift = (string) $request->input('shift', 'all');
        if (!in_array($shift, ['all', '1', '2'], true)) {
            $shift = 'all';
        }

        $machineName = trim((string) $request->input('machine_name', 'all'));
        $definitions = collect($this->oilFossMachineDefinitions());
        $definitionMap = $definitions
            ->mapWithKeys(fn(array $definition) => [(string) $definition['label'] => $definition])
            ->all();
        $machineOptions = $definitions
            ->pluck('label')
            ->values()
            ->all();

        if ($machineName !== 'all' && !in_array($machineName, $machineOptions, true)) {
            $machineName = 'all';
        }

        $rows = OilFoss::query()
            ->with('user')
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($operator !== 'ALL', fn($query) => $query->where('operator', $operator))
            ->when($shift !== 'all', fn($query) => $query->where('shift', (int) $shift))
            ->when($machineName !== 'all', fn($query) => $query->where('machine_name', $machineName))
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu')
            ->orderBy('machine_name')
            ->paginate(25)
            ->appends($request->except('page'));

        $operatorOptions = OilFoss::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereNotNull('operator')
            ->where('operator', '!=', '')
            ->select('operator')
            ->distinct()
            ->orderBy('operator')
            ->pluck('operator')
            ->values()
            ->all();

        return view('process.oil-loss-foss.data', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'operator' => $operator,
            'shift' => $shift,
            'machineName' => $machineName,
            'machineOptions' => $machineOptions,
            'operatorOptions' => $operatorOptions,
            'definitionMap' => $definitionMap,
        ]);
    }

    public function oilLossFossDataExport(Request $request)
    {
        $this->ensureOilFossAvailableForCurrentOffice();
        $officeScope = $this->getUserOfficeScope();

        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);

        $operator = trim((string) $request->input('operator', 'ALL'));
        if ($operator === '' || strtoupper($operator) === 'ALL') {
            $operator = 'ALL';
        }

        $shift = (string) $request->input('shift', 'all');
        if (!in_array($shift, ['all', '1', '2'], true)) {
            $shift = 'all';
        }

        $machineName = trim((string) $request->input('machine_name', 'all'));
        $definitions = collect($this->oilFossMachineDefinitions());
        $definitionMap = $definitions
            ->mapWithKeys(fn(array $definition) => [(string) $definition['label'] => $definition])
            ->all();
        $machineOptions = $definitions
            ->pluck('label')
            ->values()
            ->all();

        if ($machineName !== 'all' && !in_array($machineName, $machineOptions, true)) {
            $machineName = 'all';
        }

        $rows = OilFoss::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($operator !== 'ALL', fn($query) => $query->where('operator', $operator))
            ->when($shift !== 'all', fn($query) => $query->where('shift', (int) $shift))
            ->when($machineName !== 'all', fn($query) => $query->where('machine_name', $machineName))
            ->orderBy('tanggal')
            ->orderBy('waktu')
            ->orderBy('machine_name')
            ->get()
            ->map(function (OilFoss $row) use ($definitionMap): array {
                $code = (string) $row->machine_name;
                $definition = $definitionMap[$code] ?? null;

                $moist = $row->moist === null ? null : (float) $row->moist;
                $dmwm = $moist === null ? null : (100 - $moist);
                $olwb = $row->olwb === null ? null : (float) $row->olwb;
                $oldb = ($olwb !== null && $dmwm !== null && $dmwm != 0.0)
                    ? ($olwb / $dmwm)
                    : null;

                $massPercent = $definition['mass_balance_percent'] ?? null;
                $massPercent2 = $definition['mass_balance_percent_2'] ?? null;
                $oilLossesTon = ($olwb !== null && $massPercent !== null)
                    ? ($olwb * (float) $massPercent)
                    : null;

                $operatorMap = [
                    '<=' => '≤',
                    '>=' => '≥',
                    '<' => '<',
                    '>' => '>',
                    '=' => '=',
                ];

                $limitOlwb = null;
                if (($definition['limit_olwb'] ?? null) !== null) {
                    $symbol = $operatorMap[$definition['limit_olwb_operator'] ?? '<='] ?? ($definition['limit_olwb_operator'] ?? '<=');
                    $limitOlwb = $symbol . ' ' . number_format((float) $definition['limit_olwb'], 2);
                }

                $limitOldb = null;
                if (($definition['limit_oldb'] ?? null) !== null) {
                    $symbol = $operatorMap[$definition['limit_oldb_operator'] ?? '<='] ?? ($definition['limit_oldb_operator'] ?? '<=');
                    $limitOldb = $symbol . ' ' . number_format((float) $definition['limit_oldb'], 2);
                }

                $limitOilLoss = null;
                if (($definition['limit_oil_losses'] ?? null) !== null) {
                    $symbol = $operatorMap[$definition['limit_oil_losses_operator'] ?? '<='] ?? ($definition['limit_oil_losses_operator'] ?? '<=');
                    $limitOilLoss = $symbol . ' ' . number_format((float) $definition['limit_oil_losses'], 2);
                }

                return [
                    Carbon::parse($row->tanggal)->format('d-m-Y'),
                    $code,
                    $definition['sample_name'] ?? $code,
                    $moist === null ? null : round($moist, 2),
                    $dmwm === null ? null : round($dmwm, 2),
                    $olwb === null ? null : round($olwb, 2),
                    $limitOlwb,
                    $oldb === null ? null : round($oldb, 4),
                    $limitOldb,
                    $oilLossesTon === null ? null : round($oilLossesTon, 4),
                    $limitOilLoss,
                    $massPercent,
                    $massPercent2,
                ];
            })
            ->values();

        return $this->downloadAnalysisExport(
            'Data Oil Loss Foss',
            $startDate,
            $endDate,
            $rows,
            ['Tanggal', 'Kode', 'Nama Sample', 'MOIST (%)', 'DM/WM (%)', 'OLWB (%)', 'LIMIT (%)', 'OLDB (%)', 'LIMIT (%)2', 'OIL LOSSES/TON TBS (%)', 'LIMIT (%)3', '%', '%2'],
            'Data_Oil_Loss_Foss_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function oilLossFossRekap(Request $request)
    {
        $this->ensureOilFossAvailableForCurrentOffice();
        $officeScope = $this->getUserOfficeScope();

        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);

        $operator = trim((string) $request->input('operator', 'ALL'));
        if ($operator === '' || strtoupper($operator) === 'ALL') {
            $operator = 'ALL';
        }

        $shift = (string) $request->input('shift', 'all');
        if (!in_array($shift, ['all', '1', '2'], true)) {
            $shift = 'all';
        }

        $records = OilFoss::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($operator !== 'ALL', fn($query) => $query->where('operator', $operator))
            ->when($shift !== 'all', fn($query) => $query->where('shift', (int) $shift))
            ->select(
                DB::raw('DATE(tanggal) as tanggal'),
                'machine_name',
                DB::raw('AVG(olwb) as avg_olwb'),
                DB::raw('COUNT(*) as total_data')
            )
            ->groupBy('tanggal', 'machine_name')
            ->orderBy('tanggal')
            ->orderBy('machine_name')
            ->get();

        $dates = $records
            ->pluck('tanggal')
            ->filter()
            ->map(fn($tanggal) => (string) $tanggal)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $columns = $this->oilFossRekapColumns();

        $matrix = [];
        foreach ($dates as $date) {
            $matrix[$date] = [];
            foreach ($columns as $column) {
                $matrix[$date][$column['code']] = null;
            }
        }

        foreach ($records as $record) {
            $dateKey = (string) $record->tanggal;
            $code = (string) $record->machine_name;

            if (!array_key_exists($dateKey, $matrix) || !array_key_exists($code, $matrix[$dateKey])) {
                continue;
            }

            $matrix[$dateKey][$code] = round((float) $record->avg_olwb, 2);
        }

        $grandTotals = [];
        foreach ($columns as $column) {
            $code = $column['code'];
            $values = collect($dates)
                ->map(fn($date) => $matrix[$date][$code] ?? null)
                ->filter(fn($value) => $value !== null)
                ->values();

            $grandTotals[$code] = $values->isEmpty() ? null : round((float) $values->avg(), 2);
        }

        $operatorOptions = OilFoss::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereNotNull('operator')
            ->where('operator', '!=', '')
            ->select('operator')
            ->distinct()
            ->orderBy('operator')
            ->pluck('operator')
            ->values()
            ->all();

        return view('process.oil-loss-foss.rekap', [
            'dates' => $dates,
            'columns' => $columns,
            'matrix' => $matrix,
            'grandTotals' => $grandTotals,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'operator' => $operator,
            'operatorOptions' => $operatorOptions,
            'shift' => $shift,
        ]);
    }

    public function oilLossFossRekapExport(Request $request)
    {
        $this->ensureOilFossAvailableForCurrentOffice();
        $officeScope = $this->getUserOfficeScope();

        [$startDate, $endDate] = $this->resolveAnalysisDateRange($request);

        $operator = trim((string) $request->input('operator', 'ALL'));
        if ($operator === '' || strtoupper($operator) === 'ALL') {
            $operator = 'ALL';
        }

        $shift = (string) $request->input('shift', 'all');
        if (!in_array($shift, ['all', '1', '2'], true)) {
            $shift = 'all';
        }

        $records = OilFoss::query()
            ->when($officeScope !== null, fn($query) => $query->where('office', $officeScope))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($operator !== 'ALL', fn($query) => $query->where('operator', $operator))
            ->when($shift !== 'all', fn($query) => $query->where('shift', (int) $shift))
            ->select(
                DB::raw('DATE(tanggal) as tanggal'),
                'machine_name',
                DB::raw('AVG(olwb) as avg_olwb')
            )
            ->groupBy('tanggal', 'machine_name')
            ->orderBy('tanggal')
            ->orderBy('machine_name')
            ->get();

        $dates = $records
            ->pluck('tanggal')
            ->filter()
            ->map(fn($tanggal) => (string) $tanggal)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $columns = $this->oilFossRekapColumns();

        $matrix = [];
        foreach ($dates as $date) {
            $matrix[$date] = [];
            foreach ($columns as $column) {
                $matrix[$date][$column['code']] = null;
            }
        }

        foreach ($records as $record) {
            $dateKey = (string) $record->tanggal;
            $code = (string) $record->machine_name;

            if (!array_key_exists($dateKey, $matrix) || !array_key_exists($code, $matrix[$dateKey])) {
                continue;
            }

            $matrix[$dateKey][$code] = round((float) $record->avg_olwb, 2);
        }

        $grandTotals = [];
        foreach ($columns as $column) {
            $code = $column['code'];
            $values = collect($dates)
                ->map(fn($date) => $matrix[$date][$code] ?? null)
                ->filter(fn($value) => $value !== null)
                ->values();

            $grandTotals[$code] = $values->isEmpty() ? null : round((float) $values->avg(), 2);
        }

        $rows = collect($dates)
            ->map(function (string $date) use ($columns, $matrix): array {
                $cells = [$date];

                foreach ($columns as $column) {
                    $cells[] = $matrix[$date][$column['code']] ?? null;
                }

                return $cells;
            })
            ->values();

        $grandTotalRow = ['Grand Total'];
        foreach ($columns as $column) {
            $grandTotalRow[] = $grandTotals[$column['code']] ?? null;
        }
        $rows->push($grandTotalRow);

        return $this->downloadAnalysisExport(
            'Rekap Oil Loss Foss',
            $startDate,
            $endDate,
            $rows,
            array_merge(['Row Labels'], array_map(fn(array $column) => $column['sample_name'], $columns)),
            'Rekap_Oil_Loss_Foss_' . $startDate . '_to_' . $endDate . '.xlsx'
        );
    }

    public function oilLossFossEdit(OilFoss $oilFoss)
    {
        $this->ensureOilFossAvailableForCurrentOffice();
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403, 'Akses ditolak.');

        return view('process.oil-loss-foss.edit', [
            'row' => $oilFoss,
            'operatorOptions' => $this->getOperatorOptionsByOffice($oilFoss->user?->office ?? auth()->user()->office ?? null, 'oil'),
            'shiftOptions' => [1, 2],
        ]);
    }

    public function oilLossFossUpdate(Request $request, OilFoss $oilFoss)
    {
        $this->ensureOilFossAvailableForCurrentOffice();
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403, 'Akses ditolak.');

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'waktu' => ['required', 'date_format:H:i'],
            'operator' => ['nullable', 'string', 'max:255'],
            'shift' => ['required', Rule::in([1, 2, '1', '2'])],
            'moist' => ['nullable', 'numeric', 'min:0'],
            'olwb' => ['nullable', 'numeric', 'min:0'],
        ]);

        $operatorOptions = $this->getOperatorOptionsByOffice($oilFoss->user?->office ?? auth()->user()->office ?? null, 'oil');
        $operator = trim((string) ($validated['operator'] ?? ''));
        if ($operator === '' && !empty($operatorOptions)) {
            $operator = (string) $operatorOptions[0];
        }

        $oilFoss->update([
            'tanggal' => $validated['tanggal'],
            'waktu' => $validated['waktu'],
            'operator' => $operator,
            'shift' => (int) $validated['shift'],
            'moist' => $this->nullableNumeric($validated['moist'] ?? null),
            'olwb' => $this->nullableNumeric($validated['olwb'] ?? null),
        ]);

        return redirect()
            ->route('oil-loss-foss.data', $this->buildAnalysisListQuery($oilFoss->tanggal?->toDateString()))
            ->with('success', 'Data Oil Loss Foss berhasil diperbarui.');
    }

    public function oilLossFossDestroy(OilFoss $oilFoss)
    {
        $this->ensureOilFossAvailableForCurrentOffice();
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403, 'Akses ditolak.');

        $tanggal = $oilFoss->tanggal?->toDateString();
        $oilFoss->delete();

        return redirect()
            ->route('oil-loss-foss.data', $this->buildAnalysisListQuery($tanggal))
            ->with('success', 'Data Oil Loss Foss berhasil dihapus.');
    }

    private function numericOrZero(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) $value;
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        $stringValue = trim((string) ($value ?? ''));

        return $stringValue !== '' ? $stringValue : $default;
    }

    private function nullableNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function resolveCurrentOffice(?string $office = null): string
    {
        $officeCode = strtoupper(trim((string) ($office ?? auth()->user()->office ?? 'YBS')));

        return $officeCode !== '' ? $officeCode : 'YBS';
    }

    private function isSunOffice(?string $office = null): bool
    {
        return $this->resolveCurrentOffice($office) === 'SUN';
    }

    private function getCstMachinesForOffice(?string $office = null): array
    {
        return $this->isSunOffice($office) ? self::CST_MACHINES_SUN : self::CST_MACHINES_YBS;
    }

    private function getDecanterMachinesForOffice(?string $office = null): array
    {
        return $this->isSunOffice($office) ? self::DECANTER_MACHINES_SUN : self::DECANTER_MACHINES_YBS;
    }

    private function getLightPhaseMachinesForOffice(?string $office = null): array
    {
        return $this->isSunOffice($office) ? self::LIGHT_PHASE_MACHINES_SUN : self::LIGHT_PHASE_MACHINES_YBS;
    }

    private function getUsbRowNumbersForOffice(?string $office = null): array
    {
        return $this->isSunOffice($office) ? self::USB_ROW_NUMBERS_SUN : self::USB_ROW_NUMBERS_YBS;
    }

    private function ensureOilFossAvailableForCurrentOffice(): void
    {
        if ($this->isSunOffice()) {
            abort(404);
        }
    }

    private function oilFossMachineGroups(): array
    {
        $groups = [];

        foreach ($this->oilFossMachineDefinitions() as $definition) {
            $group = $definition['group'];
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }

            $groups[$group][] = $definition;
        }

        return $groups;
    }

    private function oilFossRekapColumns(): array
    {
        return [
            ['code' => 'COT IN', 'sample_name' => 'COT INLET to COT 2'],
            ['code' => 'COT 2', 'sample_name' => 'U/F COT 2'],
            ['code' => 'CST', 'sample_name' => 'U/F CST'],
            ['code' => 'FD1', 'sample_name' => 'FEED DECANTER ALVA LAFAL 1'],
            ['code' => 'FD2', 'sample_name' => 'FEED DECANTER  IHI 2'],
            ['code' => 'FD3', 'sample_name' => 'FEED DECANTER ALVA LAFAL 2'],
            ['code' => 'FD4', 'sample_name' => 'FEED DECANTER FLOTTWEG'],
            ['code' => 'HP1', 'sample_name' => 'HEAVY PHASE ALFA LAVAL 1'],
            ['code' => 'HP2', 'sample_name' => 'HEAVY PHASE IHI'],
            ['code' => 'HP3', 'sample_name' => 'HEAVY PHASE ALFA LAVAL 2'],
            ['code' => 'HP4', 'sample_name' => 'HEAVY PHASE FLOTTWEG'],
            ['code' => 'SD1', 'sample_name' => 'SOLID ALFA LAVAL 1'],
            ['code' => 'SD2', 'sample_name' => 'SOLID IHI'],
            ['code' => 'SD3', 'sample_name' => 'SOLID ALFA LAVAL 2'],
            ['code' => 'SD4', 'sample_name' => 'SOLID FLOTTWEG'],
            ['code' => 'HPL1', 'sample_name' => 'HEAVY PHASE CENTRIFUGE 1'],
            ['code' => 'HPL2', 'sample_name' => 'HEAVY PHASE CENTRIFUGE 2'],
            ['code' => 'HPL3', 'sample_name' => 'HEAVY PHASE CENTRIFUGE 3'],
            ['code' => 'FE', 'sample_name' => 'FINAL EFFLUENT'],
            ['code' => 'FBP1', 'sample_name' => 'FIBRE EX BUNCH PRESS 1'],
            ['code' => 'FBP2', 'sample_name' => 'FIBRE EX BUNCH PRESS 2'],
            ['code' => 'FBP3', 'sample_name' => 'FIBRE EX BUNCH PRESS 3'],
            ['code' => 'FBP4', 'sample_name' => 'FIBRE EX BUNCH PRESS 4'],
            ['code' => 'FBP5', 'sample_name' => 'FIBRE EX BUNCH PRESS 5'],
            ['code' => 'FP1', 'sample_name' => 'FIBRE PRESS 1'],
            ['code' => 'FP2', 'sample_name' => 'FIBRE PRESS 2'],
            ['code' => 'FP3', 'sample_name' => 'FIBRE PRESS 3'],
            ['code' => 'FP4', 'sample_name' => 'FIBRE PRESS 4'],
            ['code' => 'FP5', 'sample_name' => 'FIBRE PRESS 5'],
            ['code' => 'FP6', 'sample_name' => 'FIBRE PRESS 6'],
            ['code' => 'FP7', 'sample_name' => 'FIBRE PRESS 7'],
            ['code' => 'FP8', 'sample_name' => 'FIBRE PRESS 8'],
            ['code' => 'FP9', 'sample_name' => 'FIBRE PRESS 9'],
        ];
    }

    private function resolveOilFossRekapLabel(?string $label): ?string
    {
        if ($label === null || trim($label) === '') {
            return null;
        }

        $normalizedCurrent = $this->normalizeOilFossLabel($label);
        $labelMap = $this->oilFossRekapLabelMap();

        if (isset($labelMap[$normalizedCurrent])) {
            return $labelMap[$normalizedCurrent];
        }

        return null;
    }

    private function oilFossRekapLabelMap(): array
    {
        $map = [];

        foreach ($this->oilFossMachineDefinitions() as $definition) {
            $sampleName = (string) ($definition['sample_name'] ?? $definition['label']);
            $label = (string) ($definition['label'] ?? $sampleName);

            $map[$this->normalizeOilFossLabel($label)] = $sampleName;
            $map[$this->normalizeOilFossLabel($sampleName)] = $sampleName;
        }

        return $map;
    }

    private function normalizeOilFossLabel(string $label): string
    {
        $normalized = strtoupper(trim($label));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalized = str_replace('ALFA', 'ALVA', $normalized);
        $normalized = str_replace('LAVAL', 'LAFAL', $normalized);

        return $normalized;
    }

    private function oilFossMachineDefinitions(): array
    {
        return [
            ['key' => 'cot_in', 'group' => 'COT', 'label' => 'COT IN', 'sample_name' => 'COT INLET to COT 2', 'limit_olwb_operator' => '>', 'limit_olwb' => 45, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'cot_2', 'group' => 'COT', 'label' => 'COT 2', 'sample_name' => 'U/F COT 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 8, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'cst', 'group' => 'CST', 'label' => 'CST', 'sample_name' => 'U/F CST', 'limit_olwb_operator' => '<=', 'limit_olwb' => 6, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'fd_1', 'group' => 'FD', 'label' => 'FD1', 'sample_name' => 'FEED DECANTER ALVA LAFAL 1', 'limit_olwb_operator' => '<=', 'limit_olwb' => 6, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'fd_2', 'group' => 'FD', 'label' => 'FD2', 'sample_name' => 'FEED DECANTER  IHI 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 6, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'fd_3', 'group' => 'FD', 'label' => 'FD3', 'sample_name' => 'FEED DECANTER ALVA LAFAL 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 6, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'fd_4', 'group' => 'FD', 'label' => 'FD4', 'sample_name' => 'FEED DECANTER FLOTTWEG', 'limit_olwb_operator' => '<=', 'limit_olwb' => 6, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'hp_1', 'group' => 'HP', 'label' => 'HP1', 'sample_name' => 'HEAVY PHASE ALFA LAVAL 1', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'hp_2', 'group' => 'HP', 'label' => 'HP2', 'sample_name' => 'HEAVY PHASE IHI', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'hp_3', 'group' => 'HP', 'label' => 'HP3', 'sample_name' => 'HEAVY PHASE ALFA LAVAL 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'hp_4', 'group' => 'HP', 'label' => 'HP4', 'sample_name' => 'HEAVY PHASE FLOTTWEG', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'sd_1', 'group' => 'SD', 'label' => 'SD1', 'sample_name' => 'SOLID ALFA LAVAL 1', 'limit_olwb_operator' => '<=', 'limit_olwb' => 2.5, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => 0.02, 'mass_balance_percent_2' => null],
            ['key' => 'sd_2', 'group' => 'SD', 'label' => 'SD2', 'sample_name' => 'SOLID IHI', 'limit_olwb_operator' => '<=', 'limit_olwb' => 2.5, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => 0.02, 'mass_balance_percent_2' => null],
            ['key' => 'sd_3', 'group' => 'SD', 'label' => 'SD3', 'sample_name' => 'SOLID ALFA LAVAL 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 2.5, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => 0.02, 'mass_balance_percent_2' => null],
            ['key' => 'sd_4', 'group' => 'SD', 'label' => 'SD4', 'sample_name' => 'SOLID FLOTTWEG', 'limit_olwb_operator' => '<=', 'limit_olwb' => 2.5, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.09, 'mass_balance_percent' => 0.02, 'mass_balance_percent_2' => null],
            ['key' => 'hpl_1', 'group' => 'HPL', 'label' => 'HPL1', 'sample_name' => 'HEAVY PHASE CENTRIFUGE 1', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'hpl_2', 'group' => 'HPL', 'label' => 'HPL2', 'sample_name' => 'HEAVY PHASE CENTRIFUGE 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'hpl_3', 'group' => 'HPL', 'label' => 'HPL3', 'sample_name' => 'HEAVY PHASE CENTRIFUGE 3', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => null, 'limit_oil_losses' => null, 'mass_balance_percent' => null, 'mass_balance_percent_2' => null],
            ['key' => 'fe', 'group' => 'FE', 'label' => 'FE', 'sample_name' => 'FINAL EFFLUENT', 'limit_olwb_operator' => '<=', 'limit_olwb' => 0.7, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.50, 'mass_balance_percent_2' => null],
            ['key' => 'fbp_1', 'group' => 'FBP', 'label' => 'FBP1', 'sample_name' => 'FIBRE EX BUNCH PRESS 1', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1.3, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.75, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => 0],
            ['key' => 'fbp_2', 'group' => 'FBP', 'label' => 'FBP2', 'sample_name' => 'FIBRE EX BUNCH PRESS 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1.3, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.75, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => 0],
            ['key' => 'fbp_3', 'group' => 'FBP', 'label' => 'FBP3', 'sample_name' => 'FIBRE EX BUNCH PRESS 3', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1.3, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.75, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => 0],
            ['key' => 'fbp_4', 'group' => 'FBP', 'label' => 'FBP4', 'sample_name' => 'FIBRE EX BUNCH PRESS 4', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1.3, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.75, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => 0],
            ['key' => 'fbp_5', 'group' => 'FBP', 'label' => 'FBP5', 'sample_name' => 'FIBRE EX BUNCH PRESS 5', 'limit_olwb_operator' => '<=', 'limit_olwb' => 1.3, 'limit_oldb_operator' => null, 'limit_oldb' => null, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.2, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => 0],
            ['key' => 'fp_1', 'group' => 'FP', 'label' => 'FP1', 'sample_name' => 'FIBRE PRESS 1', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_2', 'group' => 'FP', 'label' => 'FP2', 'sample_name' => 'FIBRE PRESS 2', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_3', 'group' => 'FP', 'label' => 'FP3', 'sample_name' => 'FIBRE PRESS 3', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_4', 'group' => 'FP', 'label' => 'FP4', 'sample_name' => 'FIBRE PRESS 4', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_5', 'group' => 'FP', 'label' => 'FP5', 'sample_name' => 'FIBRE PRESS 5', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_6', 'group' => 'FP', 'label' => 'FP6', 'sample_name' => 'FIBRE PRESS 6', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_7', 'group' => 'FP', 'label' => 'FP7', 'sample_name' => 'FIBRE PRESS 7', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_8', 'group' => 'FP', 'label' => 'FP8', 'sample_name' => 'FIBRE PRESS 8', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
            ['key' => 'fp_9', 'group' => 'FP', 'label' => 'FP9', 'sample_name' => 'FIBRE PRESS 9', 'limit_olwb_operator' => '<=', 'limit_olwb' => 3.8, 'limit_oldb_operator' => '<=', 'limit_oldb' => 7.5, 'limit_oil_losses_operator' => '<=', 'limit_oil_losses' => 0.5, 'mass_balance_percent' => 0.13, 'mass_balance_percent_2' => null],
        ];
    }

    private function getOperatorOptionsByOffice(?string $office, string $module): array
    {
        $office = strtoupper(trim((string) $office));

        if ($office === '') {
            $office = 'YBS';
        }

        return config('operator-options.' . $module . '.' . $office, []);
    }

    private function resolveOilFossOperator(array $operatorOptions = []): string
    {
        if (!empty($operatorOptions)) {
            return (string) $operatorOptions[0];
        }

        $office = strtoupper(trim((string) (auth()->user()->office ?? '')));

        if ($office === 'SUN') {
            return 'SUN';
        }

        return 'YBS';
    }

    private function hasAnyMeaningfulValue(array $row, array $ignoredKeys = []): bool
    {
        foreach ($row as $key => $value) {
            if (in_array((string) $key, $ignoredKeys, true)) {
                continue;
            }

            if (is_string($value) && trim($value) !== '') {
                return true;
            }

            if (is_numeric($value) && (float) $value !== 0.0) {
                return true;
            }
        }

        return false;
    }

    private function buildAnalysisListQuery(?string $date = null, ?string $machineName = null): array
    {
        $targetDate = $date ?: now()->toDateString();

        $query = [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
        ];

        if ($machineName !== null && $machineName !== '') {
            $query['machine_name'] = $machineName;
        }

        return $query;
    }

    private function resolveAnalysisDateRange(Request $request): array
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->toDateString()))->toDateString();
        $endDate = Carbon::parse($request->input('end_date', now()->toDateString()))->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function downloadAnalysisExport(string $title, string $startDate, string $endDate, array|Collection $rows, array $headings, string $filename)
    {
        $subtitle = 'Periode: ' . Carbon::parse($startDate)->format('d-m-Y') . ' s/d ' . Carbon::parse($endDate)->format('d-m-Y');

        return Excel::download(
            new ProcessAnalysisExport(collect($rows), $headings, $title, $subtitle),
            $filename
        );
    }
}


