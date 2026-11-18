<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardWelcome extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dashboard-welcome';
}
