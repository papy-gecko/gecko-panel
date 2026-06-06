<div align="center">
  <img src="public/gecko.png" alt="Gecko Panel" width="150"/>

  # Gecko Panel

  ![Version](https://img.shields.io/badge/version-1.0.0-green)
  ![PHP](https://img.shields.io/badge/PHP-8.2-blue)
  ![License](https://img.shields.io/badge/license-AGPL--3.0-orange)

  Gecko Panel est un panel open-source de gestion de serveurs de jeu, basé sur [Pelican](https://github.com/pelican-dev/panel). Il offre une interface moderne pour déployer, configurer et gérer vos serveurs de jeu, avec des fonctionnalités avancées comme la gestion automatique des bases de données, un terminal web intégré, et bien plus.

</div>

## Liens

- [Discord](#)
- [Wings](https://github.com/pelican-dev/wings)
- [Pelican](https://github.com/pelican-dev/panel)

## Fonctionnalités supplémentaires vs Pelican

| Fonctionnalité | Description |
|---|---|
| **Base de données automatique** | Création MySQL automatique à chaque nouveau serveur |
| **Page credentials DB** | Identifiants de connexion par serveur (local, Docker, phpMyAdmin) |
| **Gestionnaire de BDD admin** | Gestion centralisée de toutes les bases de données |
| **Téléchargement de dossiers** | Compression et téléchargement ZIP à la volée |
| **Terminal web** | Terminal bash intégré avec support PTY (nano, vim, htop...) |
| **Gestionnaire Docker** | Supervision et gestion des conteneurs Docker |
| **Docker Compose** | Déploiement de stacks depuis le panel |
| **Gestionnaire de processus** | Vue et contrôle des processus système |
| **Services Systemd** | Liste et gestion des services |
| **Gestionnaire Cron** | Planification de tâches depuis le panel |
| **Pare-feu UFW** | Gestion des règles firewall intégrée |
| **Fail2ban** | Supervision des bans IP |
| **Monitoring** | Surveillance de services avec alertes |
| **Gestionnaire de fichiers admin** | Explorateur de fichiers système côté admin |

## Jeux et serveurs supportés

Gecko supporte une large variété de jeux via des conteneurs Docker isolés.

| Catégorie | Eggs |
|---|---|
| **Minecraft** | Paper, Sponge, Bungeecord, Waterfall |
| **SteamCMD** | 7 Days to Die, ARK, Arma 3, Counter Strike, DayZ, Left 4 Dead, Palworld, Project Zomboid, Satisfactory, Sons of the Forest |
| **Standalone** | Among Us, Factorio, FTL, GTA, Kerbal Space, Rimworld, Terraria |
| **Discord Bots** | Redbot, JMusicBot, Dynamica |
| **Voice Servers** | Mumble, Teamspeak, Lavalink |
| **Programmation** | Node.js, Python, Java, C# |
| **Bases de données** | Redis, MariaDB, PostgreSQL, MongoDB |

## Installation

```bash
bash <(curl -sSL https://raw.githubusercontent.com/papy-gecko/gecko-panel/main/install.sh)
```

## Prérequis

- Debian 11/12 ou Ubuntu 22.04+
- 2 Go RAM minimum
- 20 Go de stockage
- Un domaine pointant vers votre serveur

## Licence

Gecko Panel est distribué sous licence [AGPL-3.0](LICENSE).
Basé sur [Pelican Panel](https://github.com/pelican-dev/panel) © Pelican 2024-2026.
