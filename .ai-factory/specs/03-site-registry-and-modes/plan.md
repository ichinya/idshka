# 03-site-registry-and-modes

## Р¦РµР»СЊ
РЎРґРµР»Р°С‚СЊ РїРѕРґРєР»СЋС‡РµРЅРёРµ СЃР°Р№С‚РѕРІ, РІРµСЂРёС„РёРєР°С†РёСЋ РґРѕРјРµРЅР° Рё СЏРІРЅРѕРµ РІРєР»СЋС‡РµРЅРёРµ СЂРµР¶РёРјРѕРІ `api_resource`/`web_client`
СЃ fail-closed РїРѕРІРµРґРµРЅРёРµРј РґР»СЏ РЅРµРІР°Р»РёРґРЅС‹С… РёР»Рё РЅРµ РїРѕРґС‚РІРµСЂР¶РґРµРЅРЅС‹С… РґРѕРјРµРЅРѕРІ.

## Area
`site_registry`, `laravel`, `portal`

## Р—Р°РІРёСЃРёРјРѕСЃС‚Рё
- `02-user-auth-socialite`

## Refined Checklist
- [x] **T1. РњРёРіСЂР°С†РёРё Рё РёРЅРІР°СЂРёР°РЅС‚С‹ РґРѕРјРµРЅР° Sites**
  - Р”РѕР±Р°РІРёС‚СЊ migrations РґР»СЏ `sites`, `site_verifications`, `site_modes`.
  - Р—Р°С„РёРєСЃРёСЂРѕРІР°С‚СЊ РёРЅРІР°СЂРёР°РЅС‚С‹:
    - `sites.owner_user_id + normalized_domain` unique;
    - verified domain РЅРµ РјРѕР¶РµС‚ Р±С‹С‚СЊ РїСЂРёРІСЏР·Р°РЅ Рє РґРІСѓРј РІР»Р°РґРµР»СЊС†Р°Рј;
    - verification challenge С…СЂР°РЅРёС‚ `token`, `method`, `expires_at`, `status`.
  - Р”РѕР±Р°РІРёС‚СЊ РёРЅРґРµРєСЃС‹ РїРѕ `normalized_domain`, `site_id`, `status`, `expires_at`.

- [x] **T2. Site РјРѕРґРµР»Рё Рё СЃР»РѕР№ РЅРѕСЂРјР°Р»РёР·Р°С†РёРё РґРѕРјРµРЅР°**
  - Р”РѕР±Р°РІРёС‚СЊ РјРѕРґРµР»Рё/enum/value objects РІ `App\Domain\Sites\*`:
    - РЅРѕСЂРјР°Р»РёР·Р°С†РёСЏ РґРѕРјРµРЅР° (lowercase + punycode + СѓРґР°Р»РµРЅРёРµ scheme/path/query);
    - СЃС‚Р°С‚СѓСЃ РІРµСЂРёС„РёРєР°С†РёРё (`pending`, `verified`, `failed`, `expired`);
    - СЂРµР¶РёРјС‹ СЃР°Р№С‚Р° (`api_resource`, `web_client`) С‡РµСЂРµР· explicit allow-list.
  - РџРѕРґРіРѕС‚РѕРІРёС‚СЊ СЃРµСЂРІРёСЃ РІС‹РґР°С‡Рё verification instructions РґР»СЏ DNS TXT Рё `/.well-known/`.

- [x] **T3. POST /api/v1/sites (create site + challenge)**
  - Р”РѕР±Р°РІРёС‚СЊ route Рё РєРѕРЅС‚СЂРѕР»Р»РµСЂ РґР»СЏ `POST /api/v1/sites`.
  - Р”РѕР±Р°РІРёС‚СЊ request validation (`domain`, `display_name`) + owner authorization.
  - Р’РѕР·РІСЂР°С‰Р°С‚СЊ shape РёР· `docs/API_FLOWS.md`:
    - `site_id`, `domain`, `verified=false`, `verification.dns_txt_*`, `verification.file_*`.
  - Р›РѕРіРёСЂРѕРІР°С‚СЊ СЃРѕР±С‹С‚РёРµ connect Р±РµР· СѓС‚РµС‡РєРё СЃРµРєСЂРµС‚РѕРІ challenge.
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T1`, `T2`.

- [x] **T4. POST /api/v1/sites/{site}/verify (dns_txt/file)**
  - Р”РѕР±Р°РІРёС‚СЊ route, request, controller + owner policy.
  - РџРѕРґРґРµСЂР¶Р°С‚СЊ РјРµС‚РѕРґС‹ `dns_txt` Рё `file` СЃ РѕРґРёРЅР°РєРѕРІС‹Рј РєРѕРЅС‚СЂР°РєС‚РѕРј РѕС€РёР±РєРё.
  - РџСЂРѕРІРµСЂРёС‚СЊ TTL challenge Рё РєРѕСЂСЂРµРєС‚РЅРѕ РїРµСЂРµРІРѕРґРёС‚СЊ status (`verified`/`failed`/`expired`).
  - РџРёСЃР°С‚СЊ audit/event Рѕ СЂРµР·СѓР»СЊС‚Р°С‚Рµ verify.
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T1`, `T2`, `T3`.

- [x] **T5. РџСЂРѕРІРµСЂРєР° DNS TXT**
  - Р РµР°Р»РёР·РѕРІР°С‚СЊ DNS checker service СЃ timeout/retry policy Рё СЏРІРЅС‹Рј error mapping.
  - РЎСЂР°РІРЅРёРІР°С‚СЊ С‚РѕР»СЊРєРѕ РѕР¶РёРґР°РµРјРѕРµ Р·РЅР°С‡РµРЅРёРµ `idshka-site-verification=<token>`.
  - РџСЂРё СЃРµС‚РµРІС‹С… РѕС€РёР±РєР°С… РІРѕР·РІСЂР°С‰Р°С‚СЊ deterministic domain error (Р±РµР· raw stack).
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T4`.

- [x] **T6. РџСЂРѕРІРµСЂРєР° `/.well-known` С„Р°Р№Р»Р°**
  - Р РµР°Р»РёР·РѕРІР°С‚СЊ HTTP checker service РґР»СЏ
    `https://<domain>/.well-known/idshka-site-verification.txt`.
  - РџСЂРѕРІРµСЂСЏС‚СЊ status code, content-type tolerance Рё exact body token match.
  - Р‘Р»РѕРєРёСЂРѕРІР°С‚СЊ redirect РЅР° РїРѕСЃС‚РѕСЂРѕРЅРЅРёРµ host/scheme.
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T4`.

- [x] **T7. Endpoints РІРєР»СЋС‡РµРЅРёСЏ СЂРµР¶РёРјРѕРІ СЃР°Р№С‚Р°**
  - Р”РѕР±Р°РІРёС‚СЊ endpoints РІРєР»СЋС‡РµРЅРёСЏ `api_resource` Рё `web_client`.
  - Р Р°Р·СЂРµС€Р°С‚СЊ РІРєР»СЋС‡РµРЅРёРµ С‚РѕР»СЊРєРѕ РґР»СЏ `verified` СЃР°Р№С‚Р°.
  - РџРѕРІС‚РѕСЂРЅРѕРµ РІРєР»СЋС‡РµРЅРёРµ С‚РѕРіРѕ Р¶Рµ СЂРµР¶РёРјР° РґРѕР»Р¶РЅРѕ Р±С‹С‚СЊ idempotent.
  - РџРѕРґРіРѕС‚РѕРІРёС‚СЊ РјРµР¶РјРѕРґСѓР»СЊРЅС‹Р№ РєРѕРЅС‚СЂР°РєС‚ (lookup verified site) РґР»СЏ РїР»Р°РЅРѕРІ `04` Рё `06`.
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T4`, `T5`, `T6`.

- [x] **T8. Fail-closed guard РґР»СЏ production credentials**
  - Р”РѕР±Р°РІРёС‚СЊ С†РµРЅС‚СЂР°Р»РёР·РѕРІР°РЅРЅСѓСЋ РїСЂРѕРІРµСЂРєСѓ: unverified site РЅРµ РјРѕР¶РµС‚ РїРѕР»СѓС‡Р°С‚СЊ production credentials.
  - Р—Р°С„РёРєСЃРёСЂРѕРІР°С‚СЊ РїСЂР°РІРёР»Рѕ РІ domain service/contract, С‡С‚РѕР±С‹ СЃР»РµРґСѓСЋС‰РёР№ issuer/web-client flow
    РЅРµ РѕР±С…РѕРґРёР» РѕРіСЂР°РЅРёС‡РµРЅРёРµ С‡РµСЂРµР· РєРѕРЅС‚СЂРѕР»Р»РµСЂС‹.
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T7`.

- [x] **T9. API/contract РґРѕРєСѓРјРµРЅС‚Р°С†РёСЏ**
  - РћР±РЅРѕРІРёС‚СЊ `docs/API_FLOWS.md` РїРѕ С„Р°РєС‚РёС‡РµСЃРєРёРј request/response/error payload.
  - РџСЂРё РёР·РјРµРЅРµРЅРёРё РїСѓР±Р»РёС‡РЅРѕРіРѕ РєРѕРЅС‚СЂР°РєС‚Р° СЃРёРЅС…СЂРѕРЅРёР·РёСЂРѕРІР°С‚СЊ СЂРµР»РµРІР°РЅС‚РЅС‹Рµ sections РІ docs.
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T3`, `T4`, `T7`, `T8`.

- [x] **T10. Feature tests (РєРѕРЅС‚СЂР°РєС‚ + security)**
  - Р”РѕР±Р°РІРёС‚СЊ feature tests РґР»СЏ:
    - create site;
    - verify via dns/file;
    - mode enable РїРѕСЃР»Рµ verify;
    - Р·Р°РїСЂРµС‚ production credentials РґР»СЏ unverified site;
    - Р·Р°РїСЂРµС‚ cross-owner access Рє С‡СѓР¶РѕРјСѓ site.
  - РџРѕРєСЂС‹С‚СЊ deterministic HTTP РєРѕРґС‹ (`401`/`403`/`422`) РїРѕ РїСЂР°РІРёР»Р°Рј РїСЂРѕРµРєС‚Р°.
  - Р—Р°РІРёСЃРёС‚ РѕС‚: `T3`, `T4`, `T5`, `T6`, `T7`, `T8`.

## Acceptance criteria
- [ ] РјРѕР¶РЅРѕ СЃРѕР·РґР°С‚СЊ site `apishka.ru` Рё РїРѕР»СѓС‡РёС‚СЊ verification instructions (dns/file)
- [x] DNS/file verification РјРµРЅСЏРµС‚ status РЅР° `verified`
- [ ] РјРѕР¶РЅРѕ РІРєР»СЋС‡РёС‚СЊ `api_resource` Рё `web_client` С‚РѕР»СЊРєРѕ РїРѕСЃР»Рµ РІРµСЂРёС„РёРєР°С†РёРё
- [ ] РЅРµРІРµСЂРёС„РёС†РёСЂРѕРІР°РЅРЅС‹Р№ РґРѕРјРµРЅ РЅРµ РїРѕР»СѓС‡Р°РµС‚ production credentials
- [ ] verified domain РЅРµ РјРѕР¶РµС‚ Р±С‹С‚СЊ Р·Р°СЂРµРіРёСЃС‚СЂРёСЂРѕРІР°РЅ РІС‚РѕСЂС‹Рј РІР»Р°РґРµР»СЊС†РµРј Р±РµР· transfer flow
- [ ] API РєРѕРЅС‚СЂР°РєС‚С‹ Рё tests РїРѕРґС‚РІРµСЂР¶РґР°СЋС‚ fail-closed РїРѕРІРµРґРµРЅРёРµ

## РЎРІСЏР·СЊ СЃ roadmap
РЎРј. `.ai-factory/ROADMAP.md`.

