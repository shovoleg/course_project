# Course Position Import — Odoo 17

External Odoo 17 app for `course_project`. Imports aggregated CV results from Position via API token.

## Install
- `docker compose -f docker-compose.odoo.yaml up -d`
- Apps → Update Apps List → `Course Position Import` → Activate

## Use
- Course Project: `Positions/{id}/api-token` → Copy `Token` (64 hex)
- Odoo: `Course Import` → `Import by Token` → paste token, Base URL `https://courseproject.odotibmebel.synology.me` → Import
- List `Imported Positions` shows `position.title`, `attributes[]` (type), `avg/min/max` for numeric, `popular` for text

## Stack
Odoo 17.0, Python 3.11, PostgreSQL 16
