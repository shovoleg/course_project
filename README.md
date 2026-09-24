# Course Project — CV / Positions

Symfony 7.4 + MySQL 8.4.4 + Docker (`webdevops/php-nginx-dev:8.4`). Разворачивается на Synology DS923+ через Container Manager, данные — в томе `db-data`.

- Демо: `https://courseproject.odotibmebel.synology.me` (HTTPS через Synology Reverse Proxy + Let's Encrypt)
- Локально: `http://NAS-IP:8010` → `/health` = `ok`

## Быстрый старт

1. **На NAS** положить рядом `docker-compose.yaml` и папку `app` (см. `razvernut.md` — 5 минут).
2. `docker compose up -d` → выполнить миграции `php bin/console doctrine:migrations:migrate`.
3. Без ключей уже работают регистрация по почте, атрибуты, позиции, поиск.

Ключи (OAuth, загрузка фото) — в `app/.env` на NAS, в репозитории они пустые:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
IMGBB_API_KEY=
DEFAULT_URI=https://courseproject.odotibmebel.synology.me
```

Инструкция целиком — `zapusk-na-synology.md` (обратный прокси, Google Auth Platform → Clients, GitHub OAuth Apps, ImgBB).

## Что внутри

- `app/` — Symfony-приложение (PHP 8.4, Twig, Stimulus, AssetMapper)
- `docker-compose.yaml` — `app` + `database` (MySQL 8.4.4)
- `nginx/zz-fastcgi-buffers.conf` — фикс 502 на `webdevops`

## Безопасность

В репозитории нет секретов. Перед публикацией смените `APP_SECRET` в `app/.env` и не коммитьте `app/.env.local`.
