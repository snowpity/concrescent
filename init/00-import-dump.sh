#!/usr/bin/env bash

databasePath="/docker-entrypoint-initdb.d/default.sql"

if [[ -f "/docker-entrypoint-initdb.d/concrescent-reg.sql" ]]; then
  databasePath="/docker-entrypoint-initdb.d/concrescent-reg.sql"
fi

echo $databasePath

mysql -uroot --password="${MYSQL_ROOT_PASSWORD}" --execute "CREATE DATABASE IF NOT EXISTS concrescent ;"
mysql -uroot --password="${MYSQL_ROOT_PASSWORD}" concrescent < "${databasePath}"
