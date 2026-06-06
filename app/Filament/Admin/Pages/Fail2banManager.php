<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class Fail2banManager extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-no-symbol';
    protected static ?string $navigationLabel = 'Fail2ban';
    protected static ?int $navigationSort = 18;
    protected string $view = 'filament.pages.fail2ban-manager';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
