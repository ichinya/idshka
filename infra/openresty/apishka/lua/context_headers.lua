local cjson = require("cjson.safe")

local _M = {}
local IDSHKA_HEADER_NAME = "x-idshka"
local IDSHKA_HEADER_PREFIX = "x-idshka-"

local function log_debug(message, context)
    ngx.log(ngx.DEBUG, "[gateway.context_headers] ", message, " ", cjson.encode(context or {}))
end

local function is_idshka_header(name)
    local normalized = string.lower(name)

    return normalized == IDSHKA_HEADER_NAME
        or string.sub(normalized, 1, string.len(IDSHKA_HEADER_PREFIX)) == IDSHKA_HEADER_PREFIX
end

local function sanitize_incoming_headers()
    local headers = ngx.req.get_headers(0, true)
    local removed_count = 0

    for name, _ in pairs(headers) do
        if is_idshka_header(name) then
            ngx.req.clear_header(name)
            removed_count = removed_count + 1
        end
    end

    ngx.req.clear_header("Authorization")

    return removed_count
end

local function permissions_to_header(permissions)
    if type(permissions) ~= "table" then
        return ""
    end

    local values = {}

    for _, permission in ipairs(permissions) do
        values[#values + 1] = tostring(permission)
    end

    return table.concat(values, ",")
end

local function audience_to_header(claims)
    if type(claims.idshka_audience) == "string" and claims.idshka_audience ~= "" then
        return claims.idshka_audience
    end

    if type(claims.aud) == "string" then
        return claims.aud
    end

    if type(claims.aud) == "table" then
        local values = {}

        for _, audience in ipairs(claims.aud) do
            values[#values + 1] = tostring(audience)
        end

        return table.concat(values, " ")
    end

    return ""
end

function _M.apply(claims, request_id)
    local removed_count = sanitize_incoming_headers()

    ngx.req.set_header("X-Idshka-Authenticated", "1")
    ngx.req.set_header("X-Idshka-User-Id", tostring(claims.sub))
    ngx.req.set_header("X-Idshka-Site-Id", tostring(claims.site_id))
    ngx.req.set_header("X-Idshka-Audience", audience_to_header(claims))
    ngx.req.set_header("X-Idshka-Scopes", tostring(claims.scope))
    ngx.req.set_header("X-Idshka-Permissions", permissions_to_header(claims.permissions))
    ngx.req.set_header("X-Idshka-JTI", tostring(claims.jti))
    ngx.req.set_header("X-Idshka-Token-Exp", tostring(math.floor(tonumber(claims.exp) or 0)))
    ngx.req.set_header("X-Request-Id", tostring(request_id or ""))

    log_debug("applied", {
        request_id = request_id,
        removed_idshka_headers_count = removed_count,
        user_id = claims.sub,
        site_id = claims.site_id,
        audience = claims.aud,
        jti = claims.jti,
    })
end

return _M
