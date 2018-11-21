#!/usr/bin/env bash

#
# remove root
#

adduser dalamud
usermod -aG sudo dalamud
sed -i 's|PermitRootLogin yes|PermitRootLogin no|g' /etc/ssh/sshd_config
sudo systemctl reload sshd.service

#
# Install PHP + Composer
#

sudo apt-get update -y && sudo apt-get upgrade -y
sudo apt-get install -y software-properties-common acl htop unzip curl git dos2unix
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y

sudo apt-get install -y php7.2-fpm php-apcu php7.2-dev php7.2-cli php7.2-tidy php7.2-json php7.2-fpm
sudo apt-get install -y php7.2-intl php7.2-mysql php7.2-sqlite php7.2-curl php7.2-gd php7.2-mbstring
sudo apt-get install -y php7.2-dom php7.2-xml php7.2-zip php7.2-tidy php7.2-bcmath

sudo sed -i 's|display_errors = Off|display_errors = On|' /etc/php/7.2/fpm/php.ini
sudo sed -i 's|memory_limit = 512M|memory_limit = 4G|' /etc/php/7.2/fpm/php.ini

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

sudo service php7.2-fpm restart
