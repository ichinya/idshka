local cjson = require("cjson.safe")

local _M = {}
local cache = ngx.shared.idshka_jwks

local function gateway_error(status, code, message)
    return {
        status = status,
        error = code,
        message = message,
    }
end

local function log_debug(message, context)
    ngx.log(ngx.DEBUG, "[gateway.jwks] ", message, " ", cjson.encode(context or {}))
end

local function log_warn(message, context)
    ngx.log(ngx.WARN, "[gateway.jwks] ", message, " ", cjson.encode(context or {}))
end

local function normalize_ttl_seconds(ttl_seconds)
    return math.max(1, math.floor(tonumber(ttl_seconds) or 120))
end

local function cache_entry_for(key, ttl_seconds)
    local now = ngx.now()

    return {
        key = key,
        cached_at = now,
        expires_at = now + ttl_seconds,
    }
end

local function decode_cache_entry(cached)
    local entry = cjson.decode(cached)

    if type(entry) ~= "table" or type(entry.key) ~= "table" or type(entry.expires_at) ~= "number" then
        return nil
    end

    return entry
end

local function fetch_jwks(request_id, kid)
    log_debug("fetch_started", { request_id = request_id, kid = kid, cache_outcome = "refresh" })

    local response = ngx.location.capture("/__idshka_jwks")

    if response == nil then
        log_warn("fetch_failed", { request_id = request_id, kid = kid, reason = "no_response" })

        return nil, gateway_error(502, "jwks_unavailable", "JWKS is temporarily unavailable.")
    end

    if response.status ~= 200 then
        log_warn("fetch_failed", { request_id = request_id, kid = kid, status = response.status })

        return nil, gateway_error(502, "jwks_unavailable", "JWKS is temporarily unavailable.")
    end

    local payload, decode_error = cjson.decode(response.body)

    if payload == nil or type(payload.keys) ~= "table" then
        log_warn("decode_failed", { request_id = request_id, kid = kid, error = decode_error })

        return nil, gateway_error(502, "jwks_unavailable", "JWKS is temporarily unavailable.")
    end

    log_debug("fetch_completed", { request_id = request_id, kid = kid, keys_count = #payload.keys })

    return payload.keys, nil
end

function _M.get_key(kid, ttl_seconds, request_id)
    if kid == nil or kid == "" then
        return nil, gateway_error(401, "invalid_token", "JWT key id is required.")
    end

    local normalized_ttl = normalize_ttl_seconds(ttl_seconds)
    local cache_key = "kid:" .. kid
    local cached = cache:get(cache_key)

    if cached ~= nil then
        local entry = decode_cache_entry(cached)

        if entry == nil then
            cache:delete(cache_key)
            log_warn("cache_invalid", { request_id = request_id, kid = kid, cache_outcome = "invalid" })
        elseif entry.expires_at <= ngx.now() then
            cache:delete(cache_key)
            log_warn("cache_expired", {
                request_id = request_id,
                kid = kid,
                cache_outcome = "expired",
                cached_at = entry.cached_at,
                expires_at = entry.expires_at,
            })
        else
            log_debug("cache_hit", {
                request_id = request_id,
                kid = kid,
                cache_outcome = "hit",
                expires_at = entry.expires_at,
            })

            return entry.key, nil
        end
    else
        log_debug("cache_miss", { request_id = request_id, kid = kid, cache_outcome = "miss" })
    end

    local keys, error_response = fetch_jwks(request_id, kid)

    if keys == nil then
        log_warn("fail_closed", { request_id = request_id, kid = kid, error = error_response and error_response.error })

        return nil, error_response
    end

    local selected = nil

    for _, key in ipairs(keys) do
        if type(key) == "table" and type(key.kid) == "string" then
            cache:set("kid:" .. key.kid, cjson.encode(cache_entry_for(key, normalized_ttl)), normalized_ttl)

            if key.kid == kid then
                selected = key
            end
        end
    end

    if selected == nil then
        log_warn("kid_not_found", { request_id = request_id, kid = kid, cache_outcome = "refresh_miss" })

        return nil, gateway_error(401, "invalid_token", "JWT signing key is unknown.")
    end

    log_debug("cache_store_completed", {
        request_id = request_id,
        kid = kid,
        cache_outcome = "stored",
        ttl_seconds = normalized_ttl,
        expires_at = ngx.now() + normalized_ttl,
    })

    return selected, nil
end

return _M
