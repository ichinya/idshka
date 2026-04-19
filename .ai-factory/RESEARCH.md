# RESEARCH

## Принятые решения
- Основной стек: Laravel + Socialite.
- `idshka.ru` — Laravel modular monolith, а не TypeScript/Node monorepo.
- Socialite используется как OAuth client для входа через внешние провайдеры.
- Provider/issuer часть для подключённых сайтов реализуется отдельно: `/oauth/authorize`, `/oauth/token`, `/oauth/jwks.json`.
- API-only режим для `apishka.ru` проверяется на edge через OpenResty/Nginx.

## Открытые вопросы
- Выбрать конкретную PHP JWT/JWK библиотеку для MVP.
- Решить, нужен ли refresh token в первом релизе или достаточно short-lived API JWT.
- Выбрать UI-подход: Blade-only, Livewire или Inertia.
- Выбрать exact policy для auto-link Socialite accounts по email.
