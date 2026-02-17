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
};

// Suppliers API
export const suppliersApi = {
  getAll: () => api.get('/suppliers'),
  getById: (id) => api.get(`/suppliers/${id}`),
  getActive: () => api.get('/suppliers/active'),
  getOrders: (id) => api.get(`/suppliers/${id}/orders`),
  getPerformance: (id) => api.get(`/suppliers/${id}/performance`),
};

// Orders API
export const ordersApi = {
  getAll: () => api.get('/orders'),
  getById: (id) => api.get(`/orders/${id}`),
  getByStatus: (status) => api.get(`/orders/status/${status}`),
  getPending: () => api.get('/orders/pending'),
  getBySupplier: (id) => api.get(`/orders/supplier/${id}`),
};

// Transfers API
export const transfersApi = {
  getAll: (params) => api.get('/transfers', { params }),
  getById: (id) => api.get(`/transfers/${id}`),
  getByStatus: (status) => api.get(`/transfers/status/${status}`),
  getByStation: (id) => api.get(`/transfers/station/${id}`),
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

// Regional Comparison API
export const regionalComparisonApi = {
  get: () => api.get('/regional-comparison'),
};

export default api;
