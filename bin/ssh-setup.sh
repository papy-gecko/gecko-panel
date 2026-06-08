#!/bin/bash
#
# Sécurise l'accès SSH du serveur : génère une paire de clés dédiée, bascule
# sur un port personnalisé et désactive l'authentification par mot de passe.
#
# Conçu pour être appelé via sudo par www-data (voir /etc/sudoers.d/gecko-ssh-setup),
# JAMAIS exécuté à la main. Toute étape qui échoue déclenche une restauration
# complète de la configuration sshd/firewall d'origine — le port actuel ne doit
# jamais être fermé tant que le nouveau n'a pas été testé avec succès.
#
# Usage : ssh-setup.sh <ssh_user> <new_port> <output_dir>

set -euo pipefail

SSH_USER="${1:?ssh_user requis}"
NEW_PORT="${2:?new_port requis}"
OUT_DIR="${3:?output_dir requis}"

SSHD_CONFIG="/etc/ssh/sshd_config"
BACKUP="/etc/ssh/sshd_config.gecko-backup"
SSH_SERVICE="ssh"
KEY_PATH="${OUT_DIR}/gecko_id_ed25519"
USER_HOME=$(getent passwd "$SSH_USER" | cut -d: -f6)
AUTHORIZED_KEYS="${USER_HOME}/.ssh/authorized_keys"

log()  { echo "[ssh-setup] $1" >&2; }
fail() { echo "[ssh-setup][ERROR] $1" >&2; exit 1; }

[ -d "$USER_HOME" ] || fail "utilisateur '$SSH_USER' introuvable"
[[ "$NEW_PORT" =~ ^[0-9]+$ ]] && [ "$NEW_PORT" -ge 1024 ] && [ "$NEW_PORT" -le 65535 ] \
    || fail "port invalide : $NEW_PORT (doit être entre 1024 et 65535)"

CURRENT_PORT=$(grep -iE '^\s*Port\s+[0-9]+' "$SSHD_CONFIG" | awk '{print $2}' | head -n1 || true)
CURRENT_PORT="${CURRENT_PORT:-22}"
[ "$NEW_PORT" -eq "$CURRENT_PORT" ] && fail "le nouveau port est identique au port actuel ($CURRENT_PORT)"

mkdir -p "$OUT_DIR"
rm -f "$KEY_PATH" "${KEY_PATH}.pub"

ROLLED_BACK=0
rollback() {
    [ "$ROLLED_BACK" -eq 1 ] && return
    ROLLED_BACK=1
    log "annulation : restauration de la configuration d'origine..."
    if [ -f "$BACKUP" ]; then
        cp "$BACKUP" "$SSHD_CONFIG"
    fi
    ufw delete allow "$NEW_PORT"/tcp >/dev/null 2>&1 || true
    ufw allow "$CURRENT_PORT"/tcp >/dev/null 2>&1 || true
    systemctl reload "$SSH_SERVICE" >/dev/null 2>&1 || true
    rm -f "$KEY_PATH" "${KEY_PATH}.pub"
    log "configuration restaurée, le port $CURRENT_PORT reste actif"
}
trap 'echo "[ssh-setup][ERROR] interruption inattendue — restauration en cours" >&2; rollback; exit 1' ERR

# ── 1. Sauvegarde de la config actuelle ──
cp "$SSHD_CONFIG" "$BACKUP"
log "sauvegarde de sshd_config créée"

# ── 2. Génération de la paire de clés dédiée ──
ssh-keygen -t ed25519 -f "$KEY_PATH" -N "" -C "gecko-panel-$(date +%Y%m%d)" -q
# Générée par root (le script tourne via sudo), la clé doit néanmoins être
# relue par www-data — c'est PanelInstaller::hardenSsh() (PHP) qui l'affiche
# dans le navigateur puis l'efface immédiatement après. On la passe donc à
# www-data plutôt qu'à $SSH_USER (qui ne s'en sert jamais depuis le disque).
chown www-data:www-data "$KEY_PATH" "${KEY_PATH}.pub"
chmod 600 "$KEY_PATH"
chmod 644 "${KEY_PATH}.pub"
log "paire de clés générée : $KEY_PATH"

# ── 3. Ajout de la clé publique aux clés autorisées ──
mkdir -p "${USER_HOME}/.ssh"
touch "$AUTHORIZED_KEYS"
cat "${KEY_PATH}.pub" >> "$AUTHORIZED_KEYS"
chown -R "${SSH_USER}:${SSH_USER}" "${USER_HOME}/.ssh"
chmod 700 "${USER_HOME}/.ssh"
chmod 600 "$AUTHORIZED_KEYS"
log "clé publique ajoutée à $AUTHORIZED_KEYS"

# ── 4. Ouverture du nouveau port et activation en plus de l'ancien ──
ufw allow "$NEW_PORT"/tcp >/dev/null
sed -i -E "/^\s*Port\s+[0-9]+/d" "$SSHD_CONFIG"
{
    echo "Port ${CURRENT_PORT}"
    echo "Port ${NEW_PORT}"
} >> "$SSHD_CONFIG"
systemctl reload "$SSH_SERVICE"
log "port $NEW_PORT activé en plus du port $CURRENT_PORT, sshd rechargé"

# ── 5. Test actif du nouveau port (poignée de main SSH locale) ──
sleep 1
if ! timeout 5 bash -c "echo > /dev/tcp/127.0.0.1/${NEW_PORT}" 2>/dev/null; then
    echo "[ssh-setup][ERROR] le nouveau port $NEW_PORT ne répond pas localement — restauration" >&2
    rollback
    exit 1
fi
log "le port $NEW_PORT répond — bascule définitive"

# ── 6. Bascule définitive : on ferme l'ancien port et le mot de passe ──
sed -i -E "/^\s*Port\s+${CURRENT_PORT}\s*$/d" "$SSHD_CONFIG"
sed -i -E "/^\s*PasswordAuthentication\s+/d" "$SSHD_CONFIG"
echo "PasswordAuthentication no" >> "$SSHD_CONFIG"
systemctl reload "$SSH_SERVICE"
ufw delete allow "$CURRENT_PORT"/tcp >/dev/null 2>&1 || true
log "port $CURRENT_PORT fermé, authentification par mot de passe désactivée"

# ── 7. Nettoyage ──
rm -f "$BACKUP"
# Pas de chown vers $SSH_USER ici : la clé n'est jamais utilisée depuis le
# disque par cet utilisateur — l'installateur web la lit, l'affiche dans le
# navigateur puis l'efface (voir PanelInstaller::hardenSsh()). Elle doit donc
# rester lisible par www-data (le process PHP qui exécute ce script via sudo) ;
# un chown vers debian:debian la rend illisible et casse l'affichage final
# ("file_get_contents(): Permission denied").

log "terminé : SSH sécurisé sur le port $NEW_PORT avec authentification par clé uniquement"
echo "${KEY_PATH}"
