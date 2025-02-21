#!/usr/bin/env bash

if ! [[ -f .env ]]; then
  cp .env.example .env
  echo "Edit .env file with your timezone and other things."
fi

if ! [[ -f msmtprc ]]; then
  cp msmtprc.example msmtprc
  echo "Edit msmtprc file with your SMTP server configuration and other things."
fi

if ! [[ -f concrescent.php ]]; then
  cp concrescent.example.php concrescent.php
  echo "Edit concrescent.php file with your database, PayPal, Slack, event properties, and other things."
fi
