#!/bin/sh
set -eu

GATEWAY_URL="${GATEWAY_URL:-http://127.0.0.1:8081}"
JWKS_URL="${JWKS_URL:-http://127.0.0.1:8080/oauth/jwks.json}"
APP_SERVICE="${APP_SERVICE:-app}"
GATEWAY_SERVICE="${GATEWAY_SERVICE:-demo-resource-gateway}"
AUDIENCE="${AUDIENCE:-example.test}"
COMPOSE="${1:-${COMPOSE:-docker compose}}"

resolve_gateway_jwks_cache_seconds() {
  if [ -n "${JWKS_CACHE_SECONDS:-}" ]; then
    printf '%s\n' "$JWKS_CACHE_SECONDS"
    return 0
  fi

  if [ -n "${GATEWAY_JWKS_CACHE_SECONDS:-}" ]; then
    printf '%s\n' "$GATEWAY_JWKS_CACHE_SECONDS"
    return 0
  fi

  runtime_seconds="$($COMPOSE exec -T "$GATEWAY_SERVICE" printenv GATEWAY_JWKS_CACHE_SECONDS 2>/dev/null | tr -d '\r' || true)"

  if [ -n "$runtime_seconds" ]; then
    printf '%s\n' "$runtime_seconds"
    return 0
  fi

  printf '%s\n' "20"
}

JWKS_CACHE_SECONDS="$(resolve_gateway_jwks_cache_seconds)"

case "$JWKS_CACHE_SECONDS" in
  ''|*[!0-9]*)
    echo "FAIL [jwks cache ttl config] expected numeric seconds, got: $JWKS_CACHE_SECONDS" >&2
    exit 1
    ;;
esac

if [ "$JWKS_CACHE_SECONDS" -lt 1 ]; then
  echo "FAIL [jwks cache ttl config] expected positive seconds, got: $JWKS_CACHE_SECONDS" >&2
  exit 1
fi

echo "[FIX:gateway-smoke-cache-ttl] using JWKS cache TTL ${JWKS_CACHE_SECONDS}s" >&2

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

sanitize_body_for_logs() {
  printf '%s' "$body" \
    | sed -E 's/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/[redacted-jwt]/g' \
    | head -c 1200
  printf '\n'
}

assert_status() {
  expected="$1"
  label="$2"

  if [ "$status" != "$expected" ]; then
    echo "FAIL [$label] expected HTTP $expected, got $status" >&2
    sanitize_body_for_logs >&2
    exit 1
  fi
}

assert_body_contains() {
  expected="$1"
  label="$2"

  if ! printf '%s' "$body" | grep -Fq "$expected"; then
    echo "FAIL [$label] expected body to contain: $expected" >&2
    sanitize_body_for_logs >&2
    exit 1
  fi
}

wait_for_jwks() {
  for attempt in $(seq 1 30); do
    status="$(curl -sS -o "$tmp_dir/jwks" -w '%{http_code}' "$JWKS_URL" || true)"

    if [ "$status" = "200" ]; then
      return 0
    fi

    sleep 1
  done

  echo "FAIL [jwks readiness] expected HTTP 200 from $JWKS_URL, got $status" >&2
  body="$(cat "$tmp_dir/jwks")"
  sanitize_body_for_logs >&2
  exit 1
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

base64url_encode() {
  printf '%s' "$1" | openssl base64 -A | tr '+/' '-_' | tr -d '='
}

replace_token_kid() {
  token="$1"
  kid="$2"
  header="$(base64url_encode "{\"alg\":\"RS256\",\"kid\":\"$kid\",\"typ\":\"JWT\"}")"
  rest="${token#*.}"

  printf '%s.%s' "$header" "$rest"
}

token_kid() {
  token="$1"

  printf '%s' "$token" | $COMPOSE exec -T "$APP_SERVICE" php -r '
$token = trim(stream_get_contents(STDIN));
$parts = explode(".", $token);
$header = $parts[0] ?? "";
$remainder = strlen($header) % 4;

if ($remainder > 0) {
    $header .= str_repeat("=", 4 - $remainder);
}

$json = base64_decode(strtr($header, "-_", "+/"), true);
$data = is_string($json) ? json_decode($json, true) : null;

if (is_array($data) && isset($data["kid"]) && is_string($data["kid"])) {
    echo $data["kid"];
}
'
}

rotate_and_retire_kid() {
  kid="$1"

  $COMPOSE exec -T "$APP_SERVICE" php -r '
require "vendor/autoload.php";

$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = $app->make(App\Domain\Issuer\Services\SigningKeyService::class);
$service->prepareNextKey();
$service->activateNextKey();
$service->forceRetireByKid($argv[1]);
' "$kid" >/dev/null
}

echo "[gateway-smoke] checking missing token fail-closed behavior" >&2
request GET "$GATEWAY_URL/protected"
assert_status 401 "missing token"
assert_body_contains '"error":"missing_token"' "missing token"
assert_body_contains '"request_id":' "missing token request_id"

echo "[gateway-smoke] preparing database" >&2
$COMPOSE exec -T "$APP_SERVICE" php artisan migrate --force >/dev/null

echo "[gateway-smoke] issuing valid token" >&2
valid_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience="$AUDIENCE" --expires-offset=900 --not-before-offset=0)"

echo "[gateway-smoke] waiting for JWKS readiness" >&2
wait_for_jwks

echo "[gateway-smoke] checking valid token reaches upstream with sanitized context" >&2
request GET "$GATEWAY_URL/protected" \
  -H "Authorization: Bearer $valid_token" \
  -H "X-Idshka-User-Id: spoofed-user" \
  -H "X-Idshka-Scopes: admin" \
  -H "X-Idshka-Canary: spoofed-canary"
assert_status 200 "valid token"
assert_body_contains '"authenticated":"1"' "valid token context"
assert_body_contains "\"audience\":\"$AUDIENCE\"" "valid token audience"
assert_body_contains '"scopes":"orders.read"' "valid token scopes"
assert_body_contains '"permissions":"orders.read"' "valid token permissions"

if printf '%s' "$body" | grep -Fq 'spoofed-user'; then
  echo "FAIL [header sanitization] spoofed X-Idshka-User-Id reached upstream" >&2
  sanitize_body_for_logs >&2
  exit 1
fi

if printf '%s' "$body" | grep -Fq 'spoofed-canary'; then
  echo "FAIL [header sanitization] spoofed X-Idshka-Canary reached upstream" >&2
  sanitize_body_for_logs >&2
  exit 1
fi

if printf '%s' "$body" | grep -Fq "$valid_token"; then
  echo "FAIL [raw token leak] raw JWT reached upstream response" >&2
  exit 1
fi

echo "[gateway-smoke] checking JWKS cache TTL expiry" >&2
valid_kid="$(token_kid "$valid_token")"

if [ -z "$valid_kid" ]; then
  echo "FAIL [token kid] unable to read token kid" >&2
  exit 1
fi

rotate_and_retire_kid "$valid_kid"

request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $valid_token"
assert_status 200 "cached key before ttl expiry"

sleep "$((JWKS_CACHE_SECONDS + 1))"

request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $valid_token"
assert_status 401 "stale key after ttl expiry"
assert_body_contains '"error":"invalid_token"' "stale key after ttl expiry"
assert_body_contains '"JWT signing key is unknown."' "stale key after ttl expiry"

echo "[gateway-smoke] checking array audience normalizes trusted header" >&2
array_audience_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience=wrong.example.test,"$AUDIENCE" --expires-offset=900 --not-before-offset=0)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $array_audience_token"
assert_status 200 "array audience token"
assert_body_contains "\"audience\":\"$AUDIENCE\"" "array audience trusted header"

echo "[gateway-smoke] checking invalid signature" >&2
invalid_signature_token="$(tamper_token_signature "$valid_token")"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $invalid_signature_token"
assert_status 401 "invalid signature"
assert_body_contains '"error":"invalid_token"' "invalid signature"

echo "[gateway-smoke] checking unknown kid" >&2
unknown_kid_token="$(replace_token_kid "$valid_token" "unknown-kid")"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $unknown_kid_token"
assert_status 401 "unknown kid"
assert_body_contains '"error":"invalid_token"' "unknown kid"
assert_body_contains '"JWT signing key is unknown."' "unknown kid"

echo "[gateway-smoke] checking audience mismatch" >&2
wrong_audience_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience=wrong.example.test --expires-offset=900 --not-before-offset=0)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $wrong_audience_token"
assert_status 401 "wrong audience"
assert_body_contains '"error":"audience_mismatch"' "wrong audience"

echo "[gateway-smoke] checking expired token" >&2
expired_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience="$AUDIENCE" --expires-offset=-60 --not-before-offset=-120)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $expired_token"
assert_status 401 "expired token"
assert_body_contains '"error":"expired_token"' "expired token"

echo "[gateway-smoke] checking not-before token" >&2
not_before_token="$($COMPOSE exec -T "$APP_SERVICE" php artisan idshka:gateway-smoke-token --audience="$AUDIENCE" --expires-offset=900 --not-before-offset=600)"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $not_before_token"
assert_status 401 "not-before token"
assert_body_contains '"error":"invalid_token"' "not-before token"

echo "[gateway-smoke] checking JWKS unavailable fail-closed behavior" >&2
$COMPOSE stop nginx >/dev/null
jwks_down_token="$(replace_token_kid "$valid_token" "unknown-kid-jwks-down")"
request GET "$GATEWAY_URL/protected" -H "Authorization: Bearer $jwks_down_token"
assert_status 502 "jwks unavailable"
assert_body_contains '"error":"jwks_unavailable"' "jwks unavailable"
assert_body_contains '"request_id":' "jwks unavailable request_id"

echo "[gateway-smoke] passed" >&2
