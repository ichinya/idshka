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

local function fetch_jwks()
    log_debug("fetch_started")

    local response = ngx.location.capture("/__idshka_jwks")

    if response == nil then
        log_warn("fetch_failed", { reason = "no_response" })

        return nil, gateway_error(502, "jwks_unavailable", "JWKS is temporarily unavailable.")
    end

    if response.status ~= 200 then
        log_warn("fetch_failed", { status = response.status })

        return nil, gateway_error(502, "jwks_unavailable", "JWKS is temporarily unavailable.")
    end

    local payload, decode_error = cjson.decode(response.body)

    if payload == nil or type(payload.keys) ~= "table" then
        log_warn("decode_failed", { error = decode_error })

        return nil, gateway_error(502, "jwks_unavailable", "JWKS is temporarily unavailable.")
    end

    log_debug("fetch_completed", { keys_count = #payload.keys })

    return payload.keys, nil
end

function _M.get_key(kid, ttl_seconds)
    if kid == nil or kid == "" then
        return nil, gateway_error(401, "invalid_token", "JWT key id is required.")
    end

    local cache_key = "kid:" .. kid
    local cached = cache:get(cache_key)

    if cached ~= nil then
        local key = cjson.decode(cached)

        if key ~= nil then
            log_debug("cache_hit", { kid = kid })

            return key, nil
        end
    end

    local keys, error_response = fetch_jwks()

    if keys == nil then
        return nil, error_response
    end

    local normalized_ttl = math.max(1, tonumber(ttl_seconds) or 120)
    local selected = nil

    for _, key in ipairs(keys) do
        if type(key) == "table" and type(key.kid) == "string" then
            cache:set("kid:" .. key.kid, cjson.encode(key), normalized_ttl)

            if key.kid == kid then
                selected = key
            end
        end
    end

    if selected == nil then
        log_warn("kid_not_found", { kid = kid })

        return nil, gateway_error(401, "invalid_token", "JWT signing key is unknown.")
    end

    log_debug("cache_store_completed", { kid = kid, ttl_seconds = normalized_ttl })

    return selected, nil
end

return _M
