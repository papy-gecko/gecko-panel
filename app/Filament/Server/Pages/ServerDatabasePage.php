<?php

namespace App\Filament\Server\Pages;

use App\Enums\TablerIcon;
use App\Models\ServerDatabase;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ServerDatabasePage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = TablerIcon::DatabaseCog;
    protected static ?string $navigationLabel = 'Base de données';
    protected static ?string $title = 'Base de données';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.server.pages.server-database';

    public ?ServerDatabase $database = null;

    public function mount(): void
    {
        $server = Filament::getTenant();
        $this->database = ServerDatabase::where('server_id', $server->id)->first();
    }

    public function getPhpMyAdminUrl(): string
    {
        if (!$this->database) return '#';

        $password = $this->database->db_password;

        $token = base64_encode(json_encode([
            'user' => $this->database->db_user,
            'password' => $password,
            'db' => $this->database->db_name,
            'expires' => now()->addMinutes(15)->timestamp,
        ]));

        return '/pma-sso?token=' . urlencode($token);
    }

    public function copyPassword(): void
    {
        Notification::make()->title('Mot de passe copié')->success()->send();
    }

    public function getDecryptedPassword(): string
    {
        if (!$this->database) return '';
        return $this->database->db_password;
    }
}
