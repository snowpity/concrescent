#!/usr/bin/env bash

docker compose run -it --rm concrescent /usr/bin/msmtp -a defaultaccount --tls-certcheck=off --tls-trust-file= --serverinfo
