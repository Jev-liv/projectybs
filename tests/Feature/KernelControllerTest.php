<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\KernelMasterData;
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
}
