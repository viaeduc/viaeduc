#!/bin/bash

echo -e "\n -- Starting notification cleaning cron"
# echo -e "\n1. Creating lock folder if not exists"
mkdir -p ./app/cache/prod/crons
# echo -e "\n2. Checking if script already running"
if [ ! -f ./app/cache/prod/crons/rpenotifcleanlock ]
then
    umask 0002
    # echo -e "\n3. Running"
    # echo -e "\n3a. Creating lock file"
    touch ./app/cache/prod/crons/rpenotifcleanlock
    # echo -e "\n3b. Run cron command"
    php app/console rpe:notification:clearbyuser --env=prod
    # echo -e "\n3c. Deleting lock file"
    rm ./app/cache/prod/crons/rpenotifcleanlock
else
    # echo -e "\n2a. Script already in progress"
    2>/dev/null
fi
echo -e "\n -- Ended notification cleaning cron"
