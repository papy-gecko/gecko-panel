<?php
namespace App\Filament\Admin\Resources\MonitorResource\Pages;
use App\Enums\TablerIcon;
use App\Filament\Admin\Resources\MonitorResource;
use App\Traits\Filament\CanCustomizeHeaderActions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateMonitor extends CreateRecord
{
    use CanCustomizeHeaderActions;

    protected static string $resource = MonitorResource::class;
    protected static bool $canCreateAnother = false;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Action::make('create')
                ->hiddenLabel()
                ->action('create')
                ->keyBindings(['mod+s'])
                ->tooltip('Sauvegarder')
                ->icon(TablerIcon::FilePlus),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
