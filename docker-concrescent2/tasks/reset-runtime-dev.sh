#!/usr/bin/env bash

docker compose down

./tasks/destroy-mysql-data.sh
