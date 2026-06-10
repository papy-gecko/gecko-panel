<img width="15%" src="https://raw.githubusercontent.com/papy-gecko/gecko-panel/main/public/favicon.ico" alt="Gecko Panel logo">

# Gecko Panel

![Latest Release](https://img.shields.io/github/v/release/papy-gecko/gecko-panel?style=flat&label=Dernière%20version&labelColor=2d6a4f&color=ffffff)
![License](https://img.shields.io/github/license/papy-gecko/gecko-panel?style=flat&labelColor=2d6a4f&color=ffffff)

Gecko Panel est un panneau de gestion de serveurs de jeu open-source, simple à installer et à utiliser. Basé sur [Pelican Panel](https://github.com/pelican-dev/panel), il ajoute un installateur graphique accessible depuis le navigateur — pas besoin de connaissances techniques pour démarrer.

---

## 📋 Prérequis

Avant de commencer, assure-toi d'avoir :

- Un **VPS** sous **Debian 12** ou **Ubuntu 22.04+** (minimum 1 vCPU, 1 Go RAM)
- Un **nom de domaine** dont les DNS pointent déjà vers l'IP de ton serveur (ex: `panel.mondomaine.fr`)
- Un accès **SSH** à ton serveur (ou la console de ton hébergeur)

> **DNS** : dans les réglages de ton domaine, crée un enregistrement `A` qui pointe vers l'IP de ton VPS. Attends quelques minutes que ça se propage avant de lancer l'installation.

---

## 🚀 Installation

Connecte-toi à ton serveur en SSH puis lance cette commande :

```bash
curl -fsSL https://raw.githubusercontent.com/papy-gecko/gecko-panel/main/install.sh | sudo bash
```

Le script va te demander ton domaine, puis installer automatiquement :
- PHP 8.2, MySQL, Nginx, Node.js
- Le panel Gecko avec ses dépendances
- Un certificat SSL Let's Encrypt (HTTPS)
- Docker + Wings (daemon de gestion des conteneurs)
- GoTTY (terminal web intégré au panel)
- UFW (pare-feu)

L'installation prend environ **5 à 10 minutes**.

---

## ⚙️ Configuration via le navigateur

Une fois l'installation terminée, ouvre cette adresse dans ton navigateur :

```
https://ton-domaine.fr/installer
```

L'assistant te guidera étape par étape pour :

1. **Vérifier les prérequis** serveur
2. **Configurer l'environnement** (nom du panel, URL)
3. **Créer ton compte administrateur**
4. **Configurer la base de données**
5. **Sécuriser l'accès SSH** *(optionnel mais recommandé)*
   - Génère une clé SSH dédiée
   - Change le port SSH (adieu les bots sur le port 22)
   - Désactive l'authentification par mot de passe
   - **Télécharge la clé privée et le raccourci `.bat`** avant de continuer — ils ne seront plus jamais affichés

---

## 🔒 Se connecter en SSH après la sécurisation

Si tu as activé la sécurisation SSH dans l'assistant, tu ne peux plus te connecter avec le mot de passe. Utilise la clé téléchargée :

**Windows** : double-clique sur le fichier `.bat` téléchargé (à placer à côté de la clé `.ed25519`)

**Linux / macOS** :
```bash
ssh -i gecko-mondomaine.fr_ed25519 -p TON_PORT debian@IP_DU_SERVEUR
```

---

## 📧 Configuration des emails (mot de passe oublié)

Pour que la fonctionnalité "mot de passe oublié" fonctionne, configure un serveur SMTP dans `/var/www/gecko/.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=465
MAIL_USERNAME=ton-identifiant@smtp-brevo.com
MAIL_PASSWORD=ta-clé-smtp
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@tondomaine.fr
MAIL_FROM_NAME="Gecko Panel"
```

Nous recommandons [Brevo](https://www.brevo.com) (gratuit jusqu'à 300 emails/jour).

Après modification du `.env` :
```bash
cd /var/www/gecko && sudo -u www-data php artisan config:clear
```

---

## 🎮 Serveurs de jeu supportés

Gecko supporte tous les jeux compatibles avec les eggs Pelican :

| Catégorie | Exemples |
|-----------|----------|
| Minecraft | Paper, Spigot, Bungeecord, Waterfall |
| SteamCMD | ARK, Counter-Strike, DayZ, Palworld, Project Zomboid |
| Jeux standalone | Factorio, Terraria, Rimworld |
| Bots Discord | Redbot, JMusicBot |
| Serveurs voix | Mumble, TeamSpeak |
| Bases de données | Redis, MariaDB, PostgreSQL |

---

## 💬 Liens

- [Signaler un bug](https://github.com/papy-gecko/gecko-panel/issues)
- [Discussions](https://github.com/papy-gecko/gecko-panel/discussions)
- [Soutenir le projet](https://github.com/sponsors/papy-gecko)

---

*Fork de [Pelican Panel](https://github.com/pelican-dev/panel) — © 2024-2026 Gecko Panel*
