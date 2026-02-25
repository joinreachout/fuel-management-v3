# Fuel Management System — REV 3.0

Fuel supply optimization for 9 gas stations across 3 regions of Kyrgyzstan.
Tracks stock levels, forecasts shortages, recommends procurement orders, manages transfers.

**Live:** https://fuel.kittykat.tech/rev3/

---

## Documentation → start here

| File | What's inside |
|------|--------------|
| **[docs/README.md](docs/README.md)** | 🗺️ Navigation map — what's where, quick-reference table |
| [docs/PROJECT.md](docs/PROJECT.md) | Project status, data model, what's built, roadmap, session log |
| [docs/TECH_DECISIONS.md](docs/TECH_DECISIONS.md) | Architecture, units/conversions, PDF, Vue gotchas, deploy pipeline |
| [docs/API.md](docs/API.md) | All API endpoints with request/response examples |
| [docs/SERVER_SETUP.md](docs/SERVER_SETUP.md) | Server config, hosting, deploy trigger |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.1, no framework — custom Router + PDO/MySQL, MVC+Services |
| Frontend | Vue 3 + Vite + TailwindCSS v4 + Chart.js (`<script setup>`) |
| PDF | jsPDF v2.5.1 + Roboto font (Cyrillic) |
| Database | MySQL 8.0 (`d105380_fuelv3`, shared hosting) |
| Deploy | `npm run build` → `git add -f frontend/dist/` → `git push` → `update.html` on server |

---

## Project Structure

```
├── backend/
│   ├── public/index.php          # Router + all route registrations
│   └── src/
│       ├── Controllers/          # HTTP request handlers
│       ├── Services/             # Business logic
│       ├── Models/               # DB wrappers
│       └── Utils/UnitConverter.php
├── frontend/
│   └── src/
│       ├── views/                # Dashboard, Orders, Transfers, Parameters, Import
│       ├── components/           # Reusable widgets
│       ├── services/api.js       # All API calls
│       └── utils/robotoBase64.js # Pre-encoded Roboto font for PDF
├── database/
│   └── migrations/               # SQL migrations 001–008
└── docs/
    ├── README.md                 # ← Navigation map (start here)
    ├── PROJECT.md
    ├── TECH_DECISIONS.md
    ├── API.md
    ├── SERVER_SETUP.md
    └── archive/                  # Old sessions, audits, superseded docs
```

---

## Local Development

```bash
# Frontend
cd frontend && npm install && npm run dev

# Backend
cd backend/public && php -S localhost:8000
```

Copy `.env.example` → `.env`, set DB credentials.

---

## Critical Rules (quick reminder)

- Stock in **liters** in DB; displayed in **tons** in UI — never `tons = liters/1000`, always use density
- `UnitConverter::litreToTon()` / `::tonToLitre()` — never inline the formula
- jsPDF must stay **v2.5.1** — v4 breaks Cyrillic font
- `reactive()` not `ref()` for sort objects passed as template function args
- English only in code/comments; Russian only in DB content (station names)

*Private — All Rights Reserved*
