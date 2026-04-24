<?php

namespace Tests\Feature\Observers;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_status_change_marks_verification_type_manual(): void
    {
        $admin = Admin::first();
        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Pending,
        ]);

        $this->actingAs($admin, 'admin');

        $play->update(['status' => PlayStatus::Validated]);

        $this->assertSame(VerificationType::Manual, $play->fresh()->verification_type);
    }

    public function test_no_admin_auth_does_not_mark_manual(): void
    {
        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Pending,
        ]);

        $play->update(['status' => PlayStatus::Validated]);

        $this->assertNull($play->fresh()->verification_type);
    }

    public function test_admin_updating_unrelated_field_does_not_touch_verification_type(): void
    {
        $admin = Admin::first();
        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Pending,
        ]);

        $this->actingAs($admin, 'admin');

        $play->update(['notes' => 'ciao']);

        $this->assertNull($play->fresh()->verification_type);
    }
}
