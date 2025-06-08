# Installation Guide

Follow these steps to get the project running locally.

## Requirements
- PHP 8.2 or higher
- Composer
- Node.js and npm

## Steps
1. Clone the repository and enter the application directory:
   ```bash
   git clone <repo-url>
   cd minha-livraria
   ```
2. Install PHP and JavaScript dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy the example environment file and generate the application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Configure database settings in `.env` (SQLite is enabled by default) and adjust any mail credentials you require.
5. Run the database migrations and seeders:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
6. Build front‑end assets and start the development server:
   ```bash
   npm run dev
   php artisan serve
   ```

Run the test suite at any time with `composer test` or simply `make test` if you have the Makefile installed.
