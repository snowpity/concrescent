#!/usr/bin/env bash

certPath=./config/mailpit-certs

openssl genrsa -out ${certPath}/mailpit.key 2048
openssl req -new -config ${certPath}/mailpit.conf -key ${certPath}/mailpit.key -out ${certPath}/mailpit.csr
openssl x509 -req -in ${certPath}/mailpit.csr -key ${certPath}/mailpit.key -out ${certPath}/mailpit.crt -days 360

echo "Don't forget to update the TLS fingerprint in MSMTPRC config file."
echo "You can find the fingerprint by running :"
echo "docker compose run -it concrescent /usr/bin/msmtp -a defaultaccount --tls-certcheck=off --tls-trust-file= --serverinfo"
echo "then update the tls_fingerprint field in msmtprc default example file."
