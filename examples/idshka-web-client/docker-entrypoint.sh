#!/bin/sh
set -eu

cert_dir="${HTTPS_CERT_DIR:-/certs}"
key_path="${HTTPS_KEY_PATH:-$cert_dir/localhost.key}"
cert_path="${HTTPS_CERT_PATH:-$cert_dir/localhost.crt}"

mkdir -p "$cert_dir"

if [ ! -s "$key_path" ] || [ ! -s "$cert_path" ]; then
  openssl req \
    -x509 \
    -newkey rsa:2048 \
    -nodes \
    -sha256 \
    -days 365 \
    -keyout "$key_path" \
    -out "$cert_path" \
    -subj "/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
fi

export HTTPS_KEY_PATH="$key_path"
export HTTPS_CERT_PATH="$cert_path"

exec "$@"
