<?php

namespace Tests\Feature\Filament;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceRerunAutoCheckTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Admin $notaio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::first();

        $this->notaio = Admin::create([
            'name' => 'Notaio Rerun',
            'email' => 'notaio-rerun@test.com',
            'password' => 'password',
            'role' => 'notaio',
        ]);
    }

    public function test_rerun_auto_check_resets_fields_for_auto_verification(): void
    {
        $this->actingAs($this->admin, 'admin');

        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Banned,
            'verification_type' => VerificationType::Auto,
            'notes' => 'scontrino non riconosciuto',
            'ocr_raw' => ['foo' => 'bar'],
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('rerun_auto_check', $play)
            ->assertHasNoTableActionErrors();

        $fresh = $play->fresh();
        $this->assertSame(PlayStatus::Pending, $fresh->status);
        $this->assertNull($fresh->verification_type);
        $this->assertNull($fresh->notes);
        $this->assertNull($fresh->ocr_raw);
    }

    public function test_rerun_auto_check_resets_fields_for_manual_verification(): void
    {
        $this->actingAs($this->admin, 'admin');

        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Validated,
            'verification_type' => VerificationType::Manual,
            'notes' => 'verificato a mano',
            'ocr_raw' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('rerun_auto_check', $play)
            ->assertHasNoTableActionErrors();

        $fresh = $play->fresh();
        $this->assertSame(PlayStatus::Pending, $fresh->status);
        $this->assertNull($fresh->verification_type);
        $this->assertNull($fresh->notes);
    }

    public function test_rerun_auto_check_hidden_when_verification_type_null(): void
    {
        $this->actingAs($this->admin, 'admin');

        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'verification_type' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->assertTableActionHidden('rerun_auto_check', $play);
    }

    public function test_notaio_cannot_see_rerun_auto_check_action(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'verification_type' => VerificationType::Auto,
        ]);

        Livewire::test(ListPlays::class)
            ->assertTableActionHidden('rerun_auto_check', $play);
    }

    public function test_bulk_rerun_auto_check_only_processes_auto_verification(): void
    {
        $this->actingAs($this->admin, 'admin');

        $auto = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Banned,
            'verification_type' => VerificationType::Auto,
            'notes' => 'qualcosa',
            'ocr_raw' => ['x' => 1],
        ]);
        $manual = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Validated,
            'verification_type' => VerificationType::Manual,
            'notes' => 'manuale',
        ]);
        $unverified = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'verification_type' => null,
            'notes' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableBulkAction('rerun_auto_check_bulk', [$auto, $manual, $unverified])
            ->assertHasNoTableBulkActionErrors();

        $autoFresh = $auto->fresh();
        $this->assertSame(PlayStatus::Pending, $autoFresh->status);
        $this->assertNull($autoFresh->verification_type);
        $this->assertNull($autoFresh->notes);
        $this->assertNull($autoFresh->ocr_raw);

        $manualFresh = $manual->fresh();
        $this->assertSame(PlayStatus::Validated, $manualFresh->status);
        $this->assertSame(VerificationType::Manual, $manualFresh->verification_type);
        $this->assertSame('manuale', $manualFresh->notes);

        $unverifiedFresh = $unverified->fresh();
        $this->assertSame(PlayStatus::Pending, $unverifiedFresh->status);
        $this->assertNull($unverifiedFresh->verification_type);
    }

    public function test_notaio_cannot_see_bulk_rerun_auto_check(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(ListPlays::class)
            ->assertTableBulkActionHidden('rerun_auto_check_bulk');
    }
}
