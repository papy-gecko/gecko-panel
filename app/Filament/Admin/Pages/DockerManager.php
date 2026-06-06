<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class DockerManager extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Docker';
    protected static ?string $title = 'Docker Manager';
    protected static ?int $navigationSort = 11;
    protected string $view = 'filament.pages.docker-manager';
    public static function getNavigationGroup(): ?string { return 'Docker'; }
}
