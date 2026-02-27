import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://fuel.kittykat.tech/rev3/backend/public/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Dashboard API
export const dashboardApi = {
  getSummary: () => api.get('/dashboard/summary'),
  getAlerts: () => api.get('/dashboard/alerts'),
  getAlertsSummary: () => api.get('/dashboard/alerts/summary'),
  getCriticalTanks: () => api.get('/dashboard/critical-tanks'),
  getForecast: (params) => api.get('/dashboard/forecast', { params }),
};

// Stations API
export const stationsApi = {
  getAll: () => api.get('/stations'),
  getById: (id) => api.get(`/stations/${id}`),
  getDepots: (id) => api.get(`/stations/${id}/depots`),
  getTanks: (id) => api.get(`/stations/${id}/tanks`),
};

// Depots API
export const depotsApi = {
  getAll: () => api.get('/depots'),
  getById: (id) => api.get(`/depots/${id}`),
  getTanks: (id) => api.get(`/depots/${id}/tanks`),
  getStock: (id) => api.get(`/depots/${id}/stock`),
  getHistory: (id) => api.get(`/depots/${id}/history`),
};

// Fuel Types API
export const fuelTypesApi = {
  getAll: () => api.get('/fuel-types'),
  getById: (id) => api.get(`/fuel-types/${id}`),
  getTotalStock: (id) => api.get(`/fuel-types/${id}/total-stock`),
  getStationsByFuelType: (id) => api.get(`/fuel-types/${id}/stations`),
  getRegionsByFuelType: (id) => api.get(`/fuel-types/${id}/regions`),
  getDistribution: () => api.get('/fuel-types/distribution'),
  create: (name, code, density) => api.post('/fuel-types', { name, code, density }),
};

// Suppliers API
export const suppliersApi = {
  getAll: () => api.get('/suppliers'),
  getById: (id) => api.get(`/suppliers/${id}`),
  getActive: () => api.get('/suppliers/active'),
  getTop: () => api.get('/suppliers/top'),
  getOrders: (id) => api.get(`/suppliers/${id}/orders`),
  getPerformance: (id) => api.get(`/suppliers/${id}/performance`),
  create: (name) => api.post('/suppliers', { name }),
};

// Orders API
export const ordersApi = {
  getAll:    (params = {}) => api.get('/orders', { params }),
  getById:   (id)          => api.get(`/orders/${id}`),
  getStats:  ()            => api.get('/orders/stats'),
  getPending: ()           => api.get('/orders/pending'),
  getSummary: ()           => api.get('/orders/summary'),
  getRecent:  (days = 30)  => api.get('/orders/recent', { params: { days } }),
  create:    (data)        => api.post('/orders', data),
  createErp: (data)        => api.post('/orders/erp', data),
  update:    (id, data)    => api.put(`/orders/${id}`, data),
  cancel:    (id, reason)  => api.post(`/orders/${id}/cancel`, { reason }),
  delete:    (id)          => api.delete(`/orders/${id}`),
};

// Transfers API
export const transfersApi = {
  getAll:       (params) => api.get('/transfers', { params }),
  getById:      (id)     => api.get(`/transfers/${id}`),
  getByStatus:  (status) => api.get(`/transfers/status/${status}`),
  getByStation: (id)     => api.get(`/transfers/station/${id}`),
  create:       (data)   => api.post('/transfers', data),
};

// Reports API
export const reportsApi = {
  getDailyStock: (date = null) => api.get('/reports/daily-stock', { params: { date } }),
  getInventorySummary: () => api.get('/reports/inventory-summary'),
  getStationPerformance: () => api.get('/reports/station-performance'),
  getLowStock: () => api.get('/reports/low-stock'),
  getCapacityUtilization: () => api.get('/reports/capacity-utilization'),
};

// Cost Analysis API
export const costAnalysisApi = {
  get: () => api.get('/cost-analysis'),
};

// Working Capital API
export const workingCapitalApi = {
  get: () => api.get('/working-capital'),
};

// Regional Comparison API
export const regionalComparisonApi = {
  get: () => api.get('/regional-comparison'),
};

// Procurement Advisor API
export const procurementApi = {
  getUpcomingShortages: (days = 14) => api.get('/procurement/upcoming-shortages', { params: { days } }),
  getSummary: () => api.get('/procurement/summary'),
  getSupplierRecommendations: (fuelTypeId, requiredTons, urgency = 'NORMAL') =>
    api.get('/procurement/supplier-recommendations', {
      params: { fuel_type_id: fuelTypeId, required_tons: requiredTons, urgency }
    }),
  getBestSuppliers: (stationId = null, dayCost = 5) =>
    api.get('/procurement/best-suppliers', {
      params: { ...(stationId ? { station_id: stationId } : {}), day_cost: dayCost }
    }),
};

// Parameters API
export const parametersApi = {
  // GET
  getSystem:         () => api.get('/parameters/system'),
  getFuelTypes:      () => api.get('/parameters/fuel-types'),
  getSalesParams:    () => api.get('/parameters/sales-params'),
  getStockPolicies:  () => api.get('/parameters/stock-policies'),
  getSupplierOffers: () => api.get('/parameters/supplier-offers'),
  getDepotTanks:     () => api.get('/parameters/depot-tanks'),
  // PUT
  updateSystem:         (key, value)   => api.put(`/parameters/system/${key}`, { value }),
  updateFuelType:       (id, data)     => api.put(`/parameters/fuel-types/${id}`, data),
  updateSalesParam:     (id, data)     => api.put(`/parameters/sales-params/${id}`, data),
  updateStockPolicy:    (id, data)     => api.put(`/parameters/stock-policies/${id}`, data),
  updateSupplierOffer:  (id, data)     => api.put(`/parameters/supplier-offers/${id}`, data),
  // POST
  seedStockPolicies:    ()             => api.post('/parameters/stock-policies/seed-defaults'),
};

// Infrastructure (Hierarchy Manager) API
export const infrastructureApi = {
  getHierarchy:    ()         => api.get('/infrastructure/hierarchy'),
  updateStation:   (id, data) => api.put(`/infrastructure/stations/${id}`, data),
  updateDepot:     (id, data) => api.put(`/infrastructure/depots/${id}`, data),
  updateTank:      (id, data) => api.put(`/infrastructure/tanks/${id}`, data),
  addTank:         (data)     => api.post('/infrastructure/tanks', data),
};

// Import API
export const importApi = {
  syncErp: (baseUrl, periodDays) => api.post('/import/sync-erp', {
    base_url:    baseUrl,
    period_days: periodDays,
  }),
};

// Crisis Resolution API
export const crisisApi = {
  // Get split_delivery + transfer options for a critical depot
  getOptions: (depotId, fuelTypeId) =>
    api.get('/crisis/options', { params: { depot_id: depotId, fuel_type_id: fuelTypeId } }),

  // Accept a proposal → creates crisis_case record
  // type: 'split_delivery' | 'transfer'
  acceptProposal: (data) => api.post('/crisis/accept', data),

  // Link a compensating PO to an existing case (after user creates PO in step 4)
  linkPO: (caseId, poRole, poId) =>
    api.post('/crisis/link-po', { case_id: caseId, po_role: poRole, po_id: poId }),

  // List all cases (optionally filter by status)
  getCases: (status = null) =>
    api.get('/crisis/cases', status ? { params: { status } } : {}),

  // Mark a case as resolved
  resolveCase: (id) => api.post(`/crisis/cases/${id}/resolve`),
};

export default api;
