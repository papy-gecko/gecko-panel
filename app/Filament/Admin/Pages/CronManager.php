<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class CronManager extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Cron Jobs';
    protected static ?int $navigationSort = 19;
    protected string $view = 'filament.pages.cron-manager';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
