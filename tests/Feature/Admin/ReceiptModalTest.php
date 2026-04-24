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

    public function test_placeholder_will_be_replaced(): void
    {
        $this->assertTrue(true);
    }
}
