<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientAdminSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_admin_is_seeded_with_admin_role(): void
    {
        $admin = Admin::where('email', 'concorso@mokador.it')->first();

        $this->assertNotNull($admin, 'Client admin concorso@mokador.it must exist after migrations.');
        $this->assertSame('Mokador', $admin->name);
        $this->assertSame(AdminRole::Admin, $admin->role);
    }

    public function test_client_admin_password_is_hashed_and_valid(): void
    {
        $admin = Admin::where('email', 'concorso@mokador.it')->firstOrFail();

        $this->assertTrue(Hash::check('Mok4dorC0nc0rsO?', $admin->password));
    }

    public function test_client_admin_has_full_admin_privileges(): void
    {
        $admin = Admin::where('email', 'concorso@mokador.it')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isNotaio());
    }
}
