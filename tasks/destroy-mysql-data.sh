#!/usr/bin/env bash

echo "Going to do: sudo rm -r ./mysql-data"
sudo rm -rI ./mysql-data \
  && mkdir -p ./mysql-data
