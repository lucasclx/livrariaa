# API Overview

The application exposes a small JSON API under the `/api` prefix. Some endpoints are public while others require authentication via Laravel Sanctum.

## Public Endpoints
- `GET /api/livros` – list all books
- `GET /api/livros/{id}` – retrieve a specific book
- `GET /api/categorias` – list all categories

## Authenticated Endpoints
These routes require a bearer token obtained after authenticating a user.
- `POST /api/carrinho/adicionar` – add a book to the cart
- `GET /api/carrinho` – view the current cart
- `POST /api/avaliacoes` – submit a review for a book

## Example
```bash
# Obtain a token by logging in (not shown) and then call protected routes
curl -H "Authorization: Bearer <token>" https://example.com/api/carrinho
```

For additional functionality refer to the web routes defined in `routes/web.php`.
