<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class SystemdServices extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationLabel = 'Services';
    protected static ?string $title = 'Services Systemd';
    protected static ?int $navigationSort = 12;
    protected string $view = 'filament.pages.systemd-services';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
