<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\TablerIcon;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UpdateWidget extends FormWidget
{
    protected static ?int $sort = 0;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Votre panel Gecko est à jour')
                    ->icon(TablerIcon::Checkbox)
                    ->iconColor('success')
                    ->schema([
                        TextEntry::make('info')
                            ->hiddenLabel()
                            ->state('Vous utilisez Gecko v1.0.0.'),
                        Section::make('Quoi de neuf ?')
                            ->icon(TablerIcon::Script)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                TextEntry::make('changelog')
                                    ->hiddenLabel()
                                    ->markdown()
                                    ->state(implode("\n", [
                                        '- **Terminal web** — Terminal bash intégré avec support PTY (nano, vim, htop...)',
                                        '- **Gestionnaire Docker** — Supervision et gestion des conteneurs Docker',
                                        '- **Docker Compose** — Déploiement de stacks depuis le panel',
                                        '- **Gestionnaire de processus** — Vue et contrôle des processus système',
                                        '- **Services Systemd** — Liste et gestion des services',
                                        '- **Gestionnaire Cron** — Planification de tâches depuis le panel',
                                        '- **Pare-feu (UFW)** — Gestion des règles firewall',
                                        '- **Fail2ban** — Supervision des bans IP',
                                        '- **Uptime Kuma** — Intégration monitoring',
                                        '- **Gestionnaire de fichiers admin** — Explorateur de fichiers système',
                                        '- **Téléchargement de dossiers** — Compression et téléchargement ZIP à la volée',
                                        '- **Base de données automatique** — Création MySQL automatique à chaque nouveau serveur',
                                        '- **Page credentials DB** — Identifiants de connexion par serveur (local, Docker, phpMyAdmin)',
                                        '- **Gestionnaire de BDD admin** — Gestion centralisée de toutes les bases de données',
                                    ])),
                            ]),
                    ]),
            ]);
    }
}
