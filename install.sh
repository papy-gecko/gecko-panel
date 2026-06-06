#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

info()    { echo -e "${CYAN}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()   { echo -e "${RED}[ERREUR]${NC} $1"; exit 1; }
step()    { echo -e "\n${BOLD}${CYAN}══ $1 ══${NC}"; }

echo -e "${GREEN}"
echo "   ██████╗ ███████╗ ██████╗██╗  ██╗ ██████╗"
echo "  ██╔════╝ ██╔════╝██╔════╝██║ ██╔╝██╔═══██╗"
echo "  ██║  ███╗█████╗  ██║     █████╔╝ ██║   ██║"
echo "  ██║   ██║██╔══╝  ██║     ██╔═██╗ ██║   ██║"
echo "  ╚██████╔╝███████╗╚██████╗██║  ██╗╚██████╔╝"
echo "   ╚═════╝ ╚══════╝ ╚═════╝╚═╝  ╚═╝ ╚═════╝"
echo -e "${NC}"
echo -e "${CYAN}  Panel v1.0.0 — Installeur automatique${NC}"
echo -e "${CYAN}  github.com/papy-gecko/gecko-panel${NC}"
echo ""

# ── Root ──
[ "$EUID" -ne 0 ] && error "Lance ce script en root : sudo bash install.sh"

# ── OS ──
. /etc/os-release
[[ "$ID" != "debian" && "$ID" != "ubuntu" ]] && error "Debian/Ubuntu uniquement."

# ── Infos ──
step "Configuration"
read -p "Domaine du panel (ex: panel.monserveur.fr) : " DOMAIN
read -p "Email admin : " ADMIN_EMAIL
read -p "Nom d'utilisateur admin : " ADMIN_USER
read -s -p "Mot de passe admin : " ADMIN_PASS; echo

MYSQL_USER="gecko"
MYSQL_DB="gecko_panel"
MYSQL_PASS=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 24)
MYSQL_ROOT_PASS=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 24)

echo ""
info "Domaine    : $DOMAIN"
info "Email      : $ADMIN_EMAIL"
info "User admin : $ADMIN_USER"
read -p "Continuer ? [o/N] : " CONFIRM
[[ "$CONFIRM" != "o" && "$CONFIRM" != "O" ]] && exit 0

# ── Système ──
step "Mise à jour système"
apt-get update -q && apt-get upgrade -y -q
apt-get install -y -q curl wget git unzip tar nginx certbot python3-certbot-nginx \
    software-properties-common apt-transport-https ca-certificates gnupg2
success "Système mis à jour"

# ── PHP 8.2 ──
step "PHP 8.2"
curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg
echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list
apt-get update -q
apt-get install -y -q php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-sqlite3 \
    php8.2-xml php8.2-curl php8.2-mbstring php8.2-bcmath php8.2-zip \
    php8.2-gd php8.2-intl php8.2-tokenizer
success "PHP 8.2 installé"

# ── MySQL ──
step "MySQL"
apt-get install -y -q mysql-server
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE DATABASE IF NOT EXISTS \`${MYSQL_DB}\`;"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'localhost' IDENTIFIED BY '${MYSQL_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON \`${MYSQL_DB}\`.* TO '${MYSQL_USER}'@'localhost'; FLUSH PRIVILEGES;"
success "MySQL configuré"

# ── Node.js ──
step "Node.js 20"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - 2>/dev/null
apt-get install -y -q nodejs
success "Node.js $(node -v) installé"

# ── Composer ──
step "Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer 2>/dev/null
success "Composer installé"

# ── Panel ──
step "Installation Gecko Panel"
mkdir -p /var/www/gecko
git clone https://github.com/papy-gecko/gecko-panel.git /var/www/gecko
cd /var/www/gecko

info "Dépendances PHP..."
composer install --no-dev --optimize-autoloader --no-interaction -q

info "Dépendances Node..."
npm install --silent

info "Build assets..."
npm run build --silent

info "Configuration .env..."
cp .env.example .env 2>/dev/null || cp .env.example.dist .env 2>/dev/null || touch .env
php artisan key:generate --force

APP_KEY=$(grep APP_KEY .env | cut -d= -f2-)

cat > .env << ENVEOF
APP_NAME="Gecko"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://${DOMAIN}
APP_FAVICON=/favicon.ico

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${MYSQL_DB}
DB_USERNAME=${MYSQL_USER}
DB_PASSWORD=${MYSQL_PASS}

CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database

MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_DATABASE=mysql
MYSQL_USERNAME=${MYSQL_USER}
MYSQL_PASSWORD=${MYSQL_PASS}
ENVEOF

info "Migrations..."
php artisan migrate --force
php artisan db:seed --force 2>/dev/null || true

info "Création compte admin..."
php artisan p:user:make \
    --email="$ADMIN_EMAIL" \
    --username="$ADMIN_USER" \
    --name-first="Admin" \
    --name-last="Gecko" \
    --password="$ADMIN_PASS" \
    --admin=1 2>/dev/null || true

chown -R www-data:www-data /var/www/gecko
chmod -R 755 /var/www/gecko/storage /var/www/gecko/bootstrap/cache
php artisan optimize
success "Panel installé"

# ── SSL d'abord (sans HTTPS dans nginx) ──
step "Certificat SSL"
cat > /etc/nginx/sites-available/gecko << NGINXEOF
server {
    listen 80;
    server_name ${DOMAIN};
    root /var/www/gecko/public;
    index index.php;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \\.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
NGINXEOF

ln -sf /etc/nginx/sites-available/gecko /etc/nginx/sites-enabled/gecko
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

certbot certonly --nginx -d "$DOMAIN" --email "$ADMIN_EMAIL" --agree-tos --non-interactive
success "SSL obtenu"

# ── Nginx final avec HTTPS ──
step "Nginx (HTTPS)"
cat > /etc/nginx/sites-available/gecko << NGINXEOF
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}
server {
    listen 443 ssl;
    server_name ${DOMAIN};
    root /var/www/gecko/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;

    location /api/servers {
        proxy_pass https://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_buffering off;
        proxy_read_timeout 3600s;
    }

    location /download/file {
        proxy_pass https://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_buffering off;
        client_max_body_size 0;
    }

    location /upload/file {
        proxy_pass https://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_request_buffering off;
        client_max_body_size 512m;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 600s;
    }
}
NGINXEOF

nginx -t && systemctl reload nginx
success "Nginx configuré"

# ── Queue worker ──
step "Service queue"
cat > /etc/systemd/system/gecko-queue.service << SVCEOF
[Unit]
Description=Gecko Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/gecko
ExecStart=/usr/bin/php /var/www/gecko/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
SVCEOF

systemctl enable --now gecko-queue
success "Queue worker démarré"

# ── Cron ──
(crontab -l 2>/dev/null; echo "* * * * * php /var/www/gecko/artisan schedule:run >> /dev/null 2>&1") | crontab -
success "Cron configuré"

# ── Docker ──
step "Docker"
curl -fsSL https://get.docker.com | sh 2>/dev/null
systemctl enable --now docker
success "Docker installé"

# ── Wings ──
step "Wings"
mkdir -p /etc/pelican /var/lib/pelican/volumes
curl -sSL -o /usr/local/bin/wings \
    https://github.com/pelican-dev/wings/releases/latest/download/wings_linux_amd64
chmod +x /usr/local/bin/wings

cat > /etc/systemd/system/wings.service << WINGSEOF
[Unit]
Description=Gecko Wings Daemon
After=docker.service
Requires=docker.service

[Service]
User=root
WorkingDirectory=/etc/pelican
ExecStart=/usr/local/bin/wings
Restart=on-failure
StartLimitInterval=180
StartLimitBurst=30
RestartSec=5

[Install]
WantedBy=multi-user.target
WINGSEOF

systemctl enable wings
success "Wings installé"

# ── UFW ──
step "Firewall"
apt-get install -y -q ufw
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 8080/tcp
ufw --force enable
success "UFW configuré"

# ── Résumé ──
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║    Gecko Panel installé avec succès !        ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}URL du panel    :${NC} https://${DOMAIN}/admin"
echo -e "  ${BOLD}Email admin     :${NC} ${ADMIN_EMAIL}"
echo -e "  ${BOLD}User admin      :${NC} ${ADMIN_USER}"
echo -e "  ${BOLD}DB Name         :${NC} ${MYSQL_DB}"
echo -e "  ${BOLD}DB User         :${NC} ${MYSQL_USER}"
echo -e "  ${BOLD}DB Password     :${NC} ${MYSQL_PASS}"
echo -e "  ${BOLD}MySQL root pass :${NC} ${MYSQL_ROOT_PASS}"
echo ""
echo -e "  ${YELLOW}Configure un nœud Wings dans le panel puis :${NC}"
echo -e "  ${YELLOW}systemctl start wings${NC}"
echo ""
