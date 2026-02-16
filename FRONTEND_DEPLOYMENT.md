# Frontend Deployment Guide

**Date:** 2026-02-16
**Frontend:** Vue 3 + Vite + Tailwind CSS
**Production URL:** https://fuel.kittykat.tech/rev3/frontend/

---

## 📦 What's Included

### Dashboard Features:
- ✅ Real-time inventory stats (stations, depots, tanks)
- ✅ Alert system with 5 severity levels
- ✅ Critical tanks monitoring with days until empty
- ✅ Auto-refresh every 30 seconds
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Clean, modern UI with Tailwind CSS

### Components:
- `StatCard.vue` - Key metrics display
- `AlertCard.vue` - Alert notifications by severity
- `CriticalTankCard.vue` - Tank status with progress bars
- `Dashboard.vue` - Main dashboard view

### API Integration:
- Full integration with all 31 backend endpoints
- Axios-based API client
- Error handling and loading states

---

## 🚀 Deployment Steps

### Option 1: Manual Upload (Current Method)

1. **Build for Production:**
   ```bash
   cd frontend
   npm run build
   ```

2. **Upload dist folder to server:**
   - Local path: `frontend/dist/`
   - Server path: `fuel.kittykat.tech/rev3/frontend/`
   - Files to upload:
     - `index.html`
     - `assets/` folder (all CSS and JS files)

3. **Test the deployment:**
   ```bash
   curl https://fuel.kittykat.tech/rev3/frontend/
   ```

### Option 2: Automated Deployment (Future)

Create a deploy script:
```bash
#!/bin/bash
cd frontend
npm run build
# Upload to server via FTP/SFTP
```

---

## 📁 File Structure

```
frontend/
├── dist/                    # Production build (deploy this)
│   ├── index.html
│   └── assets/
│       ├── index-*.css
│       └── index-*.js
├── src/
│   ├── components/          # Vue components
│   ├── views/               # Page views
│   ├── services/            # API client
│   ├── App.vue              # Root component
│   ├── main.js              # Entry point
│   └── style.css            # Global styles
├── .env                     # Development config
├── .env.production          # Production config
├── package.json
├── vite.config.js
└── tailwind.config.js
```

---

## ⚙️ Configuration

### Environment Variables

**Development (.env):**
```
VITE_API_URL=https://fuel.kittykat.tech/rev3/backend/public/api
```

**Production (.env.production):**
```
VITE_API_URL=https://fuel.kittykat.tech/rev3/backend/public/api
```

### Vite Config (vite.config.js)
```js
export default defineConfig({
  plugins: [vue()],
  base: '/rev3/frontend/',  // Add this for subdirectory deployment
})
```

---

## 🧪 Testing Locally

### Development Server:
```bash
cd frontend
npm run dev
```
Open: http://localhost:5173

### Preview Production Build:
```bash
cd frontend
npm run build
npm run preview
```
Open: http://localhost:4173

---

## 🌐 Production Checklist

- ✅ API URL configured correctly
- ✅ CORS enabled on backend
- ✅ Production build optimized
- ⏳ Upload dist folder to server
- ⏳ Test all dashboard features
- ⏳ Verify API calls working
- ⏳ Check responsive design on mobile

---

## 📊 Dashboard Features

### Main Stats (Top Row):
1. **Total Stations** - 9 stations
2. **Total Depots** - 19 depots
3. **Total Tanks** - 95 tanks
4. **Avg Fill Level** - 68.4% (139M liters)

### Alert Summary (Second Row):
- **Catastrophe** - 0 (red)
- **Critical** - 0 (orange)
- **Must Order** - 2 (yellow)
- **Warning** - 3 (blue)
- **Info** - 5 (gray)

### Active Alerts (Left Column):
- Real-time alerts from backend
- Color-coded by severity
- Message + details display

### Critical Tanks (Right Column):
- Tanks with < 7 days until empty
- Current stock vs capacity
- Daily consumption rate
- Progress bar visualization

---

## 🔄 Auto-Refresh

Dashboard automatically refreshes every **30 seconds** to show real-time data.

---

## 📱 Responsive Design

- **Desktop:** Full 2-column layout
- **Tablet:** Adaptive grid
- **Mobile:** Single column, stacked cards

---

## 🐛 Troubleshooting

### Issue: Blank page
**Solution:** Check browser console for errors, verify API URL

### Issue: CORS error
**Solution:** Ensure backend has proper CORS headers

### Issue: API not loading
**Solution:** Check backend is running, verify network requests

---

## 🎯 Next Steps

1. ⏳ Deploy frontend to production
2. ⏳ Add more pages (Stations, Depots, Orders)
3. ⏳ Implement routing (Vue Router)
4. ⏳ Add authentication
5. ⏳ Add charts (Chart.js integration)

---

**Status:** ✅ BUILD READY - PENDING DEPLOYMENT
**Local Development:** ✅ Working on http://localhost:5174
**Production Build:** ✅ Generated in dist/
**Git Commit:** ef7c846
