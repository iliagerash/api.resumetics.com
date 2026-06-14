# api.resumetics.com

Laravel 13 API that receives inbound email webhooks from Resend, resolves the real recipient email via the originating job board's API, and forwards the email to that address — preserving sender, subject, body, and attachments.

## How it works

Resend delivers inbound emails to `POST /api/webhook/resend`. The recipient address encodes the site and user:

```
s123u456@resumetics.com
 ^^^  ^^^
 site  user
```

The controller parses the address, creates a log record, and dispatches `RouteInboundEmailJob` to the queue. The job looks up the site's API to resolve the user's real email, then forwards the email via Resend with `reply_to` set to the original sender so the recipient can reply directly to the recruiter.

Static addresses (e.g. `jobs@resumetics.com`) are matched against the `static_routes` table and forwarded without any lookup.

## Stack

- **Laravel 13** — API only, no views or sessions
- **PostgreSQL** — persistence and queue storage
- **Resend** — inbound webhook + outbound forwarding
- **PHP 8.3**

## Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Fill in `.env`:

```env
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

RESEND_API_KEY=
RESEND_WEBHOOK_SECRET=
RESEND_FROM_DOMAIN=resumetics.com
```

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Start the queue worker

**Development:**
```bash
php artisan queue:work --queue=email-routing
```

**Production (PM2):**
```bash
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup  # run the printed command to persist across reboots
```

## Adding a site

Sites are managed directly in the database. Each site needs a `site_id` (the number in `sXXX`), the base URL of its user lookup API, and a bearer token:

```bash
php artisan tinker
```

```php
App\Models\Site::create([
    'site_id'  => 123,
    'api_url'  => 'https://jobboard.example.com/api',
    'api_key'  => 'secret-token',
    'active'   => true,
]);
```

The job will call `GET {api_url}/users/{user_id}/email` with `Authorization: Bearer {api_key}` and expect:

```json
{ "email": "user@example.com" }
```

If the response shape differs, set `response_path` to a dot-notation path (e.g. `data.user.email`).

## Adding a static route

```php
App\Models\StaticRoute::create([
    'recipient'  => 'jobs@resumetics.com',
    'forward_to' => 'team@example.com',
]);
```

## Webhook signature verification

Set `RESEND_WEBHOOK_SECRET` to the `whsec_...` value from your Resend dashboard. The controller verifies the `svix-signature` header on every request. If the env var is empty, verification is skipped (useful for local testing).

## Monitoring

Check routing logs directly in the database:

```bash
php artisan tinker
```

```php
// Recent failures
App\Models\EmailRoutingLog::where('status', 'failed')->latest()->get();

// All logs for a site
App\Models\EmailRoutingLog::where('site_id', 123)->latest()->get();
```

PM2 process status:
```bash
pm2 status
pm2 logs resumetics-queue
```

## File structure

```
app/
  Http/Controllers/
    ResendWebhookController.php   # webhook entry point + signature verification
  Jobs/
    RouteInboundEmailJob.php      # resolves user email and forwards
    ForwardStaticEmailJob.php     # forwards static-route emails
  Models/
    Site.php
    StaticRoute.php
    EmailRoutingLog.php
  Services/
    UserEmailResolverService.php  # HTTP call to site's user API
    EmailForwarderService.php     # Resend outbound send
database/
  migrations/
    *_create_sites_table.php
    *_create_static_routes_table.php
    *_create_email_routing_logs_table.php
routes/
  api.php
ecosystem.config.cjs              # PM2 worker config
```
