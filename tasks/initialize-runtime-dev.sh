#!/usr/bin/env bash

rm .env
cp .env.dev .env
echo "TZ=$(cat /etc/timezone)" >> .env

./tasks/reset-runtime-dev.sh

docker compose up --detach
