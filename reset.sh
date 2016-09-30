#!/bin/bash
set -e
cd "`dirname "$0"`"

echo -e "\n** WARNING **"
echo "This will reset the application to an initial state."
echo "Do not use in a production environment."
read -sn 1 -p "Press any key to continue or ctrl+C to cancel..."
echo ""

echo -e "\n-- Running app/check.php"
check="$(php app/check.php || true)"
echo "$check" | GREP_COLOR='01;33' grep WARNING || true;
echo "$check" | GREP_COLOR='01;41' grep ERROR || true;

if [ ! -f composer.phar ]; then
    echo -e "\n-- Downloading Composer"
    curl -s http://getcomposer.org/installer | php
fi

# echo -e "\n-- Clean medias files"
# rm -rf web/medias/default/*

echo -e "\n-- Running composer.phar install"
php composer.phar install

echo -e "\n-- Installing assets"
if [ "$1" == "--symlink" ]; then
    ./app/console assets:install --symlink -v
else
    ./app/console assets:install -v
fi

echo -e "\n-- Dropping database"
./app/console doctrine:database:drop --force -v || true

echo -e "\n-- Creating database"
./app/console doctrine:database:create -v

echo -e "\n-- Creating schema"
./app/console doctrine:schema:create -v

echo -e "\n-- Importing beams"
./app/console pum:beam:import -v

# echo -e "\n-- Creating Super Admin"
# ./app/console pum:users:create_superadmin --email=your@mail.com

echo -e "\n-- Loading fixtures"
./app/console doctrine:fixtures:load --append -v

echo -e "\n-- Clearing cache"
./app/console cache:clear
./app/console cache:clear --env=prod

echo -e "\n-- Dumping assets"
./app/console assetic:dump --env=prod --no-debug -v

echo -e "\n-- Running RPE Commands"
echo -e "    -- RPE Mail import"
./app/console rpe:emails:import