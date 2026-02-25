# Documentation Map
## Fuel Management System REV 3.0

> One file to rule them all. Read this first.

---

## Active Documents

### 📋 [PROJECT.md](./PROJECT.md)
**What:** Living project reference — все о проекте
- Tech stack & key paths
- Database schema (tables, IDs, field names)
- Critical business rules
- What's built (module by module)
- Roadmap (🔴 High / 🟠 Medium / 🟡 Low)
- Session log (what was done each day)

**Update:** Every session — add to session log, update roadmap.

---

### ⚙️ [TECH_DECISIONS.md](./TECH_DECISIONS.md)
**What:** Technical decisions & lessons learned — почему так, а не иначе
- Units & conversions (liters ↔ tons, immutable rules, PHP UnitConverter)
- Key calculations (procurement formula, forecast, working capital)
- PDF generation (jsPDF v2.5.1, embedded Roboto base64, layout)
- Vue 3 bug: reactive() vs ref() in templates
- Dev principles (PSR-12, DRY, no hardcode, API standards)
- Deploy pipeline (step-by-step commands)

**Update:** When a new technical decision is made or a bug/workaround is discovered.

---

### 🔌 [API.md](./API.md)
**What:** API endpoint reference — все эндпойнты
- Base URL, response envelope format
- Stations, Depots, Fuel Types, Suppliers
- Supplier Station Offers
- Orders (PO + ERP, status flows, all fields)
- Transfers
- Dashboard, Forecast, Parameters, Procurement, Import

**Update:** When a new endpoint is added or an existing one changes.

---

### 🖥️ [SERVER_SETUP.md](./SERVER_SETUP.md)
**What:** Server & hosting details — инфраструктура
- Shared hosting config
- `update.html` deploy trigger
- DB credentials location

**Update:** Rarely.

---

### 🗄️ [archive/](./archive/)
**What:** 25 старых файлов — read-only historical reference
- Old session logs (SESSION_2026-02-*.md)
- Old architecture docs (ARCHITECTURE, SYSTEM_KNOWLEDGE_BASE)
- Old API docs (API_DOCUMENTATION_old)
- Code audits (AUDIT_*.md)
- Feature specs (ORDERS_MODULE, PROCUREMENT_ADVISOR, etc.)
- DB schema history

**Update:** Never. Only add new files here when archiving.

---

## Claude Memory (outside repo)

### 🧠 `~/.claude/projects/.../memory/MEMORY.md`
**What:** Fast-load context for Claude — загружается в начале каждой сессии
- Documentation map (this)
- Critical rules (units, orders, no hardcode)
- PDF + Vue bug quick-reference
- Next priorities

**Update:** Every session.

---

## Quick Reference

| I need to know... | Look in |
|-------------------|---------|
| What's the status of module X? | PROJECT.md → What's Built |
| What to work on next? | PROJECT.md → Roadmap |
| How do liters↔tons convert? | TECH_DECISIONS.md → Units |
| Why is jsPDF v2.5.1 and not v4? | TECH_DECISIONS.md → PDF |
| How to regenerate robotoBase64.js? | TECH_DECISIONS.md → PDF |
| Why reactive() not ref()? | TECH_DECISIONS.md → Vue Bug |
| Deploy step-by-step | TECH_DECISIONS.md → Deploy |
| What fields does GET /api/orders return? | API.md → Orders |
| What are PO vs ERP status flows? | API.md → Orders OR PROJECT.md → Orders Module |
| Station IDs / fuel type IDs? | PROJECT.md → Data Model |
| What happened in session 2026-02-18? | archive/SESSION_2026-02-18.md |
