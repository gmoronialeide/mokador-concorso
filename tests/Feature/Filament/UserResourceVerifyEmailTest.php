<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\Admin;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceVerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Admin $notaio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::first();

        $this->notaio = Admin::create([
            'name' => 'Notaio Test',
            'email' => 'notaio@verify.test',
            'password' => 'password',
            'role' => 'notaio',
        ]);
    }

    public function test_admin_can_verify_unverified_user(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create(['email_verified_at' => null]);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('verify_email')->table($user->id))
            ->assertHasNoActionErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_notaio_cannot_verify_user(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create(['email_verified_at' => null]);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('verify_email')->table($user->id));
    }

    public function test_action_hidden_when_user_already_verified(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('verify_email')->table($user->id));
    }

    public function test_verification_link_ttl_config_is_30_days(): void
    {
        $this->assertSame(43200, config('auth.verification.expire'));
    }

    public function test_admin_can_verify_from_view_page(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create(['email_verified_at' => null]);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->callAction('verify_email')
            ->assertHasNoActionErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_view_page_action_hidden_for_notaio(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create(['email_verified_at' => null]);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->assertActionHidden('verify_email');
    }

    public function test_activation_also_removes_ban(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_banned' => true,
            'ban_reason' => 'Da verificare',
        ]);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('verify_email')->table($user->id))
            ->assertHasNoActionErrors();

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->email_verified_at);
        $this->assertFalse($fresh->is_banned);
        $this->assertNull($fresh->ban_reason);
    }

    public function test_bulk_action_verifies_and_activates_many(): void
    {
        $this->actingAs($this->admin, 'admin');

        $u1 = User::factory()->create(['email_verified_at' => null, 'is_banned' => false]);
        $u2 = User::factory()->create(['email_verified_at' => null, 'is_banned' => true, 'ban_reason' => 'x']);
        $u3 = User::factory()->create(['email_verified_at' => now()]);

        Livewire::test(ListUsers::class)
            ->set('selectedTableRecords', [$u1->id, $u2->id, $u3->id])
            ->callAction(TestAction::make('verify_email_selected')->table()->bulk())
            ->assertHasNoActionErrors();

        $this->assertNotNull($u1->fresh()->email_verified_at);
        $this->assertFalse($u2->fresh()->is_banned);
        $this->assertNotNull($u2->fresh()->email_verified_at);
        $this->assertNotNull($u3->fresh()->email_verified_at);
    }
}
