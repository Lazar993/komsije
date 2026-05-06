# Upravnik Zgrade

Production-ready SaaS foundation for residential building management built with Laravel 13, PHP 8.4, Sanctum, Filament v4, queues, notifications, and a building-scoped multi-tenant domain model.

## What is implemented

- Building-based multi-tenancy using `building_id` context from `X-Building-Id` or request payload.
- Role-scoped memberships per building with `admin` and `tenant`, plus global `is_super_admin` support.
- Web portal language selection with Serbian as the default and English as an alternate persisted preference.
- Core domain entities: buildings, apartments, users, memberships, apartment occupancy, tickets, ticket comments, ticket status history, announcements, and announcement reads.
- API-first architecture with controllers, form requests, services, repositories, policies, events, listeners, and API resources.
- Filament admin panel for buildings, apartments, tickets, announcements, and users.
- Database and email notifications for ticket and announcement workflows.
- Queue-ready listeners and notifications for Redis-backed async execution.

## Architecture

### Domain model

- `buildings`: tenant root and future billing anchor.
- `apartments`: belong to buildings and include marketplace-ready flags.
- `building_user`: scoped membership table with per-building role.
- `apartment_user`: apartment occupancy mapping for tenants.
- `tickets`: maintenance workflow with comments, attachments, status history, assignment, and priority.
- `announcements`: building-wide communications with read tracking.

### Application layers

- `app/Repositories`: persistence abstraction for domain queries.
- `app/Services`: orchestration and business rules.
- `app/Events` + `app/Listeners`: side effects separated from write flows.
- `app/Http/Requests`: input validation.
- `app/Http/Resources`: consistent mobile-friendly responses.
- `app/Policies`: building-aware authorization.
- `app/Support/Tenancy/TenantContext`: resolved building context for each request.

### Admin panel

Filament resources are generated and then customized so admin writes use the same services and authorization rules as the API. Property managers only see their assigned buildings. Super admins see everything.

## API surface

### Auth

- `POST /api/auth/login`
- `GET /api/auth/me`
- `POST /api/auth/logout`

### Buildings

- `GET /api/buildings`
- `POST /api/buildings`
- `GET /api/buildings/{building}`
- `PUT /api/buildings/{building}`

### Building-scoped endpoints

Supply `building_id` in the request or `X-Building-Id` in the headers.

- `GET /api/apartments`
- `POST /api/apartments`
- `GET /api/apartments/{apartment}`
- `PUT /api/apartments/{apartment}`
- `GET /api/tickets`
- `POST /api/tickets`
- `GET /api/tickets/{ticket}`
- `PUT /api/tickets/{ticket}`
- `POST /api/tickets/{ticket}/comments`
- `GET /api/announcements`
- `POST /api/announcements`
- `GET /api/announcements/{announcement}`
- `PUT /api/announcements/{announcement}`
- `POST /api/announcements/{announcement}/read`

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Recommended production configuration:

- Queue connection: `redis`
- Cache/session: Redis
- Database: MySQL 8 or PostgreSQL
- Mailer: SMTP or API provider
- File storage: S3-compatible storage for ticket attachments

## Demo accounts

After `php artisan migrate --seed`:

- Super Admin: `admin@upravnik.test` / `password`
- Manager: `manager@upravnik.test` / `password`
- Tenant: `tenant@upravnik.test` / `password`

Admin panel:

- `/admin`

## Future-ready extension points

- Apartments already include marketplace-related fields for listing enablement and external references.
- Buildings include a billing customer reference to support future Stripe subscription flows.
- Notifications are already event-driven and queueable, which keeps mobile push support additive rather than invasive.
- The API is decoupled from the admin UI, so mobile apps can consume the same domain services without refactoring.

## Tests

Run the test suite with:

```bash
php artisan test
```
