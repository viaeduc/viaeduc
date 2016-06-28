#!/bin/bash

echo -e "\n -- Starting ExtRessources Daily cron"
# echo -e "\n1. Creating lock folder if not exists"
mkdir -p ./app/cache/prod/crons
# echo -e "\n2. Checking if script already running"
if [ ! -f ./app/cache/prod/crons/rpeextressourcesweekly ]
then
    umask 0002
    # echo -e "\n3. Running"
    # echo -e "\n3a. Creating lock file"
    touch ./app/cache/prod/crons/rpeextressourcesweekly
    # echo -e "\n3b. Run cron command"
    echo -e "\n -- Noticia source"
    php app/console rpe:extressources:batch weekly --source=noticia --env=prod
    # echo -e "\n -- Beebac source"
    # php app/console rpe:extressources:batch weekly --source=beebac
    echo -e "\n -- Regenerate index"
    php app/console pum:search:regenerateindex
    # echo -e "\n3c. Deleting lock file"
    rm ./app/cache/prod/crons/rpeextressourcesweekly
else
    # echo -e "\n2a. Script already in progress"
    2>/dev/null
fi
echo -e "\n -- Ended ExtRessources Daily cron"