# Steno Web

Web application and API backend for [Steno](https://github.com/msuemnig/steno), a browser extension that records and replays form fills. Steno Web adds cloud sync, user authentication, team management, and subscription billing.

## Tech stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** React 19 via Inertia.js v2 (SSR)
- **CSS:** Tailwind CSS v4
- **Auth:** Laravel Fortify (email/password + MFA) + Socialite (Google SSO) + Sanctum (API tokens)
- **Billing:** Laravel Cashier (Stripe) — Team is the billable entity
- **Database:** MySQL 8.0+
- **Testing:** PHPUnit 11, ~180 tests

## Features

- **Authentication** — Email/password registration, Google SSO, TOTP two-factor, email verification
- **Extension auth** — Token bridge via `postMessage` so the browser extension authenticates without a full OAuth flow
- **Team management** — Create teams, invite members, role-based access (owner, admin, editor, viewer)
- **Script library** — Web dashboard to browse and manage recorded scripts
- **Cloud sync** — Bidirectional sync with last-write-wins conflict resolution and soft-delete propagation
- **Subscription billing** — Three tiers via Stripe:

| Plan | Price | Scripts | Members | Export/Import |
|------|-------|---------|---------|---------------|
| Free | $0 | 2 | 1 | No |
| Individual | $50/yr | Unlimited | 1 | Yes |
| Business | $250/yr | Unlimited | 10 | Yes |

## Issues & contributing

Bug reports, feature requests, and tasks are tracked as GitHub issues:

**https://github.com/msuemnig/steno-web/issues**

---

## Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`
- Composer
- Node.js 18+ and npm
- MySQL 8.0+
- [Laravel Herd](https://herd.laravel.com/) (recommended for local dev — see note below)
- A Stripe account (for billing features — optional)
- A Google Cloud project with OAuth credentials (for Google SSO — optional)

> **Local dev:** This project uses **Laravel Herd** for local PHP serving. Do not use `php artisan serve`. Herd serves the app automatically at `https://steno-web.test`.

## Setup

### 1. Clone and install dependencies

```bash
git clone https://github.com/msuemnig/steno-web.git
cd steno-web
composer install
npm install
```

### 2. Environment configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and fill in your values:

```env
APP_NAME=Steno
APP_URL=https://steno-web.test

# Database
DB_DATABASE=steno
DB_USERNAME=root
DB_PASSWORD=your_password

# Google SSO (optional)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=https://steno-web.test/auth/google/callback

# Stripe billing (optional — app works without it, billing features unavailable)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_INDIVIDUAL_YEARLY_PRICE_ID=price_...
STRIPE_BUSINESS_YEARLY_PRICE_ID=price_...
```

### 3. Create the database and run migrations

```bash
mysql -u root -p -e "CREATE DATABASE steno"
php artisan migrate
```

### 4. Start Vite (Herd handles PHP)

```bash
npm run dev
```

The app will be at `https://steno-web.test`.

---

## Project structure

```
steno-web/
├── app/
│   ├── Actions/Fortify/           # Auth action classes (register, update profile, etc.)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/               # SyncController, ApiScriptController, ApiSiteController,
│   │   │   │                      # ApiPersonaController, ApiUserController
│   │   │   ├── Auth/              # ExtensionAuthController, GoogleController
│   │   │   ├── BillingController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── TeamController.php
│   │   │   ├── TeamInvitationController.php
│   │   │   └── TeamMemberController.php
│   │   ├── Middleware/
│   │   │   ├── EnsureActiveSubscription.php  # Guards POST /api/sync — requires subscription
│   │   │   └── HandleInertiaRequests.php
│   │   └── Resources/             # ScriptResource, SiteResource, PersonaResource
│   ├── Models/                    # User, Team, Script, Site, Persona, TeamInvitation
│   ├── Policies/                  # ScriptPolicy, SitePolicy, PersonaPolicy
│   └── Providers/                 # AppServiceProvider, FortifyServiceProvider
├── config/
│   ├── steno.php                  # Plan definitions and free-tier limits
│   ├── fortify.php                # Auth features config
│   └── sanctum.php                # Stateful domains, token expiry
├── database/
│   ├── migrations/                # 14 migrations
│   └── factories/                 # 5 model factories (for tests)
├── resources/js/
│   ├── Pages/                     # React pages (Auth, Dashboard, Teams, Settings, Billing, etc.)
│   ├── Layouts/                   # AppLayout, GuestLayout
│   └── Components/                # InputField, Button
├── routes/
│   ├── web.php                    # 23 web routes
│   └── api.php                    # 16 API endpoints
└── tests/
    ├── Feature/                   # Api, Auth, Team, Billing
    └── Unit/                      # Models, Policies
```

---

## Architecture

### Request flow

```
Browser/Extension
    │
    ├── Web requests  → Inertia.js → React pages (SSR) → AppLayout / GuestLayout
    │
    └── API requests  → Sanctum Bearer token → api.php routes → Api controllers → JSON responses
```

### Inertia.js

Pages are React components rendered server-side via Inertia. There is no separate SPA build step — the Blade template (`resources/views/app.blade.php`) bootstraps Inertia, which hydrates on the client. Vite handles HMR in development.

### Team as billable entity

Laravel Cashier is configured to bill `Team`, not `User`. Every user gets an auto-created personal team (free plan) on registration. When subscribing, the team gains a Stripe subscription. The `current_team_id` on `User` determines which team's subscription is active for that session.

---

## Data model

### Schema

| Table | PK type | Soft deletes | Key columns |
|-------|---------|--------------|-------------|
| `users` | bigint auto | No | name, email, password (nullable), google_id, current_team_id, 2FA columns |
| `teams` | bigint auto | No | owner_id, name, slug (unique), plan_type, stripe_id (Cashier columns) |
| `team_user` | pivot | No | team_id, user_id, role |
| `team_invitations` | bigint auto | No | team_id, email, role, token (64-char unique) |
| `sites` | UUID | Yes | team_id, user_id, hostname, label |
| `personas` | UUID | Yes | site_id (nullable), team_id, user_id, name |
| `scripts` | UUID | Yes | site_id (nullable), persona_id (nullable), team_id, user_id, name, fields (JSON), url_hint, version |
| `subscriptions` | bigint auto | No | team_id (not user_id), type, stripe_id, status |

> **UUID PKs** on sites, personas, and scripts are client-generated in the extension for offline-first operation. They arrive in the sync payload and are stored as-is.

### Script fields (JSON column)

The `fields` column stores an array of steps:

```json
[
  { "order": 1, "action": "fill", "selector": "#email", "value": "test@example.com", "type": "text", "label": "Email", "fragile": false },
  { "order": 2, "action": "click", "selector": "button[type='submit']", "label": "Submit", "fragile": false }
]
```

### Soft deletes

Scripts, Sites, and Personas use Laravel's `SoftDeletes`. The sync endpoint uses `withTrashed()` so soft-deleted records are included in responses — this is how deletion is propagated to other devices.

---

## Roles & permissions

| Action | owner | admin | editor | viewer |
|--------|-------|-------|--------|--------|
| View scripts/sites/personas | ✓ | ✓ | ✓ | ✓ |
| Create/update sites & personas | ✓ | ✓ | ✓ | — |
| Update own scripts | ✓ | ✓ | ✓ | — |
| Update any script | ✓ | ✓ | — | — |
| Delete sites & personas | ✓ | ✓ | — | — |
| Delete scripts | ✓ | ✓ | ✓ (own) | — |
| Manage team members | ✓ | ✓ | — | — |
| Send invitations | ✓ | ✓ | — | — |
| Delete team | ✓ | — | — | — |
| Billing | ✓ | — | — | — |

Roles are enforced in `app/Policies/` (Script, Site, Persona) and inline in team controllers.

---

## API endpoints

All API routes require a Sanctum Bearer token: `Authorization: Bearer <token>`.

### Data endpoints

| Method | Endpoint | Description | Notes |
|--------|----------|-------------|-------|
| GET | `/api/user` | Current user + team + subscription status | |
| GET | `/api/scripts` | List team scripts | |
| POST | `/api/scripts` | Create script | Free tier: max 2 scripts |
| GET | `/api/scripts/{id}` | Show script | |
| PUT | `/api/scripts/{id}` | Update script | Policy: editors can only edit own |
| DELETE | `/api/scripts/{id}` | Soft-delete script | |
| GET | `/api/sites` | List team sites | |
| POST | `/api/sites` | Create site | |
| GET | `/api/sites/{id}` | Show site | |
| PUT | `/api/sites/{id}` | Update site | |
| DELETE | `/api/sites/{id}` | Soft-delete site | Owners/admins only |
| GET | `/api/personas` | List team personas | |
| POST | `/api/personas` | Create persona | |
| GET | `/api/personas/{id}` | Show persona | |
| PUT | `/api/personas/{id}` | Update persona | |
| DELETE | `/api/personas/{id}` | Soft-delete persona | |
| POST | `/api/sync` | Bidirectional sync | **Requires active subscription** |

### Sync endpoint

`POST /api/sync` — the primary endpoint used by the extension.

**Request payload:**
```json
{
  "last_synced_at": "2026-03-01T12:00:00Z",
  "sites":    [{ "id": "uuid", "hostname": "...", "label": "...", "updated_at": "...", "deleted_at": null }],
  "personas": [{ "id": "uuid", "site_id": "uuid", "name": "...", "updated_at": "...", "deleted_at": null }],
  "scripts":  [{ "id": "uuid", "site_id": "uuid", "persona_id": null, "name": "...", "fields": [...], "updated_at": "...", "deleted_at": null }]
}
```

**Response:**
```json
{
  "synced_at": "2026-03-17T10:00:00Z",
  "sites":    [...],
  "personas": [...],
  "scripts":  [...]
}
```

**Conflict resolution:** Last-write-wins using `updated_at`. For each incoming item:
- If server has a newer `updated_at` → server wins, client item is ignored
- If client has a newer `updated_at` → client wins, server is updated
- Soft-deleted items are returned with `deleted_at` set so the client can remove them

The response only includes items modified since `last_synced_at`. On first sync (`last_synced_at = null`), all team data is returned.

### `/api/user` response shape

```json
{
  "id": 1,
  "name": "Jane Smith",
  "email": "jane@example.com",
  "current_team": {
    "id": 1,
    "name": "Jane's Team",
    "subscribed": true,
    "plan": "individual"
  }
}
```

The extension checks `current_team.subscribed` to determine free vs paid status.

---

## Web routes

| Method | Path | Description | Auth required |
|--------|------|-------------|--------------|
| GET | `/` | Welcome/landing page | No |
| GET | `/pricing` | Pricing table | No |
| GET | `/auth/google` | Google OAuth redirect | No |
| GET | `/auth/google/callback` | Google OAuth callback | No |
| GET | `/team-invitations/{token}/accept` | Accept team invite | No (auto-login) |
| GET | `/auth/extension-login` | Extension login page — generates token, sends via postMessage | Yes + verified |
| POST | `/auth/extension-token` | Generate Sanctum token (JSON) | Yes + verified |
| GET | `/dashboard` | Main dashboard | Yes + verified |
| GET | `/settings` | User settings & API tokens | Yes + verified |
| GET/POST | `/teams` | List / create teams | Yes + verified |
| GET/PUT/DELETE | `/teams/{team}` | Show / update / delete team | Yes + verified |
| PUT | `/teams/{team}/switch` | Switch active team | Yes + verified |
| DELETE | `/teams/{team}/members/{user}` | Remove team member | Yes + verified |
| PUT | `/teams/{team}/members/{user}` | Update member role | Yes + verified |
| POST | `/teams/{team}/invitations` | Send invitation | Yes + verified |
| DELETE | `/team-invitations/{invitation}` | Cancel invitation | Yes + verified |
| GET | `/billing` | Billing page | Yes + verified |
| POST | `/billing/subscribe` | Start subscription | Yes + verified |
| POST | `/billing/change-plan` | Swap Stripe price | Yes + verified |
| POST | `/billing/cancel` | Cancel subscription | Yes + verified |
| POST | `/billing/resume` | Resume cancelled sub | Yes + verified |
| GET | `/billing/portal` | Redirect to Stripe portal | Yes + verified |

---

## Extension integration

The Steno browser extension authenticates via a postMessage token bridge:

1. User clicks **"Login to sync"** in the extension popup
2. Extension opens `https://steno-web.test/auth/extension-login` in a new tab
3. User authenticates (email/password or Google SSO)
4. The `ExtensionAuthController` generates a named Sanctum token (`steno-extension`) and the Inertia page sends it via `window.postMessage({ type: 'STENO_AUTH_TOKEN', token, user })`
5. The extension content script (only active on `steno-web.test` and `localhost`) receives the message and forwards it to the background service worker via `chrome.runtime.sendMessage({ type: 'SET_AUTH_TOKEN', token, user })`
6. Background stores the token in `chrome.storage.local` as `steno_api_token`
7. An initial sync runs immediately after login
8. A `chrome.alarms` alarm fires every **5 minutes** for background sync

The companion extension repo is at: **https://github.com/msuemnig/steno**

---

## Plans & free tier

Plan definitions live in `config/steno.php`:

```php
'plans' => [
    'individual' => [
        'price_yearly' => env('STRIPE_INDIVIDUAL_YEARLY_PRICE_ID'),
        'amount' => 5000,      // $50/yr in cents
        'max_members' => 1,
        'max_scripts' => null, // unlimited
        'can_export' => true,
    ],
    'business' => [
        'price_yearly' => env('STRIPE_BUSINESS_YEARLY_PRICE_ID'),
        'amount' => 25000,     // $250/yr in cents
        'max_members' => 10,
        'max_scripts' => null,
        'can_export' => true,
    ],
],
'free_tier' => [
    'max_scripts' => 2,
    'can_export' => false,
]
```

Free-tier enforcement:
- `POST /api/scripts` checks `$team->subscribed('default')` — returns 403 if over limit
- `POST /api/sync` is guarded by `EnsureActiveSubscription` middleware — returns 403 with `"Active subscription required"` if not subscribed
- Team invitation limits are enforced in `TeamInvitationController` using `config('steno.plans.*.max_members')`

---

## Testing

### PHP tests (~180 tests)

```bash
# Create test database first
mysql -u root -p -e "CREATE DATABASE steno_test"

php artisan test
```

Test suites:

| Suite | Files | Coverage |
|-------|-------|----------|
| Feature/Api | ScriptApiTest, SiteApiTest, PersonaApiTest, SyncApiTest, UserApiTest | CRUD, auth, free-tier limits, sync conflict resolution |
| Feature/Auth | LoginTest, RegistrationTest, ExtensionAuthTest | Email/password, Google SSO, token generation |
| Feature/Team | TeamManagementTest, TeamMemberTest, TeamInvitationTest | CRUD, roles, invitations, member limits |
| Feature/Billing | SubscriptionTest | Plan changes, cancellation, resumption |
| Unit/Models | ScriptTest, TeamTest, UserTest | Relationships, UUID keys, soft deletes |
| Unit/Policies | ScriptPolicyTest, SitePolicyTest, PersonaPolicyTest | Role-based authorization |

> 4 billing tests that hit the real Stripe API are skipped unless `STRIPE_SECRET` is set to a live/test key.

### Frontend

No automated frontend tests currently. The companion extension repo has 125+ Vitest tests.

---

## Migrations

| Migration | Purpose |
|-----------|---------|
| `000000_create_users_table` | users, password_reset_tokens, sessions |
| `000001_create_cache_table` | cache |
| `000002_create_jobs_table` | jobs queue |
| `add_two_factor_columns_to_users` | Fortify 2FA columns |
| `create_personal_access_tokens` | Sanctum tokens |
| `add_google_id_current_team_to_users` | Google SSO, team switching |
| `create_teams_table` | teams, team_user pivot, team_invitations |
| `create_sites_table` | UUID PK, soft deletes, team_id + hostname index |
| `create_personas_table` | UUID PK, soft deletes, nullable site_id |
| `create_scripts_table` | UUID PK, soft deletes, JSON fields, team_id + updated_at index |
| `add_stripe_columns_to_teams` | Cashier billing columns on teams |
| `create_subscriptions_table` | Cashier subscriptions (references team_id) |
| `create_subscription_items_table` | Cashier subscription items |
| `add_meter_columns_to_subscription_items` | Cashier usage metering |

---

## License

MIT
