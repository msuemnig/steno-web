# Steno Web

Web application and API backend for [Steno](https://github.com/msuemnig/steno), a browser extension that records and replays form fills. Steno Web adds cloud sync, user authentication, team management, and subscription billing.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** React 19 via Inertia.js
- **CSS:** Tailwind CSS v4
- **Auth:** Laravel Fortify (email/password + MFA) + Socialite (Google SSO) + Sanctum (API tokens)
- **Billing:** Laravel Cashier (Stripe)
- **Database:** MySQL

## Features

- **Authentication** -- Email/password registration, Google SSO, TOTP two-factor authentication, email verification
- **Extension auth** -- Token bridge via `postMessage` so the browser extension can authenticate without a full OAuth flow
- **Team management** -- Create teams, invite members, role-based access (owner, admin, editor, viewer)
- **Script library** -- Web dashboard to browse, search, and manage recorded scripts
- **Cloud sync** -- Bidirectional sync between the extension and server with last-write-wins conflict resolution
- **Subscription billing** -- Three tiers via Stripe:

| Plan | Price | Scripts | Members | Export |
|------|-------|---------|---------|--------|
| Free | $0 | 5 | 1 | No |
| Individual | $50/yr | Unlimited | 1 | Yes |
| Business | $250/yr | Unlimited | 10 | Yes |

## Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`
- Composer
- Node.js 18+ and npm
- MySQL 8.0+
- A Stripe account (for billing features)
- A Google Cloud project (for SSO -- optional)

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
# Database
DB_DATABASE=steno
DB_USERNAME=root
DB_PASSWORD=your_password

# Google SSO (optional)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret

# Stripe billing (optional -- app works without it, billing features will be unavailable)
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

### 4. Start the development servers

If using [Laravel Herd](https://herd.laravel.com/):

```bash
# Herd handles PHP serving automatically -- just start Vite:
npm run dev
```

Otherwise:

```bash
php artisan serve
npm run dev
```

The app will be available at your configured `APP_URL` (e.g. `https://steno-web.test` with Herd).

## Testing

### PHP tests (180 tests)

```bash
# Create the test database first
mysql -u root -p -e "CREATE DATABASE steno_test"

php artisan test
```

Tests cover:
- Authentication (registration, login, extension token, Google OAuth)
- Teams (CRUD, invitations, member management, role enforcement)
- API (scripts, sites, personas CRUD, sync endpoint, free-tier limits)
- Billing (validation, subscription state, plan changes)
- Models (relationships, UUID keys, soft deletes)
- Policies (role-based authorization for all entities)

4 billing tests that hit the Stripe API are skipped unless you set a real `STRIPE_SECRET` in `.env`.

### Frontend

No frontend tests yet. The companion [Steno extension](https://github.com/msuemnig/steno) has 125 Vitest tests covering the StorageService, ApiService, Recorder, and Popup.

## API Endpoints

All API routes require a Sanctum Bearer token (`Authorization: Bearer <token>`).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user` | Current user + team + subscription status |
| GET | `/api/scripts` | List team scripts |
| POST | `/api/scripts` | Create script |
| GET | `/api/scripts/{id}` | Show script |
| PUT | `/api/scripts/{id}` | Update script |
| DELETE | `/api/scripts/{id}` | Delete script (soft) |
| GET | `/api/sites` | List team sites |
| POST | `/api/sites` | Create site |
| GET | `/api/sites/{id}` | Show site |
| PUT | `/api/sites/{id}` | Update site |
| DELETE | `/api/sites/{id}` | Delete site (soft) |
| GET | `/api/personas` | List team personas |
| POST | `/api/personas` | Create persona |
| GET | `/api/personas/{id}` | Show persona |
| PUT | `/api/personas/{id}` | Update persona |
| DELETE | `/api/personas/{id}` | Delete persona (soft) |
| POST | `/api/sync` | Bidirectional sync (requires active subscription) |

## Project Structure

```
app/
  Http/Controllers/
    Api/                  # API controllers (scripts, sites, personas, sync, user)
    Auth/                 # Google OAuth + extension token auth
    BillingController     # Stripe subscription management
    TeamController        # Team CRUD + switching
    TeamMemberController  # Member role management
    TeamInvitationController  # Invite/accept/cancel
  Models/                 # User, Team, Script, Site, Persona, TeamInvitation
  Policies/               # Role-based authorization (Script, Site, Persona)
config/
  steno.php               # Plan definitions and free-tier limits
resources/js/
  Pages/                  # React pages (Auth, Dashboard, Settings, Teams, Billing, Pricing)
  Layouts/                # AppLayout, GuestLayout
  Components/             # InputField, Button
routes/
  web.php                 # Web routes (23 routes)
  api.php                 # API routes (16 endpoints)
tests/
  Feature/                # Auth, API, Team, Billing feature tests
  Unit/                   # Model and policy unit tests
```

## Extension Integration

The Steno browser extension authenticates via a token bridge:

1. User clicks "Login" in the extension popup
2. Extension opens `/auth/extension-login` in a new tab
3. User logs in (email/password or Google SSO)
4. Page generates a Sanctum API token and sends it via `window.postMessage`
5. Extension content script receives the token and stores it locally
6. Background script syncs data every 5 minutes while authenticated

## License

MIT
