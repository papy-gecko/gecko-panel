<?php
namespace App\Filament\Admin\Resources\MonitorResource\Pages;
use App\Enums\TablerIcon;
use App\Filament\Admin\Resources\MonitorResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMonitor extends EditRecord
{
    protected static string $resource = MonitorResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('save')
                ->hiddenLabel()
                ->action('save')
                ->keyBindings(['mod+s'])
                ->tooltip('Sauvegarder')
                ->icon(TablerIcon::DeviceFloppy),
        ];
    }

    protected function getFormActions(): array { return []; }
}
