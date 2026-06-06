<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class ProcessManager extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Processus';
    protected static ?string $title = 'Gestionnaire de Processus';
    protected static ?int $navigationSort = 13;
    protected string $view = 'filament.pages.process-manager';
    public static function getNavigationGroup(): ?string { return 'Cockpit'; }
}
