<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\KernelMasterData;
use App\Models\KernelDirtMoistCalculation;
use App\Models\KernelCalculation;
use App\Models\KernelProsses;
use App\Models\KernelMesin;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class KernelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $permissions = [
            'view kernel losses',
            'create kernel losses',
            'edit kernel losses',
            'delete kernel losses',
            'view performance kernel losses',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
        
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo($permissions);
        
        $this->user = User::factory()->create(['office' => 'YBS']);
        $this->user->assignRole('admin');
    }

    public function test_index_displays_kernel_calculations()
    {
        $response = $this->actingAs($this->user)->get(route('kernel.index'));
        $response->assertStatus(200);
        $response->assertViewIs('kernel.index');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get(route('kernel.create'));
        $response->assertStatus(200);
        $response->assertViewIs('kernel.create');
    }

    public function test_store_creates_new_kernel_calculation()
    {
        KernelMasterData::create([
            'office' => 'YBS',
            'kode' => 'FC1',
            'nama_sample' => 'Sample FC1',
            'limit_operator' => 'le',
            'limit_value' => 100,
            'column_name' => 'col1',
            'jenis' => 'TBS',
            'is_active' => true,
        ]);

        $payload = [
            'tanggal_sampel' => now()->format('Y-m-d'),
            'rounded_time' => '10:30',
            'rows' => [[
                'kode' => 'FC1',
                'tanggal_sampel' => now()->format('Y-m-d'),
                'rounded_time' => '10:30',
                'jenis' => 'TBS',
                'operator' => 'DONI SAPUTRA',
                'sampel_boy' => 'Aprianda Tarigan',
                'berat_sampel' => 10,
                'nut_utuh_nut' => 1,
                'nut_utuh_kernel' => 1,
                'nut_pecah_nut' => 1,
                'nut_pecah_kernel' => 1,
                'kernel_utuh' => 1,
                'kernel_pecah' => 1,
            ]],
        ];

        $response = $this->actingAs($this->user)->post(route('kernel.store'), $payload);

        $response->assertRedirect(route('kernel.index'));
        $this->assertDatabaseHas('kernel_calculations', [
            'kode' => 'FC1',
        ]);
        
        $this->assertDatabaseHas('kernel_records', [
            'kode' => 'FC1',
            'sampel_boy' => 'Boy 1',
            'jenis' => 'TBS'
        ]);
    }

    public function test_store_allows_earlier_sample_time_before_latest_normal_sample()
    {
        KernelMasterData::create([
            'office' => 'YBS',
            'kode' => 'FC1',
            'nama_sample' => 'Sample FC1',
            'limit_operator' => 'le',
            'limit_value' => 100,
            'column_name' => 'col1',
            'jenis' => 'TBS',
            'is_active' => true,
        ]);

        KernelCalculation::create([
            'user_id' => $this->user->id,
            'office' => 'YBS',
            'kode' => 'FC1',
            'jenis' => 'TBS',
            'operator' => 'DONI SAPUTRA',
            'sampel_boy' => 'Aprianda Tarigan',
            'rounded_time' => Carbon::create(2024, 1, 2, 14, 0),
            'berat_sampel' => 10,
            'nut_utuh_nut' => 1,
            'nut_utuh_kernel' => 1,
            'nut_pecah_nut' => 1,
            'nut_pecah_kernel' => 1,
            'kernel_utuh' => 1,
            'kernel_pecah' => 1,
        ]);

        $process = KernelProsses::create([
            'user_id' => $this->user->id,
            'office' => 'YBS',
            'process_date' => '2024-01-02',
            'input_team' => 'Team 1',
            'team_1_start_time' => '06:00',
            'team_1_end_time' => '22:00',
            'team_2_start_time' => '06:00',
            'team_2_end_time' => '22:00',
        ]);

        $process->mesin()->create([
            'team_name' => 'Team 1',
            'machine_group' => 'Fibre Cyclone',
            'machine_name' => 'FIBRE CYCLONE 1',
            'production_start_time' => '06:00',
            'production_end_time' => '22:00',
        ]);

        $payload = [
            'tanggal_sampel' => '2024-01-02',
            'rounded_time' => '10:00',
            'rows' => [[
                'kode' => 'FC1',
                'tanggal_sampel' => '2024-01-02',
                'rounded_time' => '10:00',
                'jenis' => 'TBS',
                'operator' => 'DONI SAPUTRA',
                'sampel_boy' => 'Aprianda Tarigan',
                'berat_sampel' => 10,
                'nut_utuh_nut' => 1,
                'nut_utuh_kernel' => 1,
                'nut_pecah_nut' => 1,
                'nut_pecah_kernel' => 1,
                'kernel_utuh' => 1,
                'kernel_pecah' => 1,
            ]],
        ];

        $response = $this->actingAs($this->user)->post(route('kernel.store'), $payload);

        $response->assertRedirect(route('kernel.index'));
        $this->assertDatabaseHas('kernel_calculations', [
            'kode' => 'FC1',
            'rounded_time' => Carbon::create(2024, 1, 2, 10, 0)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_index_filters_by_sample_date_instead_of_creation_date()
    {
        $rowOnSelectedDate = KernelCalculation::create([
            'user_id' => $this->user->id,
            'office' => 'YBS',
            'kode' => 'FC1',
            'jenis' => 'TBS',
            'operator' => 'Test Operator',
            'sampel_boy' => 'Boy 1',
            'rounded_time' => Carbon::create(2024, 1, 11, 10, 30),
            'berat_sampel' => 10,
            'nut_utuh_nut' => 1,
            'nut_utuh_kernel' => 1,
            'nut_pecah_nut' => 1,
            'nut_pecah_kernel' => 1,
            'kernel_utuh' => 1,
            'kernel_pecah' => 1,
        ]);

        $rowOnOtherDate = KernelCalculation::create([
            'user_id' => $this->user->id,
            'office' => 'YBS',
            'kode' => 'FC2',
            'jenis' => 'TBS',
            'operator' => 'Test Operator',
            'sampel_boy' => 'Boy 1',
            'rounded_time' => Carbon::create(2024, 1, 12, 10, 30),
            'berat_sampel' => 10,
            'nut_utuh_nut' => 1,
            'nut_utuh_kernel' => 1,
            'nut_pecah_nut' => 1,
            'nut_pecah_kernel' => 1,
            'kernel_utuh' => 1,
            'kernel_pecah' => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('kernel.index', [
            'start_date' => '2024-01-11',
            'end_date' => '2024-01-11',
        ]));

        $response->assertOk();
        $response->assertSee($rowOnSelectedDate->kode);
        $response->assertDontSee($rowOnOtherDate->kode);
    }

    public function test_performance_uses_sample_date_filter_for_unified_records()
    {
        $selectedRecord = KernelCalculation::create([
            'user_id' => $this->user->id,
            'office' => 'YBS',
            'kode' => 'FC1',
            'jenis' => 'TBS',
            'operator' => 'Test Operator',
            'sampel_boy' => 'Boy 1',
            'rounded_time' => Carbon::create(2024, 1, 11, 10, 30),
            'created_at' => Carbon::create(2024, 1, 10, 10, 30),
            'updated_at' => Carbon::create(2024, 1, 10, 10, 30),
            'berat_sampel' => 10,
            'nut_utuh_nut' => 1,
            'nut_utuh_kernel' => 1,
            'nut_pecah_nut' => 1,
            'nut_pecah_kernel' => 1,
            'kernel_utuh' => 1,
            'kernel_pecah' => 1,
        ]);

        $otherRecord = KernelCalculation::create([
            'user_id' => $this->user->id,
            'office' => 'YBS',
            'kode' => 'FC2',
            'jenis' => 'TBS',
            'operator' => 'Test Operator',
            'sampel_boy' => 'Boy 1',
            'rounded_time' => Carbon::create(2024, 1, 12, 10, 30),
            'created_at' => Carbon::create(2024, 1, 10, 10, 30),
            'updated_at' => Carbon::create(2024, 1, 10, 10, 30),
            'berat_sampel' => 10,
            'nut_utuh_nut' => 1,
            'nut_utuh_kernel' => 1,
            'nut_pecah_nut' => 1,
            'nut_pecah_kernel' => 1,
            'kernel_utuh' => 1,
            'kernel_pecah' => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('kernel.performance', [
            'start_date' => '2024-01-11',
            'end_date' => '2024-01-11',
        ]));

        $response->assertOk();
        $response->assertSee($selectedRecord->kode);
        $response->assertDontSee($otherRecord->kode);
    }

    public function test_performance_includes_cws1_records_in_allowed_codes()
    {
        $master = KernelMasterData::create([
            'office' => 'YBS',
            'kode' => 'CWS1',
            'nama_sample' => 'Sample CWS1',
            'limit_operator' => 'le',
            'limit_value' => 100,
            'column_name' => 'col1',
            'jenis' => 'TBS',
            'is_active' => true,
        ]);

        KernelCalculation::create([
            'user_id' => $this->user->id,
            'office' => 'YBS',
            'kode' => 'CWS1',
            'jenis' => 'TBS',
            'operator' => 'Test Operator',
            'sampel_boy' => 'Boy 1',
            'rounded_time' => Carbon::create(2024, 1, 11, 10, 30),
            'berat_sampel' => 10,
            'nut_utuh_nut' => 1,
            'nut_utuh_kernel' => 1,
            'nut_pecah_nut' => 1,
            'nut_pecah_kernel' => 1,
            'kernel_utuh' => 1,
            'kernel_pecah' => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('kernel.performance', [
            'start_date' => '2024-01-11',
            'end_date' => '2024-01-11',
        ]));

        $response->assertOk();
        $response->assertSee('CWS1');
    }

    public function test_dirt_moist_store_accepts_valid_payload()
    {
        KernelMasterData::create([
            'office' => 'YBS',
            'kode' => 'CWS',
            'nama_sample' => 'Sample CWS',
            'limit_operator' => 'le',
            'limit_value' => 100,
            'column_name' => 'col1',
            'jenis' => 'TBS',
            'is_active' => true,
        ]);

        $payload = [
            'tanggal_sampel' => now()->format('Y-m-d'),
            'rounded_time' => '10:30',
            'rows' => [[
                'kode' => 'CWS',
                'tanggal_sampel' => now()->format('Y-m-d'),
                'rounded_time' => '10:30',
                'jenis' => 'TBS',
                'operator' => 'Test Operator',
                'sampel_boy' => 'Boy 1',
                'berat_sampel' => 10,
                'berat_dirty' => 2,
                'moist_percent' => 1,
            ]],
        ];

        $response = $this->actingAs($this->user)->post(route('kernel.dirt-moist.store'), $payload);

        $response->assertRedirect();
    }

    public function test_dirt_moist_store_uses_row_sample_datetime_for_ybs()
    {
        KernelMasterData::create([
            'office' => 'YBS',
            'kode' => 'CWS',
            'nama_sample' => 'Sample CWS',
            'limit_operator' => 'le',
            'limit_value' => 100,
            'column_name' => 'col1',
            'jenis' => 'TBS',
            'is_active' => true,
        ]);

        $payload = [
            'rows' => [[
                'kode' => 'CWS',
                'tanggal_sampel' => '2024-01-02',
                'rounded_time' => '10:30',
                'jenis' => 'TBS',
                'operator' => 'Test Operator',
                'sampel_boy' => 'Boy 1',
                'berat_sampel' => 10,
                'berat_dirty' => 2,
                'moist_percent' => 1,
            ]],
        ];

        $response = $this->actingAs($this->user)->post(route('kernel.dirt-moist.store'), $payload);

        $response->assertRedirect();

        $row = KernelDirtMoistCalculation::latest()->first();
        $this->assertNotNull($row);
        $this->assertSame('2024-01-02 10:30:00', $row->rounded_time->format('Y-m-d H:i:s'));
    }
}
