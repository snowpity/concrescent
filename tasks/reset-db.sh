#!/usr/bin/env bash

docker compose down mysql
echo "Going to do: sudo rm -r $(pwd)/var/mysql-data"

# ask if continue
read -p "Are you sure you want to delete the database? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[yY]$ ]]
then
    exit 1
fi


sudo rm -rI "$(pwd)/var/mysql-data" && mkdir -p "$(pwd)/var/mysql-data"
