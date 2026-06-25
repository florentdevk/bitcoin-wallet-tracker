# bitcoin-wallet-tracker

A REST API built with Symfony 8.1 to monitor Bitcoin addresses in real-time, trigger balance alerts and receive email notifications.

## Features

- **JWT Authentication** — Register and login with secure JWT tokens
- **Bitcoin Address Monitoring** — Watch Bitcoin addresses and retrieve real-time balance and transaction history via [mempool.space](https://mempool.space) API
- **Alert System** — Create threshold alerts (`balance_above`, `balance_below`) triggered automatically
- **Background Sync** — Symfony Scheduler polls watched addresses every 5 minutes via Symfony Messenger
- **Email Notifications** — Receive email alerts when a threshold is reached via Symfony Mailer

## How it works

1. **Create an account** and login to get a JWT token
2. **Add Bitcoin addresses** you want to monitor — your own wallet, an exchange address, or any public address
3. **View real-time data** — balance in BTC and full transaction history pulled from the Bitcoin blockchain via mempool.space
4. **Set alerts** — for example "notify me when this address holds more than 1 BTC" or "notify me when balance drops below 0.5 BTC"
5. **Background sync** — every 5 minutes, the app automatically checks all watched addresses and triggers alerts if thresholds are reached
6. **Email notification** — when an alert fires, you receive an email with the address and threshold details

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4 |
| Framework | Symfony 8.1 |
| Database | PostgreSQL 16 |
| Cache | Redis 7 |
| Auth | JWT (LexikJWTAuthenticationBundle) |
| Async | Symfony Messenger + Scheduler |
| Mailer | Symfony Mailer + Mailpit (dev) |
| Quality | PHPStan level 6, PHP CS Fixer, PHPUnit 13 |
| CI/CD | GitHub Actions |
| Infra | Docker + Docker Compose |

## Getting Started

### Prerequisites

- Docker + Docker Compose
- PHP 8.4
- Composer

### Installation

```bash
git clone https://github.com/florentdevk/bitcoin-wallet-tracker.git
cd bitcoin-wallet-tracker
composer install
```

### Configuration

```bash
cp .env .env.local
# Edit .env.local with your values
```

### Start services

```bash
docker compose up -d
```

### Generate JWT keys

```bash
php bin/console lexik:jwt:generate-keypair
```

### Create database

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Start the server

```bash
php -S localhost:8000 -t public/
```

### Start the Messenger worker (background processing)

```bash
php bin/console messenger:consume async --time-limit=3600
```

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth/register` | Create an account | No |
| POST | `/api/auth/login` | Get a JWT token | No |

### Addresses

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/addresses` | List watched addresses | Yes |
| POST | `/api/addresses` | Add a Bitcoin address | Yes |
| GET | `/api/addresses/{id}` | Get balance + transactions | Yes |
| DELETE | `/api/addresses/{id}` | Remove an address | Yes |

### Alerts

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/alerts` | List alerts | Yes |
| POST | `/api/alerts` | Create an alert | Yes |
| DELETE | `/api/alerts/{id}` | Delete an alert | Yes |

### Alert types

- `balance_above` — triggers when balance exceeds the threshold (BTC)
- `balance_below` — triggers when balance falls below the threshold (BTC)

## Usage Examples

```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password123"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password123"}'

# Watch a Bitcoin address
curl -X POST http://localhost:8000/api/addresses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"address": "bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh", "label": "My wallet"}'

# Get address balance and transactions
curl http://localhost:8000/api/addresses/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Create a balance alert
curl -X POST http://localhost:8000/api/alerts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"watched_address_id": 1, "type": "balance_above", "threshold_value": 1.0}'
```

## Testing

```bash
php bin/phpunit
```

## Code Quality

```bash
# Fix code style
vendor/bin/php-cs-fixer fix

# Static analysis (level 6)
vendor/bin/phpstan analyse
```

## Architecture

```
src/
├── Controller/         # REST API endpoints
├── Entity/             # Doctrine entities (User, WatchedAddress, Transaction, Alert)
├── Enum/               # AlertType enum
├── Message/            # Messenger message
├── MessageHandler/     # Messenger handler
├── Repository/         # Custom Doctrine queries
├── Schedule.php        # Symfony Scheduler (every 5 minutes)
├── Security/
│   └── Voter/          # Custom voters (AddressVoter, AlertVoter)
└── Service/
    ├── Alert/          # AlertChecker, AlertNotifier
    └── Bitcoin/        # MempoolClient, AddressInfoProvider
```