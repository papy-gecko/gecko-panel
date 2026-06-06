<?php

namespace App\Filament\Admin\Widgets;

class HelpWidget extends FormWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([]);
    }
}
