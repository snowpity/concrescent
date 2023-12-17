#!/usr/bin/env bash

docker compose down mysql
echo "Going to do: sudo rm -r $(pwd)/var/mysql-data"

sudo rm -rI "$(pwd)/var/mysql-data" && mkdir -p "$(pwd)/var/mysql-data"
