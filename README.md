# ILITECH TRAINING — Laravel / MySQL

Multi-course training platform: `training.ilitech-engineering.dz/arcgis`, `/gee`, … Each course is a landing page
(AR default, FR/EN) edited from an admin panel, with dated sessions (online / in person), free or paid registration,
SlickPay (CIB/Edahabia), CCP/BaridiMob, and automated email / WhatsApp / SMS.

## Install (fresh Laravel)
```bash
composer create-project laravel/laravel training && cd training
composer require filament/filament:"^3.3"
php artisan filament:install --panels          # panel id: admin
cp -r /path/to/training-app/* .                # overlay: app, config, database, lang, public, resources, routes
cat .env.additions >> .env                     # then fill DB, mail, SlickPay, WAHA, SMS
php artisan migrate --seed                     # seeds the ArcGIS course, a free workshop and 5 automations
php artisan make:filament-user
```
Admin: `/admin` — Courses (landing blocks per language, add-ons, CCP, SEO), Sessions (dates, mode, seats, prices, the
6 weekly classes), Registrations (mark CCP as paid, add on-site registrations, CSV), Automations, Sent messages.

## Production
- Cron: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- Queue worker (Supervisor): `php artisan queue:work --tries=3`
- Nginx root = `public/`, HTTPS, `php artisan config:cache route:cache view:cache`
- WhatsApp: run [WAHA](https://waha.devlike.pro) and scan the QR once. Use a dedicated number and send only to people who registered (consent box is on the form).
- SMS: adapt `Messenger::send()` `'sms'` branch to your Algerian provider's API.

## How things work
- The price is computed on the server; SlickPay return/webhook re-check the invoice via API before confirming.
- `automations:run` (every minute) queues each message once (`message_logs` unique key) within a 6h catch-up window.
- New course = admin → Courses → New → add a Session. Weeks = number of weekly classes generated.

## Deletion rules (T-01)
- A **course or session with registrations cannot be deleted**: set the course to inactive / close the session instead (database foreign keys use RESTRICT, models and the admin also refuse).
- **Confirmed, paid or invoiced registrations are historical records** and cannot be deleted. "Confirmed" is remembered in `confirmed_at` (set once, never cleared), so moving a registration back to pending/cancelled does not unprotect it. Unpaid pending/cancelled registrations that were never confirmed can be deleted.
- An **automation with message history cannot be deleted**: deactivate it (Active = off).
- **Meetings cannot be removed** from a session that has registrations (the edit page refuses the save); edit their dates/topics instead.
- These guards live in Eloquent models and the Filament admin. **They do not protect raw SQL, query-builder or mass deletes/updates** (`DB::table()`, `Model::where()->delete()`, `deleteQuietly()`, tinker). Only the RESTRICT foreign keys protect courses, sessions and automations at database level; registration rows have no database-level protection.
