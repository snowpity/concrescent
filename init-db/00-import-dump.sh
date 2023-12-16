#!/usr/bin/env bash

pass=rootpasswd
mysql -uroot --password=${pass} --execute "CREATE DATABASE IF NOT EXISTS concrescent ;"
mysql -uroot --password=${pass} concrescent < /docker-entrypoint-initdb.d/concrescent-reg.sql
