#!/bin/sh
set -eu

GATEWAY_URL="${GATEWAY_URL:-http://127.0.0.1:8081}"
APP_SERVICE="${APP_SERVICE:-app}"
COMPOSE="${1:-${COMPOSE:-docker compose}}"

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

request() {
  method="$1"
  url="$2"
  headers_file="$tmp_dir/headers"
  body_file="$tmp_dir/body"
  shift 2

  status="$(curl -sS -X "$method" -D "$headers_file" -o "$body_file" -w '%{http_code}' "$@" "$url" || true)"
  body="$(cat "$body_file")"
}

assert_status() {
  expected="$1"
  label="$2"

  if [ "$status" != "$expected" ]; then
    echo "FAIL [$label] expected HTTP $expected, got $status" >&2
    echo "$body" >&2
    exit 1
  fi
}

assert_body_contains() {
  expected="$1"
  label="$2"

  if ! printf '%s' "$body" | grep -Fq "$expected"; then
    echo "FAIL [$label] expected body to contain: $expected" >&2
    echo "$body" >&2
    exit 1
  fi
}

tamper_token_signature() {
  token="$1"
  header_and_payload="${token%.*}"
  signature="${token##*.}"
  first_char="$(printf '%s' "$signature" | cut -c 1)"
  rest="$(printf '%s' "$signature" | cut -c 2-)"

  if [ "$first_char" = "A" ]; then
    printf '%s.B%s' "$header_and_payload" "$rest"
  else
    printf '%s.A%s' "$header_and_payload" "$rest"
  fi
}

echo "[gateway-smoke] checking missing token fail-closed behavior" >&2
request GET "$GATEWAY_URL/protected"
assert_status 401 "missing token"
assert_body_contains '"error":"missing_token"' "missing token"
assert_body_contains '"request_id":' "missing token request_id"

echo "[gateway-smoke] preparing database" >&2
$COMPOSE exec -T "$APP_SERVICE" php artisan migrate --force >/dev/null

echo "[gateway-smoke] issuing valid token" >&2
valid_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience=apishka.ru --expires-offset=900 --not-before-offset=0)"

echo "[gateway-smoke] checking valid token reaches upstream with sanitized context" >&2
request GET "$GATEWAY_URL/protected" \
  -H "Authorization: Bearer $valid_token" \
  -H "X-Idshka-User-Id: spoofed-user" \
  -H "X-Idshka-Scopes: admin"
assert_status 200 "valid token"
assert_body_contains '"authenticated":"1"' "valid token context"
assert_body_contains '"audience":"apishka.ru"' "valid token audience"
assert_body_contains '"scopes":"orders.read"' "valid token scopes"
assert_body_contains '"permissions":"orders.read"' "valid token permissions"

if printf '%s' "$body" | grep -Fq 'spoofed-user'; then
  echo "FAIL [header sanitization] spoofed X-Idshka-User-Id reached upstream" >&2
  echo "$body" >&2
  exit 1
fi

if printf '%s' "$body" | grep -Fq "$valid_token"; then
  echo "FAIL [raw token leak] raw JWT reached upstream response" >&2
  exit 1
fi

echo "[gateway-smoke] checking array audience normalizes trusted header" >&2
array_audience_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience=wrong.apishka.ru,apishka.ru --expires-offset=900 --not-before-offset=0)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $array_audience_token"
assert_status 200 "array audience token"
assert_body_contains '"audience":"apishka.ru"' "array audience trusted header"

echo "[gateway-smoke] checking invalid signature" >&2
invalid_signature_token="$(tamper_token_signature "$valid_token")"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $invalid_signature_token"
assert_status 401 "invalid signature"
assert_body_contains '"error":"invalid_token"' "invalid signature"

echo "[gateway-smoke] checking audience mismatch" >&2
wrong_audience_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience=wrong.apishka.ru --expires-offset=900 --not-before-offset=0)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $wrong_audience_token"
assert_status 401 "wrong audience"
assert_body_contains '"error":"audience_mismatch"' "wrong audience"

echo "[gateway-smoke] checking expired token" >&2
expired_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience=apishka.ru --expires-offset=-60 --not-before-offset=-120)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $expired_token"
assert_status 401 "expired token"
assert_body_contains '"error":"expired_token"' "expired token"

echo "[gateway-smoke] checking not-before token" >&2
not_before_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience=apishka.ru --expires-offset=900 --not-before-offset=600)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $not_before_token"
assert_status 401 "not-before token"
assert_body_contains '"error":"invalid_token"' "not-before token"

echo "[gateway-smoke] passed" >&2
