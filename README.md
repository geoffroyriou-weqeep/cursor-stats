# Cursor Stats

Dashboard personnel pour visualiser votre consommation de tokens Cursor (input, output, cache read) et le **Montant réel** sur une période choisie.

**Usage local uniquement** — cette application est conçue pour tourner sur votre machine (Laravel Herd). Elle ne doit pas être déployée en production : vos identifiants de session Cursor restent sur votre poste.

## Prérequis

- [Laravel Herd](https://herd.laravel.com/) (macOS recommandé)
- Cursor installé et connecté (lecture automatique du token via SQLite)
- PHP 8.2+, Composer, Node.js (pour Vite/Tailwind)

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build
```

Servez le projet via Herd (répertoire du projet lié) ou `php artisan serve`, puis ouvrez l’URL locale dans le navigateur.

## Configuration

Variables dans `.env` :

| Variable | Description |
|----------|-------------|
| `CURSOR_STATS_TIMEZONE` | Fuseau pour les minuits des périodes (défaut : `Europe/Paris`) |
| `CURSOR_SESSION_COOKIE` | Cookie `WorkosCursorSessionToken` en secours si la lecture SQLite échoue |
| `CURSOR_STATS_SQLITE_PATH` | (optionnel) Chemin vers `state.vscdb` de Cursor |

Exemple :

```env
CURSOR_STATS_TIMEZONE=Europe/Paris
CURSOR_SESSION_COOKIE=
```

Sans cookie configuré, l’app tente de lire le token depuis la base SQLite de Cursor. En cas d’échec, une page d’erreur explique comment corriger la session.

## Utilisation

- Ouvrez `/` : la **Daily View** (aujourd’hui) s’affiche par défaut.
- Presets : Aujourd’hui, Hier, 7 derniers jours.
- Plage personnalisée : dates « Du » / « Au » puis Appliquer.
- Rechargez la page pour actualiser les données (pas de job ni polling).

## Documentation projet

- Glossaire et vocabulaire : [`CONTEXT.md`](CONTEXT.md)
- PRD : [`docs/prd/cursor-stats-dashboard.md`](docs/prd/cursor-stats-dashboard.md)
- Décision API dashboard : [`docs/adr/0001-usage-via-dashboard-api.md`](docs/adr/0001-usage-via-dashboard-api.md)

## Tests

```bash
php artisan test
```
