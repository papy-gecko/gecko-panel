<?php
namespace App\Filament\Admin\Resources\MonitorResource\Pages;
use App\Filament\Admin\Resources\MonitorResource;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class ListMonitors extends ListRecords
{
    protected static string $resource = MonitorResource::class;

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nom')->searchable(),
            TextColumn::make('type')->label('Type')->badge(),
            TextColumn::make('target')->label('Cible'),
            TextColumn::make('status')->label('Statut')->badge()->color(fn($state) => match($state) { 'up' => 'success', 'down' => 'danger', default => 'warning' }),
            TextColumn::make('latency')->label('Latence')->suffix('ms'),
            IconColumn::make('active')->label('Actif')->boolean(),
            TextColumn::make('last_checked_at')->label('Dernier check')->since(),
        ])
        ->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->toolbarActions([
            CreateAction::make()->label('Ajouter une sonde'),
        ]);
    }
}
