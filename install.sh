#!/bin/bash

# This will install everything required to run a basic FreeScout installation.
# This should be run on a clean Ubuntu server. [cite: 2]

install_path='/var/www/html'

# [TOUCH UP] Replaced deprecated net-tools/ifconfig with native hostname command for cleaner IP detection
server_ip=$(hostname -I | awk '{print $1}')

printf "
########################################
## FreeScout UBUNTU Installation ##
########################################
"

domain_name='freescout-dev.local'

# Generate random password
mysql_pass=`date +%s | sha256sum | base64 | head -c 9 ; echo` [cite: 5]

is_debian=`cat /etc/issue | grep -E ^Debian | wc -l`

#
# Dependencies
#
echo "Installing dependencies..."
sudo apt update
export DEBIAN_FRONTEND=noninteractive

sudo apt remove apache2 -y
# [TOUCH UP] Added supervisor to the install list
sudo apt -qq install git nginx supervisor
sudo apt -qq install mysql-server libmysqlclient-dev
# [TOUCH UP] Added php-bcmath and php-gmp (often helpful for laravel apps) to the standard list
sudo apt -qq install php-fpm php php-mysqli php-mbstring php-xml php-imap php-zip php-gd php-curl php-intl php-bcmath php-gmp
sudo apt -qq -q install php-json
sudo apt -qq -q install avahi-daemon
sudo hostnamectl set-hostname freescout-dev
sudo systemctl restart avahi-daemon

# Detect PHP version dynamically
php_version=`php -v | head -n 1 | cut -d " " -f 2 | cut -f1-2 -d"."` [cite: 6, 7]

# [TOUCH UP] Increase PHP Memory Limit to 512M (Freescout needs this for attachments)
echo "Tuning PHP configuration..."
sudo sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/$php_version/fpm/php.ini
sudo sed -i "s/upload_max_filesize = .*/upload_max_filesize = 20M/" /etc/php/$php_version/fpm/php.ini
sudo sed -i "s/post_max_size = .*/post_max_size = 20M/" /etc/php/$php_version/fpm/php.ini

#
# MySQL
#
echo "Configuring MySQL..."
echo 'DROP DATABASE IF EXISTS `freescout`;' | mysql -u root [cite: 8]
echo 'CREATE DATABASE `freescout` CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;' | mysql -u root
echo 'DROP USER `freescout`@`localhost`;' | mysql -u root [cite: 9]
echo 'REVOKE ALL PRIVILEGES, GRANT OPTION FROM `freescout`@`localhost`;' | mysql -u root
echo 'GRANT ALL PRIVILEGES ON `freescout`.* TO `freescout`@`localhost` IDENTIFIED BY "'"$mysql_pass"'";' | mysql -u root [cite: 11]
# new syntax for newer MySQL versions
echo 'CREATE USER `freescout`@`localhost` IDENTIFIED BY "'"$mysql_pass"'";' | mysql -u root [cite: 12]
echo 'GRANT ALL ON `freescout`.* TO `freescout`@`localhost`;' | mysql -u root [cite: 13]
echo "You may see a MySQL privileges error above. Don't worry - the script executes two different commands for different DB versions and one of them always fails - just continue the installation." [cite: 14]

#
# Application Setup
#

if [ -f "$install_path" ]; then
    echo "$install_path is not a directory. Terminating installation"
    exit;
fi

# Safe directory cleanup
if [ -d "$install_path" ]; then
    install_path_check=`sudo ls -1qA $install_path`
    if [ ! -z "$install_path_check" ]; then
        sudo rm -rf $install_path [cite: 16]
    fi
fi

sudo mkdir -p $install_path
sudo chown www-data:www-data $install_path
# Clones the repo
sudo git clone https://github.com/freescout-helpdesk/freescout $install_path [cite: 16]
cd $install_path/overrides/filp
sudo rm -r whoops
sudo git clone https://github.com/filp/whoops.git
sudo chown -R www-data:www-data $install_path
sudo find $install_path -type f -exec chmod 664 {} \; [cite: 17]
sudo find $install_path -type d -exec chmod 775 {} \;

if [ ! -f "$install_path/artisan" ]; then
    echo "Error occured installing FreeScout into $install_path. Terminating installation"
    exit;
fi
echo "Application installed"

#
# Nginx
#
echo "Configuring nginx..."
# Writes the Nginx config
sudo echo 'server {
    listen 80;
    listen [::]:80;

    server_name '"$domain_name"';

    root '"$install_path"'/public;
    index index.php index.html index.htm; [cite: 20]

    error_log '"$install_path"'/storage/logs/web-server.log;

    client_max_body_size 20M; [cite: 21]
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php'"$php_version"'-fpm.sock; [cite: 23]
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* ^/storage/attachment/ {
        expires 1M;
        access_log off;
        try_files $uri $uri/ /index.php?$query_string; [cite: 28]
    }
    
    location ~* ^/(?:css|js)/.*\.(?:css|js)$ {
        expires 2d;
        access_log off;
        add_header Cache-Control "public, must-revalidate";
    }
    
    location ~* ^/storage/.*\.((?!(jpg|jpeg|jfif|pjpeg|pjp|apng|bmp|gif|ico|cur|png|tif|tiff|webp|pdf|txt|diff|patch|json|mp3|wav|ogg|wma)).)*$ {
        add_header Content-disposition "attachment; filename=$2";
        default_type application/octet-stream; [cite: 30]
    }   
    
    location ~* ^/(?:css|fonts|img|installer|js|modules|[^\\\]+\..*)$ {
        expires 1M;
        access_log off;
        add_header Cache-Control "public";
    }
    
    location ~ /\. {
        deny  all;
    }
}' > /etc/nginx/sites-available/$domain_name

if [ -f "/etc/nginx/sites-enabled/default" ]; then
    sudo rm -f /etc/nginx/sites-enabled/default
fi

if [ -f "/etc/nginx/sites-enabled/$domain_name" ]; then
    sudo rm -f "/etc/nginx/sites-enabled/$domain_name" 
fi
sudo ln -s "/etc/nginx/sites-available/$domain_name" "/etc/nginx/sites-enabled/$domain_name"

nginx_test=`sudo nginx -t 2>&1; echo $?`
if [[ ! $nginx_test == *"test is successful"* ]]; then
    echo "Nginx configuration error. Terminating installation"
    sudo nginx -t
    exit;
fi

sudo service nginx reload

#
# Check requirements
# 
echo "Checking requirements..."
php $install_path/artisan freescout:check-requirements [cite: 42]

#
# Cron (The Heartbeat)
# 
echo "Configuring cron task for www-data..."
sudo crontab -u www-data -l > /tmp/wwwdatacron;
schedule_cron=`more /tmp/wwwdatacron | grep schedule`
if [ -z "$schedule_cron" ]; then
    sudo echo "# Main cron job
* * * * * php $install_path/artisan schedule:run >> /dev/null 2>&1" >> /tmp/wwwdatacron [cite: 43]
    sudo crontab -u www-data /tmp/wwwdatacron
fi
if [ -f "/tmp/wwwdatacron" ]; then
    sudo rm -f /tmp/wwwdatacron
fi

#
# [NEW] Supervisor (The Muscle)
# This ensures the app 'always runs' background tasks like email fetching/sending
#
echo "Configuring Supervisor..."
# Create the log file first
sudo touch $install_path/storage/logs/worker.log
sudo chown www-data:www-data $install_path/storage/logs/worker.log

sudo echo "[program:freescout-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $install_path/artisan queue:work --queue=emails,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$install_path/storage/logs/worker.log
stopwaitsecs=3600" > /etc/supervisor/conf.d/freescout-worker.conf

# Refresh supervisor to read the new config
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start freescout-worker:*

#
# Final Permissions Fix
# 
sudo chown -R www-data:www-data $install_path

#
# Finish
#
protocol='http'

echo "
########################################################
##  Congratulations! Installation is almost finished  ##
########################################################

To complete installation open the Helpdesk URL provided below in your browser
and follow instructions.

Helpdesk URL: $protocol://$domain_name/install

Database Host: localhost
Database Port: 3306
Database Name: freescout
Database Username: freescout
Database Password: $mysql_pass
"