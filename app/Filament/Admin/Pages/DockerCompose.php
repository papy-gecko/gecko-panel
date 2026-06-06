<?php
namespace App\Filament\Admin\Pages;
use Filament\Pages\Page;
class DockerCompose extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Docker Compose';
    protected static ?int $navigationSort = 20;
    protected string $view = 'filament.pages.docker-compose';
    public static function getNavigationGroup(): ?string { return 'Docker'; }
}
