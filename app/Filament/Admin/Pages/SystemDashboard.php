<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class SystemDashboard extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Système';
    protected static ?string $title = 'Dashboard Système';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.system-dashboard';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
