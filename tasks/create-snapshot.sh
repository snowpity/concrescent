#!/usr/bin/env bash

here=$(pwd)

branch=$(git rev-parse --abbrev-ref HEAD)
sha=$(git rev-parse --short HEAD)
echo "Detected a branch, we are at : ${branch} (${sha})"

branch=$(echo "${branch}" | sed 's/[^a-zA-Z0-9._-]/-/g')

assembly="concrescent-cm2_${branch}-${sha}"
archive="${assembly}.tar.gz"
targetDir="./target/${assembly}"

rm -rf "${targetDir}"
mkdir -p "${targetDir}"

echo "Installing dependencies... Don't forget to reinstall dev mode if you need it."
composer install --no-dev --classmap-authoritative --no-interaction

echo "Copying files"
mkdir -p "${targetDir}/config"
mkdir -p "${targetDir}/var"
cp -r "cm2" "${targetDir}/"
cp -r "src" "${targetDir}/"
cp -r "templates" "${targetDir}/"
cp -r "vendor" "${targetDir}/"
cp -r ".env.example" "${targetDir}/.env.example"
cp -r ".env.prod" "${targetDir}/.env.prod"
cp -r "config/concrescent.example.php" "${targetDir}/config/concrescent.example.php"
cp -r "config/msmtprc.example" "${targetDir}/config/msmtprc.example"



cd "./target" || exit

echo "Making archive..."
rm -rf "${archive}"
tar -caf "${archive}" -C "${assembly}" .
echo "Deleting assembly..."
rm -rf "${assembly}"

echo "Done."
cd "${here}" || exit
