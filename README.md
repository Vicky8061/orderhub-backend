# OrderHub POS - Backend REST API & Real-Time Engine

OrderHub POS is a high-performance restaurant order management and kitchen display system backend built with **Laravel 12**, **MySQL**, **Laravel Sanctum**, and **Laravel Reverb**.

Designed specifically as a headless REST API to power modern React SPAs, in-store cashier counters, touchscreen kiosks, and real-time Kitchen Display Screens (KDS).

---

## Features

- **Multi-Role Authentication & Authorization**:
  - Roles: `Admin`, `Cashier`, `Kitchen Staff`
  - Secured via Laravel Sanctum Bearer tokens and role-based middleware.
- **Menu, Modifiers & Combos**:
  - Full categorized menu management with image upload.
  - Custom modifier sets (add-ons, removals, size upgrades).
  - Bundled combos with quantity discounts.
- **Order Engine & Calculation**:
  - Atomic daily sequential order numbering (`ORD-001`, `ORD-002`) with row-level transaction locks.
  - Price snapshotting to preserve historical pricing integrity.
  - Automated subtotal, 8% tax calculation, and order totals.
- **Real-Time Kitchen Display Screen (KDS)**:
  - WebSocket broadcasting via **Laravel Reverb**.
  - `OrderPlaced` and `OrderStatusUpdated` broadcasted over private channel `orders.kitchen`.
  - FIFO kitchen queue endpoint (`GET /api/kitchen/queue`).
- **Reports & Printable Receipts**:
  - 80mm thermal receipt PDF generation powered by `barryvdh/laravel-dompdf`.
  - Sales summary, best-sellers, 24-hour revenue breakdown, and cashier performance reports.

---

## Tech Stack

- **Framework**: Laravel 12.x (PHP 8.2+)
- **Database**: MySQL 8.0 (Database name: `orderhub`)
- **Authentication**: Laravel Sanctum (Bearer Token)
- **WebSockets**: Laravel Reverb
- **PDF Generation**: DomPDF

---

## Getting Started

### 1. Prerequisites
- PHP 8.2+
- Composer
- MySQL Server running on `127.0.0.1:3306`

### 2. Installation
Clone the repository and install dependencies:
```bash
git clone https://github.com/Vicky8061/orderhub-backend.git
cd orderhub-backend
composer install
```

### 3. Environment Configuration
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Ensure your database connection in `.env` matches your local MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orderhub
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Setup & Seeding
Create the database in MySQL and run migrations with seeders:
```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### 5. Running the Servers

#### Terminal 1: HTTP API Server
```bash
php artisan serve
```
*API Base URL: `http://127.0.0.1:8000/api`*

#### Terminal 2: Real-Time Reverb WebSocket Server
```bash
php artisan reverb:start
```
*WebSockets running on: `ws://127.0.0.1:8080`*

---

## Seeded Staff Accounts

All seeded accounts use password: **`password123`**

| Role | Email | Permissions |
| :--- | :--- | :--- |
| **Admin** | `admin@orderhub.com` | Full CRUD on menu, combos, modifiers, reports, void orders |
| **Cashier** | `cashier@orderhub.com` | Create orders, view menu, view status, print receipts |
| **Kitchen** | `kitchen@orderhub.com` | View live queue, update statuses (`preparing`, `ready`) |

---

## Testing

Run the automated test suite:
```bash
php artisan test
```
All 20 tests (77 assertions) covering Auth, Menu CRUD, Ordering Engine, Snapshots, Receipts, and Reports will run and validate.
