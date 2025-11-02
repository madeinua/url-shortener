# URL Shortener (aka bit.ly)

A tiny, deterministic URL shortener: Symfony API for creating short codes, a redirect endpoint that increments click counts, and a placeholder static front-end.

---

## Prerequisites

- Docker + Docker Compose
- Node.js ≥ 18 (recommended 20) inside your dev environment (e.g., WSL)

---

## 1) Backend (Symfony) — build & run

```bash
# From repo root
docker compose up -d --build

# Install PHP deps
docker compose exec php composer install

# Create/refresh DB schema (dev)
docker compose exec php bin/console doctrine:schema:update --force

# Backend is now available:
# API base      : http://localhost:8080/api
# Redirects     : http://localhost:8080/r/{code}
```

---

## 2) Frontend (React + Vite + Tailwind)

### Dev mode (separate dev server)

```bash
cd frontend
cp .env.example .env
# Point the SPA to the backend via nginx:
# VITE_API_BASE_URL=http://localhost:8080/api
npm install
npm run dev
# Open http://localhost:5173
```

### Production build (served by nginx on :8080)

```bash
cd frontend
npm install
npm run build
# Dist is mounted by nginx in docker-compose; refresh:
docker compose restart nginx
# Open http://localhost:8080
```

---

## 3) Tests (PHPUnit)

Create the **test** database once:

```bash
docker compose exec db mariadb -uroot -proot \
  -e "CREATE DATABASE IF NOT EXISTS bitly_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Apply schema (test env) and run tests:

```bash
docker compose exec -e APP_ENV=test php bin/console doctrine:schema:update --force -e test
docker compose exec -e APP_ENV=test php ./vendor/bin/phpunit
```

---

## API

Base URL (dev): `http://localhost:8080`

All responses are JSON. The same canonical URL always returns the same code.

### Create short URL: `POST /api/urls`

**Request**

```bash
curl -s -X POST http://localhost:8080/api/urls \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com/Path//to?b=2&a=1#frag"}'
```

**Response `201`**

```json
{
    "code": "Ab12xyz",
    "short_url": "http://localhost:8080/r/Ab12xyz",
    "url": "https://example.com/Path/to?a=1&b=2",
    "clicks": 0,
    "created_at": "2025-11-02T12:34:56+00:00",
    "last_accessed_at": null
}
```

Rules:

- Only `http`/`https`
- Max length: 2048 chars
- Idempotent for the same canonical URL (returns same `code`)

---

### Redirect: `GET /r/{code}`

**Behavior**: `302` redirect to the original URL and increments the `clicks` counter.

```bash
curl -i http://localhost:8080/r/Ab12xyz
```

---

### Get details: `GET /api/urls/{code}`

```bash
curl -s http://localhost:8080/api/urls/Ab12xyz | jq
```

**Response `200`**

```json
{
    "code": "Ab12xyz",
    "short_url": "http://localhost:8080/r/Ab12xyz",
    "url": "https://example.com/Path/to?a=1&b=2",
    "clicks": 2,
    "created_at": "2025-11-02T12:34:56+00:00",
    "last_accessed_at": "2025-11-02T13:00:00+00:00"
}
```

---

### Get stats: `GET /api/urls/{code}/stats`

```bash
curl -s http://localhost:8080/api/urls/Ab12xyz/stats | jq
```

**Response `200`**

```json
{
    "code": "Ab12xyz",
    "clicks": 2,
    "created_at": "2025-11-02T12:34:56+00:00",
    "last_accessed_at": "2025-11-02T13:00:00+00:00"
}
```

---

### List URLs: `GET /api/urls`

Query params:

- `limit` (default 50, max 200)
- `offset` (default 0)

```bash
curl -s "http://localhost:8080/api/urls?limit=50&offset=0" | jq
```

**Response `200`**

```json
[
    {
        "code": "Ab12xyz",
        "short_url": "http://localhost:8080/r/Ab12xyz",
        "url": "https://example.com/Path/to?a=1&b=2",
        "clicks": 2,
        "created_at": "2025-11-02T12:34:56+00:00",
        "last_accessed_at": "2025-11-02T13:00:00+00:00"
    }
]
```

---

### Common errors

- `400 Bad Request` — invalid URL (`blank`, `ftp://…`, >2048 chars, etc.)
- `404 Not Found` — unknown `code`
