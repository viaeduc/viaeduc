#!/bin/bash

echo -e "\n -- Starting Low Priority Notification cron"
# echo -e "\n1. Creating lock folder if not exists"
mkdir -p ./app/cache/prod/crons
# echo -e "\n2. Checking if script already running"
if [ ! -f ./app/cache/prod/crons/rpenotiflock ]
then
    umask 0002
    # echo -e "\n3. Running"
    # echo -e "\n3a. Creating lock file"
    touch ./app/cache/prod/crons/rpenotiflock
    echo -e "\n3b. Run cron command"
    php app/console rpe:notification:treat $1 --mode=heavy --env=prod
    # echo -e "\n3c. Deleting lock file"
    rm ./app/cache/prod/crons/rpenotiflock
else
    # echo -e "\n2a. Script already in progress"
    2>/dev/null
fi