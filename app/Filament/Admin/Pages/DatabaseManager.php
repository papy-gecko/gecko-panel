<?php

namespace App\Filament\Admin\Pages;

use App\Enums\TablerIcon;
use App\Models\Server;
use App\Models\ServerDatabase;
use Filament\Actions\Action as TableAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = TablerIcon::DatabaseCog;
    protected static ?string $navigationLabel = 'Bases de données';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.database-manager';

    public static function getNavigationGroup(): ?string
    {
        return trans('admin/dashboard.advanced');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ServerDatabase::query()->with('server'))
            ->columns([
                TextColumn::make('db_name')
                    ->label('Base de données')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('db_user')
                    ->label('Utilisateur')
                    ->copyable(),
                TextColumn::make('server.name')
                    ->label('Serveur')
                    ->default('—'),
                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                TableAction::make('phpmyadmin')
                    ->label('phpMyAdmin')
                    ->icon(TablerIcon::ExternalLink)
                    ->url(fn (ServerDatabase $record) => '/pma-sso?token=' . urlencode(base64_encode(json_encode([
                        'user' => $record->db_user,
                        'password' => $record->db_password,
                        'db' => $record->db_name,
                        'expires' => now()->addMinutes(15)->timestamp,
                    ]))))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->action(function (ServerDatabase $record) {
                        DB::connection('mysql_admin')->statement("DROP DATABASE IF EXISTS `{$record->db_name}`");
                        DB::connection('mysql_admin')->statement("DROP USER IF EXISTS '{$record->db_user}'@'%'");
                        DB::connection('mysql_admin')->statement("FLUSH PRIVILEGES");
                        $record->delete();
                        Notification::make()->title('Base de données supprimée')->success()->send();
                    }),
            ])
            ->headerActions([
                TableAction::make('create')
                    ->label('Créer une base de données')
                    ->icon(TablerIcon::Plus)
                    ->form([
                        TextInput::make('db_name')
                            ->label('Nom de la base de données')
                            ->required()
                            ->alphaNum()
                            ->maxLength(50),
                    ])
                    ->action(function (array $data) {
                        $dbName = $data['db_name'];
                        $dbUser = 'man_' . Str::random(8);
                        $dbPassword = Str::random(24);

                        DB::connection('mysql_admin')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
                        DB::connection('mysql_admin')->statement("CREATE USER '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}'");
                        DB::connection('mysql_admin')->statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%'");
                        DB::connection('mysql_admin')->statement("FLUSH PRIVILEGES");

                        ServerDatabase::create([
                            'server_id' => null,
                            'db_name'   => $dbName,
                            'db_user'   => $dbUser,
                            'db_password' => $dbPassword,
                        ]);

                        Notification::make()->title('Base de données créée')->success()->send();
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
