<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Enums\PlayStatus;
use App\Models\Admin;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptModalTest extends TestCase
{
    use RefreshDatabase;

    private function makePlay(array $attrs = [], ?Store $store = null): Play
    {
        $user = User::factory()->create();

        return Play::factory()->create(array_merge([
            'user_id' => $user->id,
            'store_id' => $store?->id,
            'store_code' => $store?->code ?? 'TEST01',
            'status' => PlayStatus::Pending,
        ], $attrs));
    }

    private function renderModal(Play $play): string
    {
        return view('filament.modals.receipt-preview', ['record' => $play])->render();
    }

    public function test_modal_header_shows_store_display_name_when_store_assigned(): void
    {
        $store = Store::factory()->create([
            'code' => 'MK001',
            'name' => 'Mokador Centro',
            'sign_name' => 'Mokador',
            'city' => 'Faenza',
            'province' => 'RA',
        ]);
        $play = $this->makePlay(['store_code' => 'MK001'], $store);

        $html = $this->renderModal($play);

        $this->assertStringContainsString($store->display_name, $html);
        $this->assertStringContainsString('Faenza', $html);
        $this->assertStringContainsString('RA', $html);
    }

    public function test_modal_header_shows_store_code_when_store_missing(): void
    {
        $play = $this->makePlay(['store_code' => 'ORPHAN99'], null);

        $html = $this->renderModal($play);

        $this->assertStringContainsString('ORPHAN99', $html);
        $this->assertStringContainsString('non assegnato', $html);
    }

    public function test_validate_button_visible_for_pending_play_as_admin(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);
        $this->actingAs($admin, 'admin');

        $play = $this->makePlay(['status' => PlayStatus::Pending]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString("mountTableAction('validate'", $html);
        $this->assertStringContainsString((string) $play->id, $html);
    }

    public function test_ban_button_visible_when_play_not_banned(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);
        $this->actingAs($admin, 'admin');

        $play = $this->makePlay(['status' => PlayStatus::Validated]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString("mountTableAction('ban'", $html);
    }

    public function test_unban_button_visible_when_play_banned(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);
        $this->actingAs($admin, 'admin');

        $play = $this->makePlay([
            'status' => PlayStatus::Banned,
            'banned_at' => now(),
            'ban_reason' => 'test',
        ]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString("mountTableAction('unban'", $html);
        $this->assertStringNotContainsString("mountTableAction('ban'", $html);
        $this->assertStringNotContainsString("mountTableAction('validate'", $html);
    }
}
