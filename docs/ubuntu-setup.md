# IT Cockpit – Ubuntu Server Setup

Getestet mit **Ubuntu 22.04 LTS**, Nginx, PHP 8.2, MySQL 8.

---

## 1. System-Pakete installieren

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.2 Repository hinzufügen
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# PHP 8.2 + alle benötigten Extensions
sudo apt install -y \
    php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-zip php8.2-gd php8.2-curl \
    php8.2-bcmath php8.2-opcache

# Nginx, MySQL, Git
sudo apt install -y nginx mysql-server git unzip iproute2

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 2. MySQL einrichten

```bash
sudo mysql_secure_installation
```

Datenbank und User anlegen:

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE itcockpit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'itcockpit'@'localhost' IDENTIFIED BY 'SICHERES_PASSWORT';
GRANT ALL PRIVILEGES ON itcockpit.* TO 'itcockpit'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3. Projekt clonen

```bash
sudo mkdir -p /var/www/itcockpit
sudo chown $USER:$USER /var/www/itcockpit

git clone https://github.com/Beseco/itcockpit5.git /var/www/itcockpit
cd /var/www/itcockpit
```

---

## 4. Abhängigkeiten installieren

```bash
# PHP-Pakete (ohne Dev-Dependencies)
composer install --optimize-autoloader --no-dev

# Frontend-Assets bauen
npm ci
npm run build
```

---

## 5. Umgebung konfigurieren

```bash
cp .env.example .env
php artisan key:generate
```

`.env` anpassen:

```bash
nano .env
```

Folgende Werte setzen:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://DEINE-IP

DB_DATABASE=itcockpit
DB_USERNAME=itcockpit
DB_PASSWORD=SICHERES_PASSWORT

MAIL_MAILER=smtp
MAIL_HOST=dein-mailserver
MAIL_PORT=587
MAIL_USERNAME=dein-user
MAIL_PASSWORD=dein-passwort
MAIL_FROM_ADDRESS="itcockpit@deinefirma.de"
```

---

## 6. Datenbank migrieren & Admin-User anlegen

> **Wichtig:** Alle `php artisan`-Befehle immer als `www-data` ausführen, damit Logs und Cache-Dateien die richtigen Berechtigungen erhalten.

```bash
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force

# Admin-User erstellen
sudo -u www-data php artisan user:create-admin
```

---

## 7. Storage & Berechtigungen

```bash
sudo -u www-data php artisan storage:link

sudo chown -R www-data:www-data /var/www/itcockpit
sudo chmod -R 755 /var/www/itcockpit/storage
sudo chmod -R 755 /var/www/itcockpit/bootstrap/cache
```

---

## 8. Nginx konfigurieren

```bash
sudo nano /etc/nginx/sites-available/itcockpit
```

Inhalt:

```nginx
server {
    listen 80;
    server_name _;

    root /var/www/itcockpit/public;
    index index.php;

    # SSE-Streams nicht puffern (für Export-Fortschritt)
    location /network/export/stream {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffering off;
        fastcgi_read_timeout 600;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\. {
        deny all;
    }
}
```

Site aktivieren:

```bash
sudo ln -s /etc/nginx/sites-available/itcockpit /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

sudo nginx -t
sudo systemctl reload nginx
```

---

## 9. PHP-FPM konfigurieren

Maximale Ausführungszeit erhöhen (wichtig für Excel-Export):

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Folgende Werte setzen:

```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
```

```bash
sudo systemctl restart php8.2-fpm
```

---

## 10. Cron-Job für Laravel Scheduler

```bash
sudo crontab -u www-data -e
```

Zeile einfügen:

```
* * * * * cd /var/www/itcockpit && php artisan schedule:run >> /dev/null 2>&1
```

---

## 11. Netzwerk-Scan (Ping-Berechtigung)

Damit www-data pingen darf:

```bash
sudo setcap cap_net_raw=ep /usr/bin/ping
```

Prüfen:

```bash
sudo -u www-data ping -c 1 127.0.0.1
```

---

## 12. Produktions-Caching aktivieren

Alle Artisan-Befehle **müssen als www-data** ausgeführt werden, sonst entstehen Permission-Fehler auf storage/logs:

```bash
cd /var/www/itcockpit
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

---

## 13. Installation prüfen

```bash
# Nginx-Konfiguration valide?
sudo nginx -t

# Laravel erreichbar?
curl -s -o /dev/null -w "%{http_code}" http://localhost/
# Erwartetes Ergebnis: 200 oder 302

# Scheduler läuft?
sudo -u www-data php artisan schedule:run

# Netzwerk-Scan funktioniert?
sudo -u www-data php artisan network:scan --force
```

Öffne im Browser: `http://DEINE-SERVER-IP`

---

## 14. Updates einspielen

Workflow für spätere Deployments:

```bash
cd /var/www/itcockpit

# Wartungsmodus aktivieren
sudo -u www-data php artisan down

# Code aktualisieren
git pull

# Abhängigkeiten aktualisieren (falls nötig)
composer install --optimize-autoloader --no-dev

# Frontend neu bauen (falls CSS/JS geändert)
npm ci && npm run build

# Migrationen ausführen
sudo -u www-data php artisan migrate --force

# Cache leeren und neu aufbauen
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Wartungsmodus beenden
sudo -u www-data php artisan up
```

---

---

## 15. Datenmigration vom alten Server

### Schritt 1: Dump auf dem alten Server erstellen

```bash
# SSH auf alten Server
cd /home/users/ticketsystem/www

# Datenbank-Zugangsdaten aus .env auslesen
grep DB_ .env

# Datenbank-Dump erstellen
mysqldump -u DB_USERNAME -p DB_DATABASE > /tmp/itcockpit_dump.sql

# Uploads packen
tar -czf /tmp/itcockpit_storage.tar.gz -C /home/users/ticketsystem/www storage/app/public
```

### Schritt 2: Dateien auf den neuen Server übertragen

```bash
# Von lokalem PC aus (beide Dateien herunterladen, dann hochladen)
scp USER@ALTER-SERVER:/tmp/itcockpit_dump.sql ./
scp USER@ALTER-SERVER:/tmp/itcockpit_storage.tar.gz ./

scp itcockpit_dump.sql USER@NEUER-SERVER:/tmp/
scp itcockpit_storage.tar.gz USER@NEUER-SERVER:/tmp/
```

### Schritt 3: Datenbank einspielen (auf dem neuen Server)

```bash
# Leere Datenbank sicherstellen
sudo mysql -u root -p -e "DROP DATABASE itcockpit; CREATE DATABASE itcockpit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Dump einspielen
sudo mysql -u root -p itcockpit < /tmp/itcockpit_dump.sql
```

### Schritt 4: Uploads wiederherstellen

```bash
cd /var/www/itcockpit
sudo tar -xzf /tmp/itcockpit_storage.tar.gz -C .
sudo chown -R www-data:www-data storage/
```

### Schritt 5: APP_KEY vom alten Server übernehmen

Den `APP_KEY` aus der `.env` des alten Servers in die neue `.env` kopieren – sonst können bestehende Sessions und verschlüsselte Daten nicht gelesen werden:

```bash
# Auf altem Server anzeigen
grep APP_KEY /home/users/ticketsystem/www/.env

# Auf neuem Server setzen
nano /var/www/itcockpit/.env
# → APP_KEY=... eintragen

sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
```

---

## Troubleshooting

| Problem | Lösung |
|---|---|
| 500 Error | `tail -f /var/www/itcockpit/storage/logs/laravel.log` |
| Keine Schreibrechte | `sudo chown -R www-data:www-data storage bootstrap/cache` |
| Ping schlägt fehl | `sudo setcap cap_net_raw=ep /usr/bin/ping` |
| Export bricht ab | PHP `memory_limit` und `max_execution_time` in php.ini prüfen |
| Nginx zeigt keine Seite | `sudo nginx -t` und `sudo systemctl status nginx` |
| Cron läuft nicht | `sudo crontab -u www-data -l` prüfen |
