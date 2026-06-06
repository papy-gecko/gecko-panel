<?php
namespace App\Filament\Admin\Pages;

use App\Models\Monitor;
use App\Jobs\CheckMonitors;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class UptimeKuma extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Monitoring';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.uptime-kuma';
    public function getTitle(): string { return 'Monitoring'; }
    public static function getNavigationGroup(): ?string { return 'Monitoring'; }

    public function checkNow(): void
    {
        dispatch(new CheckMonitors());
        Notification::make()->title('Verification lancee !')->success()->send();
    }

    public function getMonitors(): \Illuminate\Database\Eloquent\Collection
    {
        return Monitor::with(['logs' => function($q) {
            $q->orderBy('checked_at', 'desc')->limit(60);
        }])->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check_now')
                ->label('Verifier maintenant')
                ->icon('heroicon-o-arrow-path')
                ->action('checkNow'),
            Action::make('add_monitor')
                ->label('Ajouter une sonde')
                ->icon('heroicon-o-plus')
                ->url('/admin/monitors/create')
                ->color('success'),
        ];
    }
}
