#!/bin/bash
# PMS 4.0 Local Development Setup Script
# Run with: sudo bash setup_local.sh

set -e

echo "========================================"
echo " PMS 4.0 - Local Dev Setup"
echo "========================================"

PROJECT_DIR="/home/weirdsoul/Desktop/pms_4.0"
VHOST_FILE="/etc/apache2/sites-available/pms4.conf"
APACHE_USER="www-data"

# 1. Enable Apache mod_rewrite
echo "[1/5] Enabling Apache mod_rewrite..."
a2enmod rewrite
a2enmod php8.3

# 2. Create Virtual Host
echo "[2/5] Creating Apache virtual host..."
cat > "$VHOST_FILE" <<EOF
<VirtualHost *:80>
    ServerName pms4.local
    ServerAlias localhost
    DocumentRoot $PROJECT_DIR

    <Directory $PROJECT_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/pms4_error.log
    CustomLog \${APACHE_LOG_DIR}/pms4_access.log combined
</VirtualHost>
EOF

# 3. Enable the site
echo "[3/5] Enabling site..."
a2ensite pms4.conf
# Disable default if it conflicts
# a2dissite 000-default.conf

# 4. Set permissions on project directory
echo "[4/5] Setting directory permissions..."
chown -R $APACHE_USER:$APACHE_USER "$PROJECT_DIR/application/cache"
chown -R $APACHE_USER:$APACHE_USER "$PROJECT_DIR/application/logs"
chown -R $APACHE_USER:$APACHE_USER "$PROJECT_DIR/uploads"
chmod -R 755 "$PROJECT_DIR/application/cache"
chmod -R 755 "$PROJECT_DIR/application/logs"
chmod -R 755 "$PROJECT_DIR/uploads"

# Ensure cache/temp exists for sessions
mkdir -p "$PROJECT_DIR/application/cache/temp"
chown -R $APACHE_USER:$APACHE_USER "$PROJECT_DIR/application/cache/temp"
chmod -R 777 "$PROJECT_DIR/application/cache/temp"

# 5. Restart Apache
echo "[5/5] Restarting Apache..."
systemctl restart apache2

# 6. Add pms4.local to /etc/hosts (optional)
if ! grep -q "pms4.local" /etc/hosts; then
    echo "127.0.0.1  pms4.local" >> /etc/hosts
    echo "Added pms4.local to /etc/hosts"
fi

echo ""
echo "========================================"
echo " Setup Complete!"
echo "========================================"
echo " Access the app at: http://localhost"
echo " Or:               http://pms4.local"
echo ""
echo " NOTE: You still need to set up the database."
echo " See the .env.example file for credentials."
echo "========================================"
