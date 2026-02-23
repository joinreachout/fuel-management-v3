# Fuel Management System — REV 3.0

Fuel supply optimization system for a network of 9 gas stations across 3 regions of Kyrgyzstan.
Tracks stock levels, forecasts shortages, recommends procurement orders, and manages transfers between depots.

**Live:** https://fuel.kittykat.tech/rev3/

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.1 (no framework), custom router, PDO/MySQL |
| Frontend | Vue 3 + Vite + TailwindCSS + Chart.js |
| Database | MySQL 8.0 (`d105380_fuelv3` on shared hosting) |
| Deploy | `npm run build` → `git push` → `git pull` on server |

---

## Project Structure

```
REV 3.0/
├── backend/
│   ├── public/index.php        # Router + all endpoints
│   └── src/
│       ├── Controllers/        # HTTP request handlers
│       ├── Models/             # DB models (raw SQL)
│       └── Services/           # Business logic
│           ├── ForecastService.php
│           ├── ProcurementAdvisorService.php
│           ├── AlertService.php
│           └── ...
├── frontend/
│   └── src/
│       ├── views/              # Pages (Dashboard, Orders, Transfers, Parameters)
│       ├── components/         # Reusable widgets
│       └── services/api.js     # All API calls
├── database/
│   └── migrations/             # SQL migration files (001, 002, ...)
└── docs/                       # Documentation (see below)
```

---

## Deploy

```bash
# Build frontend
cd frontend && npm run build

# Push to server (auto-deploys via git pull hook)
git add -f frontend/dist/
git commit -m "deploy: ..."
git push

# SSH to server (if needed)
ssh -i ~/.ssh/id_kittykat virt105026@kittykat.tech
# cd /data01/virt105026/domeenid/www.kittykat.tech/fuel/rev3/
# git pull origin main
```

---

## Local Development

```bash
# Frontend
cd frontend && npm install && npm run dev

# Backend — PHP built-in server
cd backend/public && php -S localhost:8000
```

Copy `.env.example` to `.env` and set DB credentials.

---

## Documentation

| File | Description |
|------|-------------|
| [docs/SYSTEM_KNOWLEDGE_BASE.md](docs/SYSTEM_KNOWLEDGE_BASE.md) | Full system reference: DB schema, calculations, business logic |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Units, conversions, data model |
| [docs/SERVER_SETUP.md](docs/SERVER_SETUP.md) | Server configuration & SSH setup |
| [docs/features/PROCUREMENT_ADVISOR.md](docs/features/PROCUREMENT_ADVISOR.md) | Procurement Advisor feature details |
| [docs/features/WORKING_CAPITAL.md](docs/features/WORKING_CAPITAL.md) | Working Capital analysis |
| [docs/audits/](docs/audits/) | Code audit trail (Feb 2026) |
| [DEVELOPMENT_PRINCIPLES.md](DEVELOPMENT_PRINCIPLES.md) | Coding standards for this project |
| [API_DOCUMENTATION.md](API_DOCUMENTATION.md) | API endpoints reference |
| [PROGRESS.md](PROGRESS.md) | Current status + backlog |

---

## Key Business Rules

- Stock tracked in **liters** in DB, displayed in **tons** in UI
- Density per fuel type used for liter↔ton conversion (see docs/ARCHITECTURE.md)
- Forecast = linear consumption curve + scheduled order deliveries (`confirmed`/`in_transit`)
- Procurement threshold = `(stock - min_level) / daily_consumption < lead_time_days`
- All SQL divisions protected with `NULLIF(x, 0)` to prevent division by zero

---

*Private — All Rights Reserved*
