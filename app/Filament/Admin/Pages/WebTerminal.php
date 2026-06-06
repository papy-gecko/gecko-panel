<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class WebTerminal extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-command-line';
    protected static ?string $navigationLabel = 'Terminal';
    protected static ?string $title = 'Terminal Web';
    protected static ?int $navigationSort = 14;
    protected string $view = 'filament.pages.web-terminal';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
