# Livraria

A simple online bookstore built with [Laravel](https://laravel.com/) located in the `minha-livraria` directory. It allows visitors to browse a catalog of books, add items to a cart and complete the checkout process. An admin area is included for managing books, categories, orders and more.

## Installation

1. Clone the repository and enter the project folder.

```bash
git clone <repo-url>
cd livrariaa/minha-livraria
```

2. Install PHP and Node.js dependencies (requires PHP 8.2 and Composer).

```bash
composer install
npm install
```

3. Copy the example environment file and generate an application key.

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database connection in `.env` (for example using SQLite or MySQL) and any mail credentials you require.

5. Run database migrations and seeders to create initial data.

```bash
php artisan migrate
php artisan db:seed
```

6. Build frontend assets and start the development server.

```bash
npm run dev
php artisan serve
```

## Features

- **Catalog browsing** – public listing of books with detail pages.
- **Shopping cart** – add, update or remove items before checkout.
- **Checkout** – place orders and track them in your account area.
- **Admin area** – manage catalog, categories, orders, reviews and coupons.

## Running Tests

Automated tests can be executed with:

```bash
composer test
```

## Required Environment Variables

At minimum the following variables must be configured in your `.env` file:

- `APP_KEY` – set via `php artisan key:generate`.
- `DB_CONNECTION`, `DB_DATABASE` and related database settings.
- `MAIL_MAILER` and mail credentials if email sending is desired.

Refer to `.env.example` for the complete list of options.

