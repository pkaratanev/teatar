# Teatar backend

Laravel 13: the JSON API for the app (`/api/v1`, Sanctum tokens), the Filament admin (`/admin`) and the
programme adapters (`app/Adapters`). Setup, commands and the programme data: [../README.md](../README.md).
Project status and rules: [../docs/STATUS.md](../docs/STATUS.md), [../CLAUDE.md](../CLAUDE.md).

| Where | What |
| --- | --- |
| `app/Http/Controllers/Api/V1` | API endpoints (routes in `routes/api.php`); docs at `/docs/api` (Scramble) |
| `app/Filament` | Admin: catalogue, moderation queue, sync log, dashboard widgets |
| `app/Adapters` | One class per theatre site; registered in `config/teatar.php` |
| `app/Services/ProgrammeImporter.php` | Turns adapter rows into productions, stages, performances and posters |
| `app/Console/Commands` | `teatar:demo`, `teatar:sync-programmes`, `teatar:check-sources` |
| `tests/Fixtures` | Saved pages per theatre for the adapter tests |

```bash
composer setup     # first install: dependencies, .env, key, migrate --seed, Filament assets
php artisan test   # Pest; CI runs it on Postgres 17
vendor/bin/pint    # code style
vendor/bin/phpstan # Larastan
```
