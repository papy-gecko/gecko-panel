<?php
namespace App\Filament\Admin\Resources;

use App\Models\Monitor;
use App\Filament\Admin\Resources\MonitorResource\Pages;
use App\Traits\Filament\CanModifyForm;
use App\Traits\Filament\CanModifyTable;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class MonitorResource extends Resource
{
    use CanModifyForm;
    use CanModifyTable;

    protected static ?string $model = Monitor::class;
    protected static string|BackedEnum|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Sondes';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string { return 'Monitoring'; }

    public static function defaultForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('Nom')->required(),
                Select::make('type')->label('Type')->options(['http' => 'HTTP', 'tcp' => 'TCP', 'ping' => 'Ping'])->required()->live(),
                TextInput::make('target')->label('Cible')->required(),
                TextInput::make('port')->label('Port')->numeric()->visible(fn($get) => $get('type') === 'tcp'),
                TextInput::make('interval')->label('Intervalle (s)')->numeric()->default(60),
                TextInput::make('timeout')->label('Timeout (s)')->numeric()->default(10),
                TextInput::make('discord_webhook')->label('Discord Webhook')->nullable()->columnSpanFull(),
                Toggle::make('active')->label('Actif')->default(true),
            ]),
        ]);
    }

    public static function defaultTable(Table $table): Table
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
            CreateAction::make()->label('Ajouter'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitors::route('/'),
            'create' => Pages\CreateMonitor::route('/create'),
            'edit' => Pages\EditMonitor::route('/{record}/edit'),
        ];
    }
}
