<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class FirewallManager extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Firewall';
    protected static ?int $navigationSort = 17;
    protected string $view = 'filament.pages.firewall-manager';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
