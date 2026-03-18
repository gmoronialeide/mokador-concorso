<?php

namespace Tests\Feature;

use App\Filament\Pages\FinalDraw;
use App\Models\Admin;
use App\Models\FinalDrawResult;
use App\Models\FinalPrize;
use App\Models\Play;
use App\Models\User;
use App\Services\FinalDrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinalDrawPageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::first();
    }

    private function createUserWithPlays(int $playsCount): User
    {
        $user = User::factory()->create();

        for ($i = 0; $i < $playsCount; $i++) {
            Play::create([
                'user_id' => $user->id,
                'store_code' => 'STORE01',
                'receipt_image' => 'receipts/test.jpg',
                'played_at' => now()->subDays($i),
                'is_winner' => false,
                'is_banned' => false,
            ]);
        }

        return $user;
    }

    public function test_page_requires_admin_authentication(): void
    {
        $this->get('/admin/final-draw')
            ->assertRedirect();
    }

    public function test_page_accessible_by_authenticated_admin(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get('/admin/final-draw')
            ->assertOk();
    }

    public function test_draw_winners_action_hidden_when_contest_running(): void
    {
        config(['app.concorso_end_date' => now()->addMonth()->toDateString()]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->assertDontSee('Estrai Vincitori');
    }

    public function test_draw_winners_action_visible_when_contest_ended(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(2);
        }

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->assertSee('Estrai Vincitori');
    }

    public function test_draw_substitutes_action_hidden_without_winners(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->assertActionHidden('drawSubstitutes');
    }

    public function test_draw_winners_creates_3_winners(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->callAction('drawWinners');

        $this->assertEquals(3, FinalDrawResult::where('role', 'winner')->count());
        $this->assertEquals(3, FinalPrize::whereNotNull('drawn_at')->count());
    }

    public function test_draw_substitutes_creates_9_substitutes(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $service = app(FinalDrawService::class);
        $service->drawWinners($this->admin);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->callAction('drawSubstitutes');

        $this->assertEquals(9, FinalDrawResult::where('role', 'substitute')->count());
    }

    public function test_results_displayed_after_draw(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $service = app(FinalDrawService::class);
        $service->drawWinners($this->admin);
        $service->drawSubstitutes($this->admin);

        $winner = FinalDrawResult::where('role', 'winner')->first();

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->assertSee($winner->user->email)
            ->assertSee('Vincitore')
            ->assertSee('Sostituti');
    }

    public function test_reset_all_clears_results(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $service = app(FinalDrawService::class);
        $service->drawWinners($this->admin);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->callAction('resetAll');

        $this->assertEquals(0, FinalDrawResult::count());
        $this->assertEquals(0, FinalPrize::whereNotNull('drawn_at')->count());
    }

    public function test_reset_substitutes_preserves_winners(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $service = app(FinalDrawService::class);
        $service->drawWinners($this->admin);
        $service->drawSubstitutes($this->admin);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->callAction('resetSubstitutes');

        $this->assertEquals(3, FinalDrawResult::where('role', 'winner')->count());
        $this->assertEquals(0, FinalDrawResult::where('role', 'substitute')->count());
    }

    public function test_export_verbale_hidden_when_not_fully_drawn(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->assertActionHidden('exportVerbale');
    }

    public function test_export_verbale_visible_when_fully_drawn(): void
    {
        config(['app.concorso_end_date' => now()->subDay()->toDateString()]);

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $service = app(FinalDrawService::class);
        $service->drawWinners($this->admin);
        $service->drawSubstitutes($this->admin);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->assertActionVisible('exportVerbale');
    }

    public function test_contest_warning_shown_when_running(): void
    {
        config(['app.concorso_end_date' => now()->addMonth()->toDateString()]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(FinalDraw::class)
            ->assertSee('Il concorso è ancora in corso');
    }
}
