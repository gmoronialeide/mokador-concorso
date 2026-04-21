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

class PlayResourceSearchTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private User $mario;

    private User $luca;

    private Play $marioPlay;

    private Play $lucaPlay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::first();

        $this->mario = User::factory()->create([
            'name' => 'Mario',
            'surname' => 'Rossi',
        ]);

        $this->luca = User::factory()->create([
            'name' => 'Luca',
            'surname' => 'Bianchi',
        ]);

        $store1 = Store::factory()->create([
            'code' => 'STORE01',
            'name' => 'Bar Roma',
            'sign_name' => '',
            'city' => 'Milano',
            'province' => 'MI',
            'address' => 'Via Roma 12',
            'cap' => '20121',
        ]);

        $store2 = Store::factory()->create([
            'code' => 'STORE02',
            'name' => 'Caffetteria Dante',
            'sign_name' => 'Da Dante',
            'city' => 'Torino',
            'province' => 'TO',
            'address' => 'Via Dante 5',
            'cap' => '10121',
        ]);

        $this->marioPlay = Play::factory()->forStore($store1)->create([
            'user_id' => $this->mario->id,
            'receipt_image' => 'receipts/mario.jpg',
            'played_at' => now(),
        ]);

        $this->lucaPlay = Play::factory()->forStore($store2)->create([
            'user_id' => $this->luca->id,
            'receipt_image' => 'receipts/luca.jpg',
            'played_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_search_by_first_name_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'mario')
            ->assertCanSeeTableRecords([$this->marioPlay])
            ->assertCanNotSeeTableRecords([$this->lucaPlay]);
    }

    public function test_search_by_surname_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'rossi')
            ->assertCanSeeTableRecords([$this->marioPlay])
            ->assertCanNotSeeTableRecords([$this->lucaPlay]);
    }

    public function test_search_by_full_name_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'mario rossi')
            ->assertCanSeeTableRecords([$this->marioPlay])
            ->assertCanNotSeeTableRecords([$this->lucaPlay]);
    }

    public function test_search_by_surname_first_also_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'rossi mario')
            ->assertCanSeeTableRecords([$this->marioPlay])
            ->assertCanNotSeeTableRecords([$this->lucaPlay]);
    }

    public function test_search_with_no_match_shows_no_records(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'nonesiste')
            ->assertCanNotSeeTableRecords([$this->marioPlay, $this->lucaPlay]);
    }

    public function test_search_by_store_code_still_works(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'STORE01')
            ->assertCanSeeTableRecords([$this->marioPlay])
            ->assertCanNotSeeTableRecords([$this->lucaPlay]);
    }

    public function test_search_by_store_name_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'Bar Roma')
            ->assertCanSeeTableRecords([$this->marioPlay])
            ->assertCanNotSeeTableRecords([$this->lucaPlay]);
    }

    public function test_search_by_store_sign_name_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'Da Dante')
            ->assertCanSeeTableRecords([$this->lucaPlay])
            ->assertCanNotSeeTableRecords([$this->marioPlay]);
    }

    public function test_search_by_store_city_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'Milano')
            ->assertCanSeeTableRecords([$this->marioPlay])
            ->assertCanNotSeeTableRecords([$this->lucaPlay]);
    }

    public function test_search_by_store_province_finds_play(): void
    {
        Livewire::test(ListPlays::class)
            ->set('tableSearch', 'Torino')
            ->assertCanSeeTableRecords([$this->lucaPlay])
            ->assertCanNotSeeTableRecords([$this->marioPlay]);
    }

    public function test_list_column_renders_store_code_with_tooltip_data(): void
    {
        Livewire::test(ListPlays::class)
            ->assertSee('STORE01')
            ->assertSee('STORE02')
            ->assertSee('Bar Roma (Milano, MI)')
            ->assertSee('Da Dante (Torino, TO)');
    }
}
