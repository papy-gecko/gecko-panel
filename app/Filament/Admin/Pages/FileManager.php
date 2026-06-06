<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class FileManager extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationLabel = 'Fichiers';
    protected static ?int $navigationSort = 16;
    protected string $view = 'filament.pages.file-manager';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
