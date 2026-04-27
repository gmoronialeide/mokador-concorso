<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceReceiptModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::first(), 'admin');
    }

    public function test_modal_shows_store_vat_number_when_present(): void
    {
        $store = Store::factory()->create([
            'vat_number' => '12345678901',
        ]);
        $play = Play::factory()->forStore($store)->create([
            'user_id' => User::factory(),
        ]);

        $component = Livewire::test(ListPlays::class)
            ->mountTableAction('receipt', $play->getKey());

        $content = $component->instance()->getMountedAction()->getModalContent();
        $html = is_object($content) && method_exists($content, 'render')
            ? $content->render()
            : (string) $content;

        $this->assertStringContainsString('P. IVA: 12345678901', $html);
    }

    public function test_modal_hides_store_vat_number_when_empty(): void
    {
        $store = Store::factory()->create([
            'vat_number' => '',
        ]);
        $play = Play::factory()->forStore($store)->create([
            'user_id' => User::factory(),
        ]);

        $component = Livewire::test(ListPlays::class)
            ->mountTableAction('receipt', $play->getKey());

        $content = $component->instance()->getMountedAction()->getModalContent();
        $html = is_object($content) && method_exists($content, 'render')
            ? $content->render()
            : (string) $content;

        $this->assertStringNotContainsString('P. IVA:', $html);
    }
}
