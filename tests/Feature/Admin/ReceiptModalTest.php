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

        $this->assertStringContainsString("\$wire.replaceMountedAction('validate', { nextId: nextId, ids: ids }, { table: true, recordKey: '{$play->id}' })", $html);
        $this->assertStringNotContainsString('@js(', $html);
    }

    public function test_ban_button_visible_when_play_not_banned(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);
        $this->actingAs($admin, 'admin');

        $play = $this->makePlay(['status' => PlayStatus::Validated]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString("\$wire.replaceMountedAction('ban', { nextId: nextId, ids: ids }, { table: true, recordKey: '{$play->id}' })", $html);
        $this->assertStringNotContainsString('@js(', $html);
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

        $this->assertStringContainsString("\$wire.replaceMountedAction('unban', { nextId: nextId, ids: ids }, { table: true, recordKey: '{$play->id}' })", $html);
        $this->assertStringNotContainsString("replaceMountedAction('ban'", $html);
        $this->assertStringNotContainsString("replaceMountedAction('validate'", $html);
        $this->assertStringNotContainsString('@js(', $html);
    }

    public function test_keyboard_run_validate_passes_next_id(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);
        $this->actingAs($admin, 'admin');

        $play = $this->makePlay();
        $html = $this->renderModal($play);

        $this->assertStringContainsString("run('validate', true)", $html);
        $this->assertStringContainsString("run('unban', true)", $html);
        $this->assertStringContainsString("run('ban', true)", $html);
    }

    public function test_notaio_sees_no_action_buttons(): void
    {
        $notaio = Admin::factory()->create(['role' => AdminRole::Notaio]);
        $this->actingAs($notaio, 'admin');

        $play = $this->makePlay(['status' => PlayStatus::Pending]);

        $html = $this->renderModal($play);

        $this->assertStringNotContainsString("replaceMountedAction('validate'", $html);
        $this->assertStringNotContainsString("replaceMountedAction('ban'", $html);
        $this->assertStringNotContainsString("replaceMountedAction('unban'", $html);
    }

    public function test_guest_sees_no_action_buttons(): void
    {
        $play = $this->makePlay(['status' => PlayStatus::Pending]);

        $html = $this->renderModal($play);

        $this->assertStringNotContainsString("replaceMountedAction('validate'", $html);
        $this->assertStringNotContainsString("replaceMountedAction('ban'", $html);
    }

    public function test_modal_header_shows_play_id_and_user_name(): void
    {
        $user = User::factory()->create(['name' => 'Mario', 'surname' => 'Rossi']);
        $play = Play::factory()->create([
            'user_id' => $user->id,
            'store_code' => 'TEST01',
            'status' => PlayStatus::Pending,
        ]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString('ID '.$play->id, $html);
        $this->assertStringContainsString('Mario Rossi', $html);
    }

    public function test_modal_header_omits_user_name_when_user_missing(): void
    {
        $play = $this->makePlay(['store_code' => 'TEST01']);
        $play->setRelation('user', null);

        $html = $this->renderModal($play);

        $this->assertStringContainsString('ID '.$play->id, $html);
        $this->assertStringNotContainsString(' - - ', $html);
    }

    public function test_modal_shows_notes_when_present(): void
    {
        $play = $this->makePlay(['notes' => 'Scontrino sbiadito, parziale lettura']);

        $html = $this->renderModal($play);

        $this->assertStringContainsString('Scontrino sbiadito, parziale lettura', $html);
        $this->assertStringContainsString('Note', $html);
    }

    public function test_modal_omits_notes_block_when_notes_null(): void
    {
        $play = $this->makePlay(['notes' => null]);

        $html = $this->renderModal($play);

        $this->assertStringNotContainsString('<strong>Note', $html);
    }

    public function test_modal_shows_ocr_unavailable_when_ocr_raw_null(): void
    {
        $play = $this->makePlay(['ocr_raw' => null]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString('Lettura automatica non disponibile', $html);
    }

    public function test_modal_shows_ocr_unavailable_when_parser_returns_null(): void
    {
        $play = $this->makePlay(['ocr_raw' => ['analyzeResult' => ['documents' => []]]]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString('Lettura automatica non disponibile', $html);
    }

    public function test_modal_renders_counter_default_one_of_one(): void
    {
        $play = $this->makePlay();
        $html = $this->renderModal($play);
        $this->assertStringContainsString('Scontrino 1 / 1', $html);
    }

    public function test_modal_renders_counter_with_provided_ids(): void
    {
        $play = $this->makePlay();
        $html = view('filament.modals.receipt-preview', [
            'record' => $play,
            'ids' => [10, $play->id, 20, 30],
        ])->render();
        $this->assertStringContainsString('Scontrino 2 / 4', $html);
    }

    public function test_modal_renders_prev_next_buttons(): void
    {
        $play = $this->makePlay();
        $html = $this->renderModal($play);
        $this->assertStringContainsString('Precedente', $html);
        $this->assertStringContainsString('Successivo', $html);
    }

    public function test_modal_disables_prev_when_first(): void
    {
        $play = $this->makePlay();
        $html = view('filament.modals.receipt-preview', [
            'record' => $play,
            'ids' => [$play->id, 20, 30],
        ])->render();
        $this->assertMatchesRegularExpression(
            '/<button[^>]*disabled[^>]*>[^<]*Precedente/s',
            $html,
        );
    }

    public function test_modal_disables_next_when_last(): void
    {
        $play = $this->makePlay();
        $html = view('filament.modals.receipt-preview', [
            'record' => $play,
            'ids' => [10, 20, $play->id],
        ])->render();
        $this->assertMatchesRegularExpression(
            '/<button[^>]*disabled[^>]*>[^<]*Successivo/s',
            $html,
        );
    }

    public function test_modal_renders_keyboard_listener(): void
    {
        $play = $this->makePlay();
        $html = $this->renderModal($play);
        $this->assertStringContainsString('x-on:keydown.window', $html);
        $this->assertStringContainsString('ArrowLeft', $html);
        $this->assertStringContainsString('ArrowRight', $html);
    }

    public function test_modal_shows_ocr_data_when_ocr_raw_populated(): void
    {
        $play = $this->makePlay(['ocr_raw' => [
            'analyzeResult' => [
                'documents' => [[
                    'docType' => 'receipt.retailMeal',
                    'fields' => [
                        'MerchantName' => ['valueString' => 'Bar Centrale', 'confidence' => 0.95],
                        'MerchantAddress' => ['valueAddress' => [
                            'streetAddress' => 'Via Roma 1',
                            'postalCode' => '48018',
                            'city' => 'Faenza',
                            'state' => 'RA',
                        ]],
                        'MerchantTaxId' => ['valueString' => '01234567890'],
                        'TransactionDate' => ['valueDate' => '2026-04-20'],
                        'Total' => ['valueCurrency' => ['amount' => 12.5]],
                        'Items' => ['valueArray' => [['x' => 1], ['x' => 2], ['x' => 3]]],
                    ],
                ]],
            ],
        ]]);

        $html = $this->renderModal($play);

        $this->assertStringContainsString('Bar Centrale', $html);
        $this->assertStringContainsString('2026-04-20', $html);
        $this->assertStringContainsString('€ 12,50', $html);
        $this->assertStringContainsString('01234567890', $html);
        $this->assertStringContainsString('95%', $html);
        $this->assertStringContainsString('Scontrino', $html);
        $this->assertStringContainsString('Via Roma 1', $html);
        $this->assertStringContainsString('3 articoli', $html);
    }
}
