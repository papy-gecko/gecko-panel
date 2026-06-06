<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class LogViewer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Logs';
    protected static ?int $navigationSort = 15;
    protected string $view = 'filament.pages.log-viewer';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
