#!/bin/sh
set -eu

cert_path="${HTTPS_CERT_PATH:-/certs/localhost.crt}"
key_path="${HTTPS_KEY_PATH:-/certs/localhost.key}"

mkdir -p "$(dirname "$cert_path")" "$(dirname "$key_path")"

if [ ! -f "$cert_path" ] || [ ! -f "$key_path" ]; then
  openssl req -x509 -newkey rsa:2048 -nodes \
    -keyout "$key_path" \
    -out "$cert_path" \
    -days 365 \
    -subj "/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,IP:127.0.0.1,IP:::1"
fi

exec "$@"
