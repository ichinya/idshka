local cjson = require("cjson.safe")
local jwks_cache = require("jwks_cache")

local _M = {}

local function gateway_error(status, code, message)
    return {
        status = status,
        error = code,
        message = message,
    }
end

local function log_debug(message, context)
    ngx.log(ngx.DEBUG, "[gateway.jwt_validate] ", message, " ", cjson.encode(context or {}))
end

local function log_warn(message, context)
    ngx.log(ngx.WARN, "[gateway.jwt_validate] ", message, " ", cjson.encode(context or {}))
end

local function base64url_decode(value)
    if type(value) ~= "string" or value == "" then
        return nil
    end

    local normalized = value:gsub("-", "+"):gsub("_", "/")
    local remainder = #normalized % 4

    if remainder == 1 then
        return nil
    end

    if remainder > 0 then
        normalized = normalized .. string.rep("=", 4 - remainder)
    end

    return ngx.decode_base64(normalized)
end

local function wrap_base64(value)
    local lines = {}

    for index = 1, #value, 64 do
        lines[#lines + 1] = value:sub(index, index + 63)
    end

    return table.concat(lines, "\n")
end

local function der_length(length)
    if length < 128 then
        return string.char(length)
    end

    local bytes = {}
    local remaining = length

    while remaining > 0 do
        table.insert(bytes, 1, string.char(remaining % 256))
        remaining = math.floor(remaining / 256)
    end

    return string.char(0x80 + #bytes) .. table.concat(bytes)
end

local function der_tlv(tag, value)
    return string.char(tag) .. der_length(#value) .. value
end

local function der_integer(value)
    if value == nil or value == "" then
        return nil
    end

    while #value > 1 and value:byte(1) == 0 and value:byte(2) < 0x80 do
        value = value:sub(2)
    end

    if value:byte(1) >= 0x80 then
        value = string.char(0) .. value
    end

    return der_tlv(0x02, value)
end

local function public_jwk_to_pem(jwk)
    if type(jwk) ~= "table" or jwk.kty ~= "RSA" then
        return nil
    end

    local modulus = base64url_decode(jwk.n)
    local exponent = base64url_decode(jwk.e)
    local der_modulus = der_integer(modulus)
    local der_exponent = der_integer(exponent)

    if der_modulus == nil or der_exponent == nil then
        return nil
    end

    local rsa_public_key = der_tlv(0x30, der_modulus .. der_exponent)
    local rsa_algorithm_identifier = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00"
    local subject_public_key = der_tlv(0x03, string.char(0) .. rsa_public_key)
    local subject_public_key_info = der_tlv(0x30, rsa_algorithm_identifier .. subject_public_key)
    local encoded = ngx.encode_base64(subject_public_key_info)

    return "-----BEGIN PUBLIC KEY-----\n" .. wrap_base64(encoded) .. "\n-----END PUBLIC KEY-----\n"
end

local function write_file(path, value)
    local file = io.open(path, "wb")

    if file == nil then
        return false
    end

    file:write(value)
    file:close()

    return true
end

local function remove_file(path)
    if path ~= nil then
        os.remove(path)
    end
end

local function openssl_status_ok(result, exit_type, code)
    return result == true or result == 0 or code == 0 or exit_type == "exit" and code == 0
end

local function verify_rs256_signature(signing_input, signature, jwk, request_id)
    local public_pem = public_jwk_to_pem(jwk)

    if public_pem == nil then
        log_warn("jwk_to_pem_failed", { request_id = request_id, kid = jwk and jwk.kid })

        return false
    end

    local unique = tostring(ngx.worker.pid()) .. "_" .. tostring(math.floor(ngx.now() * 1000000)) .. "_" .. tostring(math.random(100000, 999999))
    local base_path = "/tmp/idshka_jwt_" .. unique
    local data_path = base_path .. ".data"
    local signature_path = base_path .. ".sig"
    local public_key_path = base_path .. ".pub"

    local ok = write_file(data_path, signing_input)
        and write_file(signature_path, signature)
        and write_file(public_key_path, public_pem)

    if not ok then
        remove_file(data_path)
        remove_file(signature_path)
        remove_file(public_key_path)
        log_warn("temp_file_write_failed", { request_id = request_id })

        return false
    end

    local command = "openssl dgst -sha256 -verify " .. public_key_path .. " -signature " .. signature_path .. " " .. data_path .. " >/dev/null 2>&1"
    local result, exit_type, code = os.execute(command)

    remove_file(data_path)
    remove_file(signature_path)
    remove_file(public_key_path)

    return openssl_status_ok(result, exit_type, code)
end

local function audience_matches(claim_audience, expected_audience)
    if type(claim_audience) == "string" then
        if claim_audience == expected_audience then
            return true, expected_audience
        end

        return false, nil
    end

    if type(claim_audience) == "table" then
        for _, value in ipairs(claim_audience) do
            if value == expected_audience then
                return true, expected_audience
            end
        end
    end

    return false, nil
end

local function require_claims(payload)
    if type(payload.sub) ~= "string" or payload.sub == "" then
        return false
    end

    if type(payload.site_id) ~= "string" or payload.site_id == "" then
        return false
    end

    if payload.token_type ~= "user_api" then
        return false
    end

    if type(payload.scope) ~= "string" then
        return false
    end

    if type(payload.permissions) ~= "table" then
        return false
    end

    if type(payload.jti) ~= "string" or payload.jti == "" then
        return false
    end

    if type(payload.iat) ~= "number" or type(payload.nbf) ~= "number" then
        return false
    end

    if payload.exp ~= nil and type(payload.exp) ~= "number" then
        return false
    end

    return true
end

local function parse_token(token)
    local header_b64, payload_b64, signature_b64 = token:match("^([^.]+)%.([^.]+)%.([^.]+)$")

    if header_b64 == nil then
        return nil, nil, nil, nil
    end

    local header_json = base64url_decode(header_b64)
    local payload_json = base64url_decode(payload_b64)
    local signature = base64url_decode(signature_b64)

    if header_json == nil or payload_json == nil or signature == nil then
        return nil, nil, nil, nil
    end

    local header = cjson.decode(header_json)
    local payload = cjson.decode(payload_json)

    if type(header) ~= "table" or type(payload) ~= "table" then
        return nil, nil, nil, nil
    end

    return header, payload, signature, header_b64 .. "." .. payload_b64
end

function _M.validate(options)
    local request_id = options.request_id
    local authorization = ngx.var.http_authorization or ""

    log_debug("started", { request_id = request_id })

    if authorization == "" then
        log_warn("missing_token", { request_id = request_id })

        return nil, gateway_error(401, "missing_token", "Bearer token is required.")
    end

    local token = authorization:match("^[Bb]earer%s+(.+)$")

    if token == nil or token == "" then
        log_warn("malformed_authorization", { request_id = request_id })

        return nil, gateway_error(401, "invalid_token", "Bearer token is malformed.")
    end

    local header, payload, signature, signing_input = parse_token(token)

    if header == nil then
        log_warn("parse_failed", { request_id = request_id })

        return nil, gateway_error(401, "invalid_token", "JWT is malformed.")
    end

    if header.alg ~= options.allowed_alg or type(header.kid) ~= "string" or header.kid == "" then
        log_warn("header_invalid", { request_id = request_id, alg = header.alg, has_kid = header.kid ~= nil })

        return nil, gateway_error(401, "invalid_token", "JWT header is invalid.")
    end

    if header.typ ~= nil and header.typ ~= "JWT" then
        log_warn("typ_invalid", { request_id = request_id, typ = header.typ })

        return nil, gateway_error(401, "invalid_token", "JWT header is invalid.")
    end

    local jwk, key_error = jwks_cache.get_key(header.kid, options.jwks_cache_seconds)

    if jwk == nil then
        return nil, key_error
    end

    if jwk.alg ~= nil and jwk.alg ~= header.alg then
        log_warn("jwk_alg_mismatch", { request_id = request_id, kid = header.kid })

        return nil, gateway_error(401, "invalid_token", "JWT signing key is invalid.")
    end

    if not verify_rs256_signature(signing_input, signature, jwk, request_id) then
        log_warn("signature_invalid", { request_id = request_id, kid = header.kid })

        return nil, gateway_error(401, "invalid_token", "JWT signature is invalid.")
    end

    if payload.iss ~= options.issuer then
        log_warn("issuer_mismatch", { request_id = request_id, kid = header.kid })

        return nil, gateway_error(401, "invalid_token", "JWT issuer is invalid.")
    end

    local audience_ok, matched_audience = audience_matches(payload.aud, options.audience)

    if not audience_ok then
        log_warn("audience_mismatch", { request_id = request_id, kid = header.kid })

        return nil, gateway_error(401, "audience_mismatch", "JWT audience is invalid.")
    end

    if not require_claims(payload) then
        log_warn("claims_invalid", { request_id = request_id, kid = header.kid })

        return nil, gateway_error(401, "invalid_token", "JWT claims are invalid.")
    end

    local now = ngx.time()
    local skew = tonumber(options.clock_skew_seconds) or 0

    if payload.exp ~= nil and payload.exp <= now - skew then
        log_warn("token_expired", { request_id = request_id, kid = header.kid, jti = payload.jti })

        return nil, gateway_error(401, "expired_token", "JWT is expired.")
    end

    if payload.nbf > now + skew then
        log_warn("token_not_before", { request_id = request_id, kid = header.kid, jti = payload.jti })

        return nil, gateway_error(401, "invalid_token", "JWT is not valid yet.")
    end

    payload.idshka_audience = matched_audience

    log_debug("completed", {
        request_id = request_id,
        kid = header.kid,
        user_id = payload.sub,
        site_id = payload.site_id,
        audience = options.audience,
        jti = payload.jti,
    })

    return payload, nil
end

function _M.reject(error_response, request_id)
    local response = error_response or gateway_error(401, "invalid_token", "JWT is invalid.")

    ngx.status = response.status
    ngx.header.content_type = "application/json"
    ngx.say(cjson.encode({
        error = response.error,
        message = response.message,
        request_id = request_id,
    }))

    return ngx.exit(response.status)
end

return _M
