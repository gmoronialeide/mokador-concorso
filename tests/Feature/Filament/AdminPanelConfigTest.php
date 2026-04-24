<?php

namespace Tests\Feature\Filament;

use Filament\Facades\Filament;
use Tests\TestCase;

class AdminPanelConfigTest extends TestCase
{
    public function test_admin_panel_has_fully_collapsible_sidebar_on_desktop(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->isSidebarFullyCollapsibleOnDesktop());
    }
}
