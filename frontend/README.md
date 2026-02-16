# Fuel Management System - Frontend

**Vue 3 + Vite + Tailwind CSS**

Real-time dashboard for monitoring fuel inventory, alerts, and critical tanks.

## 🚀 Quick Start

### Development
```bash
npm install
npm run dev
```
Open: http://localhost:5173

### Production Build
```bash
npm run build
```
Output: `dist/` folder

### Preview Production
```bash
npm run preview
```

## 📊 Features

- **Real-time Dashboard** - Auto-refresh every 30 seconds
- **Inventory Stats** - Stations, depots, tanks, fill levels
- **Alert System** - 5 severity levels (Catastrophe → Info)
- **Critical Tanks** - Monitoring with days until empty
- **Responsive Design** - Mobile, tablet, desktop support

## 🔧 Tech Stack

- Vue 3 (Composition API)
- Vite (Build tool)
- Tailwind CSS (Styling)
- Axios (API client)
- Vue Router (Future: Multi-page navigation)

## 📁 Structure

```
src/
├── components/          # Reusable UI components
│   ├── StatCard.vue
│   ├── AlertCard.vue
│   └── CriticalTankCard.vue
├── views/               # Page views
│   └── Dashboard.vue
├── services/            # API integration
│   └── api.js
├── App.vue              # Root component
└── main.js              # Entry point
```

## 🌐 API Integration

Connects to REV 3.0 Backend API:
- Base URL: `https://fuel.kittykat.tech/rev3/backend/public/api`
- 31 endpoints fully integrated
- Dashboard endpoints working

## 📦 Deployment

See `FRONTEND_DEPLOYMENT.md` for full deployment instructions.

**Quick Deploy:**
1. Build: `npm run build`
2. Upload `dist/` folder to server
3. Configure: Set base path in `vite.config.js`

## ✅ Status

- ✅ Dashboard working locally
- ✅ API integration complete
- ✅ Production build ready
- ⏳ Pending server deployment

---

**Last Updated:** 2026-02-16
**Version:** 1.0.0
**Git Commit:** a13bed1
