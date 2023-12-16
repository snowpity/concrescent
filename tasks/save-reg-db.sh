#!/usr/bin/env bash

docker compose exec mysql mysqldump concrescent -uroot -p > ./init-db/concrescent-reg.sql
